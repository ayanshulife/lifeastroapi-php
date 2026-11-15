// Golden files for the PHP port of the PDF report templates.
//
// The PHP renderers (src/Pdf/, `Reports::render*Html`) are a port of the Node SDK's
// src/pdf/. This script renders the same inputs with the Node TypeScript
// sources and writes the expected HTML, so tests/Pdf/PdfGoldenTest.php can check
// that the PHP output is byte-for-byte identical. It is the same tool as the Dart SDK's
// tool/pdf_goldens.mjs, writing to tests/fixtures/pdf/ instead.
//
// Inputs:
//   1. every render*Html call made by the Node SDK's own PDF tests
//      (test/pdf.test.ts, test/pdf-south-locales.test.ts), recorded while the
//      tests run against a stubbed `vitest`;
//   2. the real report responses in the website's examples.json, rendered in
//      all 10 locales, with and without a branding override.
//
//   node tool/pdf-goldens.mjs          (or: composer pdf-goldens)
//
// Environment: LIFEASTRO_NODE_SDK, LIFEASTRO_EXAMPLES (as tool/generate.mjs).
// Needs the Node SDK's dev dependencies (esbuild): `npm install` in that repo.
import { createRequire } from "node:module";
import { existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const NODE_SDK = resolve(process.env.LIFEASTRO_NODE_SDK ?? join(ROOT, "..", "lifeastroapi-node"));
const EXAMPLES = resolve(
  process.env.LIFEASTRO_EXAMPLES ??
    join(ROOT, "..", "LifeAstroAPI", "LifeAstroApiWebsite", "lib", "generated", "examples.json"),
);
const OUT = join(ROOT, "tests", "fixtures", "pdf");
const TMP = join(ROOT, "build", "pdf-goldens");

const require = createRequire(join(NODE_SDK, "package.json"));
const esbuild = require("esbuild");
const PDF_INDEX = join(NODE_SDK, "src", "pdf", "index.ts");

const RENDERERS = [
  "renderKundliLiteHtml",
  "renderKundliDetailedHtml",
  "renderKundliBrihadHtml",
  "renderMatchMakingHtml",
  "renderDashaAnalysisHtml",
  "renderNumerologyHtml",
  "renderSadeSatiHtml",
  "renderVarshaphalHtml",
  "renderHoroscopeHtml",
  "renderMangalDoshaHtml",
];

// A bundle that exposes the real templates plus a recorder around them.
const recorderSrc = `
import * as real from ${JSON.stringify(PDF_INDEX)};
export * from ${JSON.stringify(PDF_INDEX)};
globalThis.__pdfCalls = globalThis.__pdfCalls ?? [];
const rec = (name, fn) => (...args) => {
  globalThis.__pdfCalls.push({ fn: name, args: JSON.parse(JSON.stringify(args.map((a) => a ?? null))) });
  return fn(...args);
};
${RENDERERS.map((n) => `export const ${n} = rec(${JSON.stringify(n)}, real.${n});`).join("\n")}
`;

const stubVitest = `
const chain = new Proxy(function () {}, { get: () => chain, apply: () => chain });
const run = (fn) => { try { const r = fn?.(); if (r && typeof r.catch === "function") r.catch(() => {}); } catch {} };
export const describe = (_n, fn) => run(fn);
export const it = (_n, fn) => run(fn);
it.each = () => (_n, fn) => {};
export const test = it;
export const expect = () => chain;
export const vi = chain;
export const beforeAll = () => {}, afterAll = () => {}, beforeEach = () => {}, afterEach = () => {};
`;

const plugin = {
  name: "pdf-goldens",
  setup(build) {
    build.onResolve({ filter: /^vitest$/ }, () => ({ path: "vitest", namespace: "stub" }));
    build.onLoad({ filter: /.*/, namespace: "stub" }, () => ({ contents: stubVitest, loader: "js" }));
    build.onResolve({ filter: /src\/pdf\/index\.js$/ }, () => ({ path: "recorder", namespace: "rec" }));
    build.onLoad({ filter: /.*/, namespace: "rec" }, () => ({
      contents: recorderSrc,
      loader: "js",
      resolveDir: join(NODE_SDK, "src", "pdf"),
    }));
  },
};

rmSync(TMP, { recursive: true, force: true });
mkdirSync(TMP, { recursive: true });
// The Node tests' htmlToPdf cases reject without Puppeteer; only the render
// calls matter here.
process.on("unhandledRejection", () => {});

async function bundle(entry, outfile) {
  await esbuild.build({
    entryPoints: [entry],
    bundle: true,
    format: "esm",
    platform: "node",
    outfile,
    plugins: [plugin],
    logLevel: "error",
  });
  return import(pathToFileURL(outfile).href + `?t=${Date.now()}`);
}

// 1. Record the Node tests' calls.
for (const t of ["pdf.test.ts", "pdf-south-locales.test.ts"]) {
  await bundle(join(NODE_SDK, "test", t), join(TMP, t.replace(/\.ts$/, ".mjs")));
}
await new Promise((r) => setTimeout(r, 50)); // let async test bodies settle
const recorded = globalThis.__pdfCalls ?? [];

// The real templates (and the Brihad TOC helpers), bundled from the TS source.
const helpersEntry = join(TMP, "helpers.ts");
writeFileSync(
  helpersEntry,
  `export * from ${JSON.stringify(PDF_INDEX)};
export { brihadHeaderTemplate, brihadFooterTemplate, buildBrihadToc, tagBrihadSectionsForToc } from ${JSON.stringify(
    join(NODE_SDK, "src", "pdf", "templates", "kundliBrihadSections.ts"),
  )};
export { TOC_HEADING } from ${JSON.stringify(join(NODE_SDK, "src", "pdf", "locales.ts"))};
`,
);
const pdf = await bundle(helpersEntry, join(TMP, "helpers.mjs"));

// 2. Real report responses in every locale.
const LOCALES = ["en", "hi", "mr", "te", "ta", "kn", "ml", "gu", "bn", "pa"];
const BRANDING = {
  companyName: "Acme Astro",
  logoUrl: "https://cdn.example/acme/logo.png",
  primaryColor: "#6b21a8",
  secondaryColor: "#9333ea",
  footerText: "Acme Astro · confidential",
  watermarkText: "SAMPLE",
};
const REPORTS = {
  "/v1/reports/kundli/lite": "renderKundliLiteHtml",
  "/v1/reports/kundli/detailed": "renderKundliDetailedHtml",
  "/v1/reports/kundli/brihad": "renderKundliBrihadHtml",
  "/v1/reports/match-making": "renderMatchMakingHtml",
  "/v1/reports/dasha-analysis": "renderDashaAnalysisHtml",
  "/v1/reports/numerology": "renderNumerologyHtml",
  "/v1/reports/sade-sati": "renderSadeSatiHtml",
  "/v1/reports/varshaphal": "renderVarshaphalHtml",
  "/v1/reports/mangal-dosha": "renderMangalDoshaHtml",
};
const cases = [];
const seen = new Set();
function addCase(fn, args) {
  const key = fn + JSON.stringify(args);
  if (seen.has(key)) return;
  seen.add(key);
  cases.push({ fn, args });
}
for (const c of recorded) addCase(c.fn, c.args.length === 1 ? [c.args[0], null] : c.args);
// Horoscope reports have no example response (the examples run without a
// live corpus); render the Node test inputs in every locale instead.
for (const c of recorded.filter((r) => r.fn === "renderHoroscopeHtml")) {
  for (const loc of ["en", "hi", "mr", "te", "ta", "kn", "ml", "gu", "bn", "pa"]) {
    addCase(c.fn, [{ ...c.args[0], language: loc }, c.args[1] ?? null]);
  }
}
if (existsSync(EXAMPLES)) {
  const ex = JSON.parse(readFileSync(EXAMPLES, "utf8"));
  for (const [path, fn] of Object.entries(REPORTS)) {
    const resp = ex[path]?.response;
    if (!resp || typeof resp !== "object") continue;
    for (const loc of LOCALES) addCase(fn, [{ ...resp, Locale: loc }, null]);
    addCase(fn, [resp, BRANDING]);
    addCase(fn, [{ ...resp, Locale: "hi" }, BRANDING]);
  }
} else {
  console.warn(`examples.json not found at ${EXAMPLES}; only the Node test inputs are used.`);
}

// Render.
rmSync(OUT, { recursive: true, force: true });
mkdirSync(join(OUT, "golden"), { recursive: true });
const counters = {};
const index = [];
for (const c of cases) {
  const short = c.fn.replace(/^render/, "").replace(/Html$/, "");
  const name = short[0].toLowerCase() + short.slice(1);
  counters[name] = (counters[name] ?? 0) + 1;
  const id = `${name}-${String(counters[name]).padStart(3, "0")}`;
  const args = JSON.parse(JSON.stringify(c.args));
  const html = pdf[c.fn](args[0], args[1] ?? undefined);
  writeFileSync(join(OUT, "golden", `${id}.html`), html);
  index.push({ id, fn: c.fn, data: c.args[0], branding: c.args[1] });
}
writeFileSync(join(OUT, "cases.json"), JSON.stringify(index, null, 1) + "\n");

// Text helpers: every string in the inputs, through humanize / cleanNarrative / esc.
const strings = new Set();
const walk = (v) => {
  if (typeof v === "string") strings.add(v);
  else if (Array.isArray(v)) v.forEach(walk);
  else if (v && typeof v === "object") Object.values(v).forEach(walk);
};
for (const c of cases) walk(c.args);
for (const s of [
  "A — B", "x – y", "a -> b => c", "—", " – ", "word —", "— word", "a , , b", "(, x ,)",
  "In Vedic astrology the chart is read on three levels: birth, dasha and transit. It is about guidance.",
  "the native's career; The native is calm; native's luck; native",
  "जातक की कुंडली में जातक को लाभ। जातक",
  "जातकाच्या जीवनात जातकाला यश जातक",
]) strings.add(s);
const text = [];
for (const s of [...strings].sort()) {
  const row = { s, humanize: pdf.humanize(s), esc: pdf.esc(s), clean: {} };
  for (const loc of ["en", "hi", "mr", "te", "gu"]) row.clean[loc] = pdf.cleanNarrative(s, loc);
  text.push(row);
}
writeFileSync(join(OUT, "text.json"), JSON.stringify(text, null, 1) + "\n");

// Brihad running header/footer, TOC, and section tagging.
const brihad = {
  header: [pdf.brihadHeaderTemplate(undefined), pdf.brihadHeaderTemplate({ ...BRANDING })],
  footer: [pdf.brihadFooterTemplate(undefined), pdf.brihadFooterTemplate({ ...BRANDING })],
  toc: LOCALES.filter((l) => pdf.TOC_HEADING[l]).map((l) => ({
    heading: pdf.TOC_HEADING[l],
    entries: [
      { title: "Part 1: Birth Details", page: 3 },
      { title: "Planets & <Houses>", page: 7 },
      { title: "भाग 2: दशा", page: 12 },
    ],
    html: pdf.buildBrihadToc(
      [
        { title: "Part 1: Birth Details", page: 3 },
        { title: "Planets & <Houses>", page: 7 },
        { title: "भाग 2: दशा", page: 12 },
      ],
      pdf.TOC_HEADING[l],
    ),
  })),
  tagged: index
    .filter((c) => c.fn === "renderKundliBrihadHtml")
    .map((c) => {
      const { html, titles } = pdf.tagBrihadSectionsForToc(readFileSync(join(OUT, "golden", `${c.id}.html`), "utf8"));
      return { id: c.id, titles, html };
    }),
};
writeFileSync(join(OUT, "brihad.json"), JSON.stringify(brihad, null, 1) + "\n");

const byFn = {};
for (const c of index) byFn[c.fn] = (byFn[c.fn] ?? 0) + 1;
console.log(`${index.length} golden HTML files (${recorded.length} calls recorded from the Node tests).`);
console.log(byFn);
console.log(`${text.length} text-helper rows.`);
