// Port helper: prints top-level constant tables of a Node SDK TypeScript file
// as PHP array literals, with the data evaluated by Node (so every string,
// number and key is copied exactly). Used to port the PDF templates' label
// tables into src/Pdf/.
//
//   node tool/ts-tables.mjs <file under lifeastroapi-node/src/pdf> NAME[:PHP_NAME] ...
//
// Example:
//   node tool/ts-tables.mjs templates/kundliLite.ts LABELS
//
// Objects become arrays with string keys (numeric-looking keys become int keys,
// which is what PHP does with them anyway), arrays become lists, strings are
// single-quoted with `\` and `'` escaped. A RegExp prints as a preg pattern
// string ('/.../u').
import { createRequire } from "node:module";
import { mkdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const NODE_SDK = resolve(process.env.LIFEASTRO_NODE_SDK ?? join(ROOT, "..", "lifeastroapi-node"));
const require = createRequire(join(NODE_SDK, "package.json"));
const esbuild = require("esbuild");

const [file, ...names] = process.argv.slice(2);
if (!file || names.length === 0) {
  console.error("usage: node tool/ts-tables.mjs <file> NAME[:PHP_NAME] ...");
  process.exit(2);
}
const src = join(NODE_SDK, "src", "pdf", file);
const pairs = names.map((n) => {
  const [ts, php] = n.split(":");
  return { ts, php: php ?? ts };
});

const tmpDir = join(ROOT, "build", "ts-tables");
mkdirSync(tmpDir, { recursive: true });
const entry = join(dirname(src), `.__tables_${process.pid}.ts`);
writeFileSync(entry, readFileSync(src, "utf8") + `\nexport const __tables = { ${pairs.map((p) => p.ts).join(", ")} };\n`);
const out = join(tmpDir, `t${process.pid}.mjs`);
try {
  await esbuild.build({ entryPoints: [entry], bundle: true, format: "esm", platform: "node", outfile: out, logLevel: "error" });
} finally {
  rmSync(entry, { force: true });
}
const mod = await import(pathToFileURL(out).href);

const str = (s) => "'" + s.replace(/\\/g, "\\\\").replace(/'/g, "\\'") + "'";
function lit(v, indent = "    ") {
  const next = indent + "    ";
  if (v === null || v === undefined) return "null";
  if (typeof v === "string") return str(v);
  if (typeof v === "number") return Number.isInteger(v) ? String(v) : String(v);
  if (typeof v === "boolean") return v ? "true" : "false";
  if (v instanceof RegExp) return str(`/${v.source.replace(/\//g, "\\/")}/u${v.flags.includes("i") ? "i" : ""}`);
  if (Array.isArray(v)) {
    if (v.length === 0) return "[]";
    return "[\n" + v.map((x) => next + lit(x, next) + ",").join("\n") + "\n" + indent + "]";
  }
  const keys = Object.keys(v);
  if (keys.length === 0) return "[]";
  return "[\n" + keys.map((k) => `${next}${/^(0|[1-9]\d*)$/.test(k) ? k : str(k)} => ${lit(v[k], next)},`).join("\n") + "\n" + indent + "]";
}
for (const p of pairs) console.log(`    private const ${p.php} = ${lit(mod.__tables[p.ts])};\n`);
