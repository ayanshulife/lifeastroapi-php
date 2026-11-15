<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Engine;

/**
 * Prints HTML to PDF with a headless Chrome, Chromium or Edge on your own server, the way
 * Puppeteer does (DevTools protocol, `Page.printToPDF`). No Composer package and no Node.js are
 * needed, only the browser, and the PDF is made on your machine: nothing is sent to LifeAstroAPI.
 *
 * Launching a browser takes a moment. For many PDFs, launch one engine, pass it to every call and
 * close it at the end:
 *
 * ```php
 * $engine = ChromePdfEngine::launch();
 * try {
 *     $pdf = Reports::kundliLitePdf($report, engine: $engine);
 * } finally {
 *     $engine->close();
 * }
 * ```
 *
 * On Debian / Ubuntu: `apt install chromium fonts-noto-core` (the Noto fonts cover every Indic
 * script the reports use).
 */
final class ChromePdfEngine implements PdfEngine
{
    /** Waits for the document, its images and its web fonts. */
    private const WAIT_FOR_LOAD = <<<'JS'
(async () => {
  if (document.readyState !== 'complete') {
    await new Promise((r) => window.addEventListener('load', r, { once: true }));
  }
  await Promise.all(Array.from(document.images).map((img) => img.complete ? null :
    new Promise((r) => { img.addEventListener('load', r, { once: true });
                         img.addEventListener('error', r, { once: true }); })));
  if (document.fonts && document.fonts.ready) await document.fonts.ready;
  return true;
})()
JS;

    private int $nextId = 0;

    private bool $closed = false;

    /** @param resource|null $process */
    private function __construct(
        private readonly WebSocketClient $ws,
        private $process = null,
        private readonly ?string $userDataDir = null,
        private readonly ?string $logFile = null,
    ) {
    }

    /**
     * Launch a headless browser for this engine.
     *
     * @param string|null $executablePath Default: `LIFEASTRO_CHROME`, `CHROME_PATH` or
     *     `PUPPETEER_EXECUTABLE_PATH` from the environment, then the usual install locations.
     * @param bool|null $noSandbox Pass `--no-sandbox`. Default: only when PHP runs as root
     *     (Chrome refuses to start as root otherwise, typically in a Docker container).
     * @param list<string> $args Extra command-line switches.
     * @param float $timeout Seconds to wait for the browser to start.
     *
     * @throws PdfException
     */
    public static function launch(
        ?string $executablePath = null,
        ?bool $noSandbox = null,
        array $args = [],
        float $timeout = 30.0,
    ): self {
        $exe = $executablePath ?? self::findChromeExecutable();
        if ($exe === null) {
            throw new PdfException(
                'LifeAstro PDF: no Chrome, Chromium or Edge found. Install one (Debian/Ubuntu: '
                . '`apt install chromium fonts-noto-core`), pass executablePath:, or set CHROME_PATH. '
                . 'Without a browser on the server, use GotenbergPdfEngine.'
            );
        }
        $noSandbox ??= \function_exists('posix_geteuid') && posix_geteuid() === 0;

        $dir = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'lifeastro-chrome-' . bin2hex(random_bytes(6));
        if (!@mkdir($dir, 0700, true)) {
            throw new PdfException("Cannot create a browser profile directory in {$dir}");
        }
        $log = $dir . '.log';
        $command = [
            $exe,
            '--headless=new',
            '--remote-debugging-port=0',
            '--user-data-dir=' . $dir,
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-background-networking',
            '--disable-extensions',
            '--disable-sync',
            '--disable-dev-shm-usage',
            '--hide-scrollbars',
            '--mute-audio',
        ];
        if ($noSandbox) {
            $command[] = '--no-sandbox';
        }
        $command = [...$command, ...$args, 'about:blank'];

        $null = \DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';
        // Output goes to a file, not a pipe: nothing here drains a pipe while the browser runs.
        $io = [0 => ['file', $null, 'r'], 1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']];
        $process = @proc_open($command, $io, $pipes);
        if (!\is_resource($process)) {
            self::removeDir($dir);
            throw new PdfException("Cannot start the browser: {$exe}");
        }

        $deadline = microtime(true) + $timeout;
        $url = null;
        while (true) {
            $text = (string) @file_get_contents($log);
            if (preg_match('#DevTools listening on (ws://\S+)#', $text, $m) === 1) {
                $url = $m[1];
                break;
            }
            $status = proc_get_status($process);
            if (!$status['running'] || microtime(true) > $deadline) {
                proc_terminate($process, 9);
                proc_close($process);
                self::removeDir($dir);
                @unlink($log);
                throw new PdfException(
                    ($status['running'] ? 'The browser did not start in time' : 'The browser exited')
                    . ': ' . substr($text, 0, 2000)
                );
            }
            usleep(50_000);
        }

        try {
            $ws = WebSocketClient::connect($url, max(1.0, $deadline - microtime(true)));
        } catch (PdfException $e) {
            proc_terminate($process, 9);
            proc_close($process);
            self::removeDir($dir);
            @unlink($log);
            throw $e;
        }

        return new self($ws, $process, $dir, $log);
    }

    /**
     * Use a browser that is already running with remote debugging, for example a Chrome container.
     * `$webSocketDebuggerUrl` is its `ws://.../devtools/browser/...` URL (from
     * `http://host:9222/json/version`). close() disconnects but leaves that browser running.
     *
     * @throws PdfException
     */
    public static function connect(string $webSocketDebuggerUrl, float $timeout = 10.0): self
    {
        return new self(WebSocketClient::connect($webSocketDebuggerUrl, $timeout));
    }

    /** The first Chrome, Chromium or Edge found on this machine, or null. */
    public static function findChromeExecutable(): ?string
    {
        foreach (['LIFEASTRO_CHROME', 'CHROME_PATH', 'PUPPETEER_EXECUTABLE_PATH'] as $name) {
            $value = getenv($name);
            if (\is_string($value) && $value !== '' && is_file($value)) {
                return $value;
            }
        }
        $candidates = [];
        if (\PHP_OS_FAMILY === 'Darwin') {
            $home = getenv('HOME');
            foreach (['/Applications', (\is_string($home) ? $home : '') . '/Applications'] as $root) {
                $candidates[] = "{$root}/Google Chrome.app/Contents/MacOS/Google Chrome";
                $candidates[] = "{$root}/Chromium.app/Contents/MacOS/Chromium";
                $candidates[] = "{$root}/Microsoft Edge.app/Contents/MacOS/Microsoft Edge";
                $candidates[] = "{$root}/Brave Browser.app/Contents/MacOS/Brave Browser";
            }
        } elseif (\PHP_OS_FAMILY === 'Windows') {
            foreach (['PROGRAMFILES', 'PROGRAMFILES(X86)', 'LOCALAPPDATA'] as $var) {
                $root = getenv($var);
                if (!\is_string($root) || $root === '') {
                    continue;
                }
                $candidates[] = "{$root}\\Google\\Chrome\\Application\\chrome.exe";
                $candidates[] = "{$root}\\Chromium\\Application\\chrome.exe";
                $candidates[] = "{$root}\\Microsoft\\Edge\\Application\\msedge.exe";
            }
        } else {
            $path = getenv('PATH');
            $names = ['google-chrome-stable', 'google-chrome', 'chromium', 'chromium-browser', 'microsoft-edge',
                'microsoft-edge-stable'];
            foreach (explode(':', \is_string($path) ? $path : '') as $dir) {
                if ($dir === '') {
                    continue;
                }
                foreach ($names as $name) {
                    $candidates[] = "{$dir}/{$name}";
                }
            }
            $candidates[] = '/usr/bin/chromium';
            $candidates[] = '/snap/bin/chromium';
        }
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function htmlToPdf(string $html, ?PdfOptions $options = null): string
    {
        $options ??= new PdfOptions();
        [$width, $height] = $options->paperInches();
        $margin = $options->marginInches();
        $params = [
            // Orientation is already in paperWidth / paperHeight.
            'landscape' => false,
            'printBackground' => $options->printBackground,
            'preferCSSPageSize' => $options->effectivePreferCssPageSize(),
            'paperWidth' => $width,
            'paperHeight' => $height,
            'marginTop' => $margin['top'],
            'marginBottom' => $margin['bottom'],
            'marginLeft' => $margin['left'],
            'marginRight' => $margin['right'],
            'scale' => $options->scale ?? 1.0,
            'displayHeaderFooter' => $options->displayHeaderFooter,
        ];
        if ($options->displayHeaderFooter) {
            $params['headerTemplate'] = $options->headerTemplate ?? '<span></span>';
            $params['footerTemplate'] = $options->footerTemplate ?? '<span></span>';
        }

        return $this->withPage($html, $options->timeout, function (string $session) use ($params, $options): string {
            $result = $this->send('Page.printToPDF', $params, $session, $options->timeout * 4);
            $data = $result['data'] ?? null;
            $pdf = \is_string($data) ? base64_decode($data, true) : false;
            if ($pdf === false) {
                throw new PdfException('The browser returned no PDF data');
            }

            return $pdf;
        });
    }

    /**
     * Run a JavaScript expression (it may return a promise) in a page showing `$html` and return
     * its JSON value.
     *
     * @throws PdfException
     */
    public function evaluate(string $html, string $expression, float $timeout = 30.0): mixed
    {
        return $this->withPage($html, $timeout, function (string $session) use ($expression, $timeout): mixed {
            $result = $this->send(
                'Runtime.evaluate',
                ['expression' => $expression, 'awaitPromise' => true, 'returnByValue' => true],
                $session,
                $timeout,
            );
            $value = $result['result'] ?? null;

            return \is_array($value) ? ($value['value'] ?? null) : null;
        });
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        if (\is_resource($this->process)) {
            try {
                $this->send('Browser.close', [], null, 5.0);
            } catch (PdfException) {
                // The browser is gone already, or did not answer: it is stopped below.
            }
        }
        $this->ws->close();
        if (\is_resource($this->process)) {
            $deadline = microtime(true) + 5.0;
            while (proc_get_status($this->process)['running'] && microtime(true) < $deadline) {
                usleep(50_000);
            }
            if (proc_get_status($this->process)['running']) {
                proc_terminate($this->process, 9);
            }
            proc_close($this->process);
            $this->process = null;
        }
        if ($this->userDataDir !== null) {
            self::removeDir($this->userDataDir);
        }
        if ($this->logFile !== null) {
            @unlink($this->logFile);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Open `$html` in a new tab, run `$body` with the tab's session id, and close the tab.
     *
     * @template T
     *
     * @param \Closure(string): T $body
     *
     * @return T
     */
    private function withPage(string $html, float $timeout, \Closure $body): mixed
    {
        if ($this->closed) {
            throw new PdfException('ChromePdfEngine is closed');
        }
        $target = $this->send('Target.createTarget', ['url' => 'about:blank'], null, $timeout);
        $targetId = $target['targetId'] ?? null;
        if (!\is_string($targetId)) {
            throw new PdfException('The browser did not open a tab');
        }
        try {
            $attach = $this->send(
                'Target.attachToTarget',
                ['targetId' => $targetId, 'flatten' => true],
                null,
                $timeout,
            );
            $session = $attach['sessionId'] ?? null;
            if (!\is_string($session)) {
                throw new PdfException('Could not attach to the browser tab');
            }
            $this->send('Page.enable', [], $session, $timeout);
            $tree = $this->send('Page.getFrameTree', [], $session, $timeout);
            $frameTree = $tree['frameTree'] ?? null;
            $frame = \is_array($frameTree) ? ($frameTree['frame'] ?? null) : null;
            $frameId = \is_array($frame) ? ($frame['id'] ?? null) : null;
            if (!\is_string($frameId)) {
                throw new PdfException('Could not read the tab\'s frame');
            }
            $this->send('Page.setDocumentContent', ['frameId' => $frameId, 'html' => $html], $session, $timeout);
            $this->send(
                'Runtime.evaluate',
                ['expression' => self::WAIT_FOR_LOAD, 'awaitPromise' => true, 'returnByValue' => true],
                $session,
                $timeout,
            );

            return $body($session);
        } finally {
            if (!$this->closed) {
                try {
                    $this->send('Target.closeTarget', ['targetId' => $targetId], null, 10.0);
                } catch (PdfException) {
                    // The tab is gone with the browser.
                }
            }
        }
    }

    /**
     * Send one DevTools command and wait for its result (events and late answers are skipped).
     *
     * @param array<string, mixed> $params
     *
     * @return array<mixed>
     *
     * @throws PdfException
     */
    private function send(string $method, array $params, ?string $session, float $timeout): array
    {
        $id = ++$this->nextId;
        $message = ['id' => $id, 'method' => $method, 'params' => $params === [] ? new \stdClass() : $params];
        if ($session !== null) {
            $message['sessionId'] = $session;
        }
        try {
            $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;
            $this->ws->send(json_encode($message, $flags));
        } catch (\JsonException $e) {
            throw new PdfException('Cannot encode the DevTools command: ' . $e->getMessage(), 0, $e);
        }
        $deadline = microtime(true) + $timeout;
        while (true) {
            $raw = $this->ws->receive($deadline);
            $reply = json_decode($raw, true);
            if (!\is_array($reply) || ($reply['id'] ?? null) !== $id) {
                continue;
            }
            $error = $reply['error'] ?? null;
            if (\is_array($error)) {
                $text = $error['message'] ?? 'unknown error';
                throw new PdfException("DevTools {$method}: " . (\is_string($text) ? $text : 'unknown error'));
            }
            $result = $reply['result'] ?? [];

            return \is_array($result) ? $result : [];
        }
    }

    private static function removeDir(string $dir): void
    {
        for ($attempt = 0; $attempt < 5 && is_dir($dir); $attempt++) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($items as $item) {
                if (!$item instanceof \SplFileInfo) {
                    continue;
                }
                $item->isDir() && !$item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            }
            @rmdir($dir);
            if (is_dir($dir)) {
                usleep(200_000);
            }
        }
    }
}
