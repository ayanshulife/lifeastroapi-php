<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Engine;

/**
 * Zero-dependency post-processing of Chromium-rendered PDFs (a port of the Node.js SDK's
 * src/pdf/outline.ts and the Dart SDK's link reader):
 *
 * - {@see addOutline()} appends a bookmark outline as an incremental update: the original bytes
 *   are not rewritten; new objects, a new xref section and a new trailer are appended.
 * - {@see linkTargetPages()} lists the page each URI link annotation sits on, which the Brihad
 *   table of contents uses to find the exact page of every section.
 *
 * Both expect a cross-reference-table PDF with uncompressed object dictionaries, which is what
 * Chromium writes.
 */
final class PdfOutline
{
    private function __construct()
    {
    }

    /** The number of pages of a Chromium-rendered PDF. */
    public static function pageCount(string $pdf): int
    {
        return \count(self::parse($pdf)['pages']);
    }

    /**
     * For every URI link annotation, the 1-based page it is on (the first page wins when a URI
     * appears more than once).
     *
     * @return array<string, int>
     *
     * @throws PdfException When the PDF cannot be read.
     */
    public static function linkTargetPages(string $pdf): array
    {
        $p = self::parse($pdf);
        $out = [];
        foreach ($p['pages'] as $i => $pageObj) {
            $page = $p['bodies'][$pageObj] ?? '';
            $refs = null;
            if (preg_match('/\/Annots\s*\[([^\]]*)\]/', $page, $m) === 1) {
                $refs = $m[1];
            } elseif (preg_match('/\/Annots\s+(\d+)\s+0\s+R/', $page, $m) === 1) {
                $arr = $p['bodies'][(int) $m[1]] ?? '';
                if (preg_match('/\[([^\]]*)\]/', $arr, $mm) === 1) {
                    $refs = $mm[1];
                }
            }
            if ($refs === null) {
                continue;
            }
            $annots = [];
            preg_match_all('/(\d+)\s+0\s+R/', $refs, $all);
            foreach ($all[1] as $ref) {
                $annots[] = $p['bodies'][(int) $ref] ?? '';
            }
            if (str_contains($refs, '<<')) {
                $annots[] = $refs;
            }
            foreach ($annots as $annot) {
                preg_match_all('/\/URI\s*\(((?:\\\\.|[^\\\\)])*)\)/', $annot, $uris);
                foreach ($uris[1] as $raw) {
                    $uri = (string) preg_replace('/\\\\(.)/s', '$1', $raw);
                    $out[$uri] ??= $i + 1;
                }
            }
        }

        return $out;
    }

    /**
     * Append a bookmark outline and return the new PDF bytes.
     *
     * @param list<array{title: string, page: int, level: int}> $items In order; `page` is 1-based,
     *     `level` the 1-based depth.
     *
     * @throws PdfException When the PDF cannot be read.
     */
    public static function addOutline(string $pdf, array $items): string
    {
        if ($items === []) {
            return $pdf;
        }
        $parsed = self::parse($pdf);
        $pages = $parsed['pages'];

        $valid = [];
        foreach ($items as $item) {
            if (trim($item['title']) === '') {
                continue;
            }
            $valid[] = [
                'title' => $item['title'],
                'page' => max(1, min($item['page'], \count($pages))),
                'level' => max(1, $item['level']),
            ];
        }
        if ($valid === []) {
            return $pdf;
        }

        $outlineRoot = $parsed['maxObj'] + 1;
        $itemObj = static fn (int $i): int => $outlineRoot + 1 + $i;
        $ref = static fn (int $obj): string => "{$obj} 0 R";

        $n = \count($valid);
        $parent = array_fill(0, $n, -1);
        /** @var array<int, list<int>> $children */
        $children = array_fill(0, $n, []);
        $rootChildren = [];
        $stack = [];
        for ($i = 0; $i < $n; $i++) {
            $level = $valid[$i]['level'];
            while ($stack !== [] && $valid[$stack[\count($stack) - 1]]['level'] >= $level) {
                array_pop($stack);
            }
            if ($stack === []) {
                $rootChildren[] = $i;
            } else {
                $top = $stack[\count($stack) - 1];
                $parent[$i] = $top;
                $children[$top][] = $i;
            }
            $stack[] = $i;
        }

        $countOpen = static function (int $i) use (&$countOpen, $children): int {
            $c = \count($children[$i]);
            foreach ($children[$i] as $child) {
                $c += $countOpen($child);
            }

            return $c;
        };

        $bodies = [];
        $total = \count($rootChildren);
        foreach ($rootChildren as $r) {
            $total += $countOpen($r);
        }
        $bodies[$outlineRoot] = '<< /Type /Outlines /First ' . $ref($itemObj($rootChildren[0]))
            . ' /Last ' . $ref($itemObj($rootChildren[\count($rootChildren) - 1])) . " /Count {$total} >>";

        for ($i = 0; $i < $n; $i++) {
            $par = $parent[$i];
            $siblings = $par === -1 ? $rootChildren : $children[$par];
            $kids = $children[$i];
            $pos = (int) array_search($i, $siblings, true);
            $parentRef = $par === -1 ? $ref($outlineRoot) : $ref($itemObj($par));
            $b = '<< /Title ' . self::pdfText($valid[$i]['title']) . ' /Parent ' . $parentRef;
            if ($pos > 0) {
                $b .= ' /Prev ' . $ref($itemObj($siblings[$pos - 1]));
            }
            if ($pos < \count($siblings) - 1) {
                $b .= ' /Next ' . $ref($itemObj($siblings[$pos + 1]));
            }
            if ($kids !== []) {
                $b .= ' /First ' . $ref($itemObj($kids[0])) . ' /Last ' . $ref($itemObj($kids[\count($kids) - 1]));
                $count = \count($kids);
                foreach ($kids as $k) {
                    $count += $countOpen($k);
                }
                $b .= " /Count {$count}";
            }
            $b .= ' /Dest [' . $ref($pages[$valid[$i]['page'] - 1]) . ' /Fit] >>';
            $bodies[$itemObj($i)] = $b;
        }

        // The Catalog again, with /Outlines and /PageMode /UseOutlines added.
        $rootObj = $parsed['root'];
        if (preg_match('/(?<![0-9])' . $rootObj . '\s+0\s+obj([\s\S]*?)endobj/', $pdf, $m) !== 1) {
            throw new PdfException('PDF outline: could not read the /Root object');
        }
        $inner = trim($m[1]);
        $inner = (string) preg_replace('/\/Outlines\s+\d+\s+0\s+R/', '', $inner);
        $inner = (string) preg_replace('/\/PageMode\s*\/\w+/', '', $inner);
        $close = strrpos($inner, '>>');
        if ($close === false) {
            throw new PdfException('PDF outline: the /Root object is not a dictionary');
        }
        $bodies[$rootObj] = substr($inner, 0, $close) . ' /Outlines ' . $ref($outlineRoot)
            . ' /PageMode /UseOutlines ' . substr($inner, $close);
        ksort($bodies);

        $out = $pdf . "\n";
        $offsets = [];
        foreach ($bodies as $obj => $body) {
            $offsets[$obj] = \strlen($out);
            $out .= "{$obj} 0 obj\n{$body}\nendobj\n";
        }

        $xrefStart = \strlen($out);
        $xref = "xref\n";
        $objs = array_keys($offsets);
        $i = 0;
        while ($i < \count($objs)) {
            $j = $i;
            while ($j + 1 < \count($objs) && $objs[$j + 1] === $objs[$j] + 1) {
                $j++;
            }
            $xref .= $objs[$i] . ' ' . ($j - $i + 1) . "\n";
            for ($k = $i; $k <= $j; $k++) {
                $xref .= str_pad((string) $offsets[$objs[$k]], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
            }
            $i = $j + 1;
        }
        $maxNew = $objs[\count($objs) - 1];
        $xref .= 'trailer' . "\n" . '<< /Size ' . ($maxNew + 1) . ' /Root ' . $ref($rootObj)
            . ' /Prev ' . $parsed['prevStartXref'] . " >>\nstartxref\n{$xrefStart}\n%%EOF\n";

        return $out . $xref;
    }

    /**
     * @return array{maxObj: int, root: int, pages: list<int>, prevStartXref: int, bodies: array<int, string>}
     *
     * @throws PdfException
     */
    private static function parse(string $pdf): array
    {
        $maxObj = 0;
        preg_match_all('/(\d+)\s+0\s+obj(?![A-Za-z0-9_])/', $pdf, $all);
        foreach ($all[1] as $n) {
            $maxObj = max($maxObj, (int) $n);
        }
        if (preg_match('/\/Root\s+(\d+)\s+0\s+R/', $pdf, $m) !== 1) {
            throw new PdfException('PDF outline: /Root not found in the trailer');
        }
        $root = (int) $m[1];

        $pages = [];
        $bodies = [];
        preg_match_all('/(\d+)\s+0\s+obj([\s\S]*?)endobj/', $pdf, $defs, PREG_SET_ORDER);
        foreach ($defs as $def) {
            $obj = (int) $def[1];
            $bodies[$obj] = $def[2];
            if (
                preg_match('/\/Type\s*\/Page(?![A-Za-z0-9_])/', $def[2]) === 1
                && preg_match('/\/Type\s*\/Pages(?![A-Za-z0-9_])/', $def[2]) !== 1
            ) {
                $pages[] = $obj;
            }
        }
        $prev = 0;
        preg_match_all('/startxref\s+(\d+)/', $pdf, $xrefs);
        foreach ($xrefs[1] as $x) {
            $prev = (int) $x;
        }
        if ($pages === []) {
            throw new PdfException('PDF outline: no /Type /Page objects found');
        }

        return ['maxObj' => $maxObj, 'root' => $root, 'pages' => $pages, 'prevStartXref' => $prev, 'bodies' => $bodies];
    }

    /** A PDF text string: UTF-16BE with a BOM, as a literal `( ... )`. */
    private static function pdfText(string $s): string
    {
        $bytes = "\xFE\xFF" . mb_convert_encoding($s, 'UTF-16BE', 'UTF-8');

        return '(' . strtr($bytes, ['(' => '\\(', ')' => '\\)', '\\' => '\\\\']) . ')';
    }
}
