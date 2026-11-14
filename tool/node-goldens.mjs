// Records the request the Node.js SDK sends for every method, with the sample inputs of
// tool/samples.json, into tests/fixtures/node-requests.json. tests/NodeParityTest.php then checks
// that the PHP SDK sends the same path and query string, method by method: the wire names,
// prefixes, `lang` / `locale`, defaults, booleans and number formatting.
//
//   node tool/node-goldens.mjs          # or: composer goldens
//
// Needs the Node SDK built (`npm run build` in ../lifeastroapi-node; the dist/ folder). Nothing is
// sent over the network: the Node client gets a fetch that only records the URL.
//
// Environment:
//   LIFEASTRO_NODE_SDK   path to the Node SDK repo (default ../lifeastroapi-node)
import { existsSync, readFileSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const NODE_SDK = resolve(process.env.LIFEASTRO_NODE_SDK ?? join(ROOT, "..", "lifeastroapi-node"));
const dist = join(NODE_SDK, "dist", "index.js");
if (!existsSync(dist)) {
  console.error(`Node SDK build not found at ${dist}. Run \`npm run build\` in ${NODE_SDK}.`);
  process.exit(2);
}
const { LifeAstro, VERSION } = await import(pathToFileURL(dist).href);
const manifest = JSON.parse(readFileSync(join(ROOT, "tool", "manifest.json"), "utf8"));
const samples = JSON.parse(readFileSync(join(ROOT, "tool", "samples.json"), "utf8"));

/** The same inputs as LifeAstroApi\Tool\Samples::arguments(), keyed by Node field name. */
function inputFor(entry) {
  const input = structuredClone(samples.shapes[entry.shape] ?? {});
  for (const p of entry.params) {
    if (p.kind === "path" && p.name in samples.path) input[p.name] = samples.path[p.name];
  }
  for (const x of entry.extras) {
    if (x.required && x.wire in samples.wire) input[x.field] = samples.wire[x.wire];
  }
  Object.assign(input, samples.perPath[entry.path] ?? {});
  const names = new Set(entry.params.map((p) => p.name));
  return Object.fromEntries(Object.entries(input).filter(([k]) => names.has(k)));
}

let last = null;
const fetch = async (url) => {
  last = url;
  return new Response("{}", { status: 200, headers: { "content-type": "application/json" } });
};
const BASE = "https://sdk.test";
const client = new LifeAstro({ apiKey: "dv_live_test", baseUrl: BASE, fetch, maxRetries: 0 });

function methodOf(key) {
  const parts = key.split(".");
  const name = parts.pop();
  let target = client;
  for (const part of parts) target = target[part];
  return target[name].bind(target);
}

const out = {};
for (const entry of manifest) {
  const call = methodOf(entry.key);
  const input = inputFor(entry);
  last = null;
  await call(input);
  const record = { url: last.slice(BASE.length) };
  if (entry.params.some((p) => p.name === "lang")) {
    last = null;
    await call({ ...input, lang: "bn" });
    record.lang_url = last.slice(BASE.length);
  }
  out[entry.key] = record;
}

const file = join(ROOT, "tests", "fixtures", "node-requests.json");
writeFileSync(
  file,
  JSON.stringify({ node_sdk_version: VERSION, methods: out }, null, 1) + "\n",
);
console.log(`Wrote tests/fixtures/node-requests.json: ${Object.keys(out).length} methods (Node SDK ${VERSION}).`);
