// Code generator for the PHP SDK.
//
// Reads the endpoint manifest of the Node.js SDK (scripts/endpoints_part1.json +
// endpoints_part2.json, resolved by scripts/manifest.mjs: per-path overrides, the `lang`
// rules, namespace and method names) and writes:
//
//   src/Resources/*.php          one final class per resource group (the public methods)
//   src/Resources/Resources.php  the base class of LifeAstro with every group as a property
//   tool/manifest.json           the resolved manifest with the PHP parameters (tests, live sweep)
//   tests/fixtures/examples.json request params and parameter docs of every endpoint, from the
//                                website's examples.json when that file is found
//
// Importing manifest.mjs, rather than copying its rules, keeps the Node, Python, Dart and PHP
// SDKs on the same endpoint list, method names and `lang` rules.
//
//   node tool/generate.mjs                                   # or: composer generate
//   node tool/generate.mjs --costs-url http://127.0.0.1:8080  # refresh tool/costs.json first
//
// Credit costs in the PHPDoc come from tool/costs.json, a snapshot of GET /v1/meta/endpoints
// (free endpoint; the key, if needed, comes from LIFEASTRO_API_KEY).
//
// Environment:
//   LIFEASTRO_NODE_SDK   path to the Node SDK repo (default ../lifeastroapi-node)
//   LIFEASTRO_EXAMPLES   path to the website's examples.json (default
//                        ../LifeAstroAPI/LifeAstroApiWebsite/lib/generated/examples.json)
import { existsSync, mkdirSync, readFileSync, readdirSync, rmSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const NODE_SDK = resolve(process.env.LIFEASTRO_NODE_SDK ?? join(ROOT, "..", "lifeastroapi-node"));
const EXAMPLES = resolve(
  process.env.LIFEASTRO_EXAMPLES ??
    join(ROOT, "..", "LifeAstroAPI", "LifeAstroApiWebsite", "lib", "generated", "examples.json"),
);
const OUT = join(ROOT, "src", "Resources");
const COSTS = join(ROOT, "tool", "costs.json");
const MANIFEST_OUT = join(ROOT, "tool", "manifest.json");
const FIXTURE = join(ROOT, "tests", "fixtures", "examples.json");

const manifestPath = join(NODE_SDK, "scripts", "manifest.mjs");
if (!existsSync(manifestPath)) {
  console.error(`Node SDK manifest not found at ${manifestPath}. Set LIFEASTRO_NODE_SDK.`);
  process.exit(2);
}
const { entries } = await import(pathToFileURL(manifestPath).href);

// ---------------------------------------------------------------------------
// Credit costs (snapshot of GET /v1/meta/endpoints)
// ---------------------------------------------------------------------------
const argv = process.argv.slice(2);
const costsUrlAt = argv.indexOf("--costs-url");
if (costsUrlAt !== -1) {
  const base = String(argv[costsUrlAt + 1] ?? "").replace(/\/+$/, "");
  if (!base) {
    console.error("--costs-url needs a base URL, e.g. http://127.0.0.1:8080");
    process.exit(2);
  }
  const headers = { Accept: "application/json" };
  if (process.env.LIFEASTRO_API_KEY) headers.Authorization = `Bearer ${process.env.LIFEASTRO_API_KEY}`;
  const res = await fetch(`${base}/v1/meta/endpoints`, { headers });
  if (!res.ok) {
    console.error(`GET ${base}/v1/meta/endpoints answered ${res.status}`);
    process.exit(1);
  }
  const body = await res.json();
  const routes = {};
  for (const r of body.routes ?? []) {
    if ((r.method ?? "GET") === "GET" && String(r.path).startsWith("/v1/")) routes[r.path] = r.cost;
  }
  const sorted = Object.fromEntries(Object.entries(routes).sort(([a], [b]) => (a < b ? -1 : a > b ? 1 : 0)));
  const snapshot = {
    source: `GET ${base}/v1/meta/endpoints`,
    fetched: new Date().toISOString().slice(0, 10),
    routes: sorted,
  };
  writeFileSync(COSTS, JSON.stringify(snapshot, null, 1) + "\n");
  console.log(`Wrote tool/costs.json: ${Object.keys(sorted).length} routes from ${snapshot.source}`);
}
const COST_ROUTES = existsSync(COSTS) ? JSON.parse(readFileSync(COSTS, "utf8")).routes : {};

const norm = (path) => path.replace(/\{[^}]+\}/g, "{}");
const templateRegex = (path) =>
  new RegExp("^" + path.split(/\{[^}]+\}/).map((p) => p.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")).join("[^/]+") + "$");

function costFor(path) {
  if (path in COST_ROUTES) return COST_ROUTES[path];
  // Same template with other placeholder names, or a literal path (D9) of a template.
  const matches = Object.entries(COST_ROUTES)
    .filter(([route]) => norm(route) === norm(path) || templateRegex(route).test(path))
    .map(([route, cost]) => [(route.match(/\{/g) ?? []).length, cost])
    .sort((a, b) => a[0] - b[0] || a[1] - b[1]);
  return matches.length ? matches[0][1] : null;
}

// ---------------------------------------------------------------------------
// Parameter docs (website examples.json, or the committed fixture)
// ---------------------------------------------------------------------------
let examples = null;
if (existsSync(EXAMPLES)) {
  const raw = JSON.parse(readFileSync(EXAMPLES, "utf8"));
  examples = {};
  for (const path of Object.keys(raw).sort()) {
    const v = raw[path];
    const docs = {};
    for (const d of v.params_doc ?? []) {
      if (d.description) docs[d.name] = String(d.description).replace(/\s+/g, " ").trim();
    }
    examples[path] = { status: v.status ?? null, required: v.required ?? [], params: v.params ?? {}, docs };
  }
  mkdirSync(dirname(FIXTURE), { recursive: true });
  writeFileSync(FIXTURE, JSON.stringify(examples, null, 1) + "\n");
} else if (existsSync(FIXTURE)) {
  console.log(`examples.json not found at ${EXAMPLES}; using tests/fixtures/examples.json`);
  examples = JSON.parse(readFileSync(FIXTURE, "utf8"));
} else {
  console.log(`examples.json not found at ${EXAMPLES}; parameter docs are left out.`);
  examples = {};
}
const examplesByTemplate = Object.fromEntries(Object.entries(examples).map(([k, v]) => [norm(k), v]));
function exampleFor(path) {
  if (examples[path]) return examples[path];
  if (examplesByTemplate[norm(path)]) return examplesByTemplate[norm(path)];
  for (const [template, ex] of Object.entries(examples)) {
    if (template.includes("{") && templateRegex(template).test(path)) return ex;
  }
  return null;
}

// ---------------------------------------------------------------------------
// PHP parameter model. A scalar has `wires` (query names); an object (a birth array such as
// `boy`) has a `prefix` and is sent as prefix + date / time / tz / lat / lon.
// ---------------------------------------------------------------------------
const RESERVED = new Set(["query", "requestOptions", "this"]);
const BIRTH_SHAPE =
  "array{date: string, time: string, tz: string, lat: float|int, lon: float|int}";
const WINDOW_BIRTH_SHAPE =
  "array{date: string, time: string, tz: string, lat?: float|int, lon?: float|int}";

const scalar = (name, type, required, wire, more = {}) => ({
  name,
  type,
  required,
  kind: "scalar",
  wires: [wire ?? name],
  ...more,
});
const obj = (name, shape, required, prefix, more = {}) => ({
  name,
  type: "array",
  shape,
  required,
  kind: "object",
  prefix,
  place: false,
  ...more,
});
const AYANAMSA = () => scalar("ayanamsa", "Ayanamsa", false);
const HOUSE = () => scalar("houseSystem", "HouseSystem", false, "house_system");
const flatBirth = (prefix = "") => [
  scalar("lat", "float", true, prefix + "lat"),
  scalar("lon", "float", true, prefix + "lon"),
  scalar("date", "string", true, prefix + "date"),
  scalar("time", "string", true, prefix + "time"),
  scalar("tz", "string", true, prefix + "tz"),
];

// Base shapes, in the order of the Node SDK's src/params.ts builders (q* functions). `expr` is
// the PHP value expression where it differs from the plain variable (defaults as in params.ts).
const SHAPES = {
  moment: () => [
    scalar("lat", "float", true),
    scalar("lon", "float", true),
    scalar("tz", "string", true),
    scalar("date", "string", false, "date", { expr: "$date ?? Query::todayIn($tz)", doc: "date_today" }),
    scalar("time", "string", false),
    AYANAMSA(),
  ],
  birth: () => [...flatBirth(), AYANAMSA(), HOUSE()],
  birthPrefixed: () => [...flatBirth("birth."), AYANAMSA()],
  boygirl: () => [
    obj("boy", BIRTH_SHAPE, true, "boy."),
    obj("girl", BIRTH_SHAPE, true, "girl."),
    AYANAMSA(),
  ],
  twoPerson: () => [obj("personA", BIRTH_SHAPE, true, "personA."), obj("personB", BIRTH_SHAPE, true, "personB.")],
  rashi: () => [
    scalar("rashi", "string", true, "rashi", { expr: "strtolower(trim($rashi))" }),
    scalar("date", "string", false),
    scalar("lang", "Lang", false),
  ],
  numerology: () => [scalar("name", "string", false), scalar("dob", "string", false), scalar("lang", "Lang", false)],
  dateRange: () => [scalar("startDate", "string", true, "start_date"), scalar("endDate", "string", true, "end_date")],
  westernNatal: () => [...flatBirth(), HOUSE()],
  westernMoment: () => [scalar("date", "string", true), scalar("time", "string", true), scalar("tz", "string", true)],
  muhurta: () => [
    scalar("lat", "float", true),
    scalar("lon", "float", true),
    scalar("tz", "string", true),
    scalar("startDate", "string", true, "start_date"),
    scalar("endDate", "string", true, "end_date"),
  ],
  natalTransit: () => [
    obj("birth", BIRTH_SHAPE, true, "birth."),
    scalar("date", "string", true),
    scalar("time", "string", true),
    scalar("tz", "string", true),
    AYANAMSA(),
  ],
  // Also sends the birth place unprefixed: the handler casts the natal lagna from it.
  natalTransitPlace: () => [
    obj("birth", BIRTH_SHAPE, true, "birth.", { place: true }),
    scalar("date", "string", true),
    scalar("time", "string", true),
    scalar("tz", "string", true),
    AYANAMSA(),
  ],
  natalWindow: () => [
    obj("birth", WINDOW_BIRTH_SHAPE, true, "birth."),
    scalar("from", "string", false),
    scalar("tz", "string", false),
    AYANAMSA(),
  ],
  natalWindowOptional: () => [
    obj("birth", WINDOW_BIRTH_SHAPE, false, "birth."),
    scalar("from", "string", false),
    scalar("tz", "string", false),
    AYANAMSA(),
  ],
  instant: () => [
    scalar("tz", "string", false, "tz", { expr: "$tz ?? 'UTC'", doc: "tz_utc" }),
    scalar("date", "string", false, "date", { expr: "$date ?? Query::todayIn($tz ?? 'UTC')", doc: "date_today" }),
    scalar("time", "string", false),
    AYANAMSA(),
  ],
  none: () => [],
  custom: () => [],
};

function extraType(x, name) {
  if (name === "lang" || name === "locale" || x.type === "locale") return "Lang";
  if (name === "ayanamsa") return "Ayanamsa";
  if (name === "houseSystem") return "HouseSystem";
  return { number: "float", integer: "int", boolean: "bool" }[x.type] ?? "string";
}

function resolveEntry(e) {
  const { ns, method } = e._resolved;
  const shapeFn = SHAPES[e.shape];
  if (!shapeFn) throw new Error(`Unknown shape "${e.shape}" for ${e.path}`);
  const params = [];
  const byName = new Map();
  const ops = []; // [paramName, wire, expr]

  const add = (p) => {
    if (!/^[A-Za-z_][A-Za-z0-9_]*$/.test(p.name) || RESERVED.has(p.name)) {
      throw new Error(`${e.path}: parameter name "${p.name}" cannot be used in PHP here`);
    }
    const existing = byName.get(p.name);
    if (!existing) {
      params.push(p);
      byName.set(p.name, p);
      return p;
    }
    if (existing.kind === "object" || p.kind === "object") {
      throw new Error(`${e.path}: "${p.name}" is both a scalar and an object`);
    }
    existing.required = existing.required || p.required;
    return existing;
  };

  for (const n of e.pathParams ?? []) {
    add({ name: n, type: n === "n" || n === "number" ? "int|string" : "string", required: true, kind: "path", wires: [] });
  }
  for (const p of shapeFn()) {
    const target = add(p);
    if (p.kind === "object") continue;
    for (const w of p.wires) ops.push([target.name, w, p.expr ?? "$" + target.name]);
  }
  for (const x of e.extras ?? []) {
    const name = x.name ?? camelOf(x.wire);
    const target = add(scalar(name, extraType(x, name), !!x.required, x.wire));
    // `lang` is sent as both `lang` and `locale` (the API reads either; some older routes read
    // only one), exactly as the Node SDK does.
    const wires = x.wire === "lang" ? ["lang", "locale"] : [x.wire];
    for (const w of wires) {
      ops.push([target.name, w, "$" + target.name]);
      if (!target.wires.includes(w)) target.wires.push(w);
    }
  }
  return { entry: e, ns, method, params, ops, cost: costFor(e.path) };
}

function camelOf(s) {
  const words = String(s).split(/[^a-zA-Z0-9]+/).filter(Boolean);
  return words.map((w, i) => (i === 0 ? w[0].toLowerCase() + w.slice(1) : w[0].toUpperCase() + w.slice(1))).join("");
}
const pascal = (s) => {
  const c = camelOf(s);
  return c[0].toUpperCase() + c.slice(1);
};

// ---------------------------------------------------------------------------
// PHP source emission
// ---------------------------------------------------------------------------
const NS_DOC = {
  ashtakavarga: "Ashtakavarga: sarva, bhinna (per planet), kaksha and transit score.",
  calendar: "Hindu calendar: month, ritu, samvatsara, adhik maas, solstices.",
  chart:
    "Birth chart (kundli): planets, ascendant, houses, divisional charts, dignity, aspects, shadbala, KP, " +
    "and the data bundles `kundliFacts`, `yogas`, `doshas`, `sensitivity`.",
  dasha: "Vimshottari (5 levels), Yogini and Chara dashas.",
  eclipses: "Solar and lunar eclipses in a date range, with local contact times and sutak.",
  festivals: "Hindu festivals and vrat dates by month, on a date, or by vrat family for a year.",
  geo: "Place search, reverse geocoding, time zone lookup.",
  horoscope: "Daily, weekly and monthly horoscope by rashi.",
  lalkitab: "Lal Kitab chart and dasha.",
  meta: "Free catalogs: endpoints with credit costs, ayanamsas, yogas, doshas, locales, version.",
  milan: "Kundli matching: Ashtakoota, doshas, navamsa and dasha compatibility, overlay, summary.",
  muhurta: "Muhurta: vivah, naamkaran, griha pravesh, vyapar, yatra, best time, chandra/tara bala.",
  narrative:
    "Authored Vedic readings: lagna, planets, yogas, doshas, dasha phal, transit phal, prashna, horoscopes " +
    "and more.",
  numerology:
    "Numerology: driver, conductor, soul, personality, destiny, full, advanced, challenges, personal periods.",
  panchang:
    "Panchang: tithi, nakshatra, yoga, karana, vara, sunrise/sunset, muhurta windows, choghadiya, hora, " +
    "monthly calendar.",
  planetMoments: "Planet moments: retrograde windows, sign ingress, combustion window, speed.",
  prashna: "Prashna (horary): answer, chart, arudha, significators.",
  remedies:
    "Remedies: by rule id, by graha (`forGraha`), mantra, pooja, vrat, daan, rudraksha, yantra, Lal Kitab.",
  reports:
    "Composed JSON reports: kundli (lite, detailed, brihad), match making, mangal dosha, sade sati, " +
    "varshaphal, numerology, horoscopes.",
  tarot: "Tarot: cards, a single card, daily card, spreads, yes/no.",
  transit: "Transits: positions, sade sati, small panoti, tarabala, vedha, double transit, dated windows.",
  varshaphal: "Varshaphal (annual chart): chart, lord, muntha, mudda dasha, harsha bala, tajika yogas.",
  western:
    "Western (tropical) astrology. Sub-groups: `natal`, `synastry`, `transits`, `returns` and more. " +
    "Answers in English (`narrative` also in Hindi and Marathi).",
};

const PARAM_DOC = {
  lat: "Latitude in decimal degrees, north positive.",
  lon: "Longitude in decimal degrees, east positive.",
  tz: "Time zone: IANA name (`Asia/Kolkata`) or UTC offset (`+05:30`).",
  tz_utc: "Time zone: IANA name (`Asia/Kolkata`) or UTC offset (`+05:30`). Defaults to `UTC`.",
  date: "Date, `YYYY-MM-DD`.",
  date_today: "Date, `YYYY-MM-DD`. Defaults to today in `tz`.",
  time: "Local time, 24-hour `HH:MM`.",
  lang:
    "Response language: a `Lang` case or its code (`'hi'`, `'gu'`, ...). Sent as `lang` and `locale`.",
  lang_shape: "Response language: a `Lang` case or its code (`'hi'`, `'gu'`, ...).",
  ayanamsa: "Sidereal ayanamsa (`Ayanamsa` case or value); the API uses Lahiri when left out.",
  houseSystem: "House system (`HouseSystem` case or value), sent as `house_system`.",
  startDate: "First day of the window, `YYYY-MM-DD`.",
  endDate: "Last day of the window, `YYYY-MM-DD`.",
  rashi: "Moon sign, English or Hindi name in any case (`aries`, `Mesh`).",
  name: "Full name as written on official documents.",
  dob: "Date of birth, `YYYY-MM-DD`.",
  from: "Start of the range, `YYYY-MM-DD` (local midnight in `tz`); defaults to now.",
  boy: "Groom's birth details, sent as `boy.date`, `boy.time`, `boy.tz`, `boy.lat`, `boy.lon`.",
  girl: "Bride's birth details, sent as `girl.date`, `girl.time`, `girl.tz`, `girl.lat`, `girl.lon`.",
  personA: "First person's birth details, sent as `personA.date`, `personA.time`, ...",
  personB: "Second person's birth details, sent as `personB.date`, `personB.time`, ...",
  birth: "Natal chart: birth `date`, `time`, `tz`, `lat`, `lon`, sent as `birth.date`, `birth.time`, ...",
  birth_place:
    "Natal chart: birth `date`, `time`, `tz`, `lat`, `lon`, sent as `birth.*`; `lat` / `lon` also unprefixed.",
  birth_window:
    "Natal chart, sent as `birth.*`: `date`, `time`, `tz`; `lat` / `lon` add the lagna-based fields.",
};

/** PHP native type of a parameter. */
function nativeType(p) {
  const base =
    p.kind === "object"
      ? "array"
      : { Lang: "Lang|string", Ayanamsa: "Ayanamsa|string", HouseSystem: "HouseSystem|string" }[p.type] ?? p.type;
  if (p.required) return base;
  return base.includes("|") ? `${base}|null` : `?${base}`;
}

/** PHPDoc type of a parameter. */
function docType(p) {
  const base =
    p.kind === "object"
      ? p.shape
      : { Lang: "Lang|string", Ayanamsa: "Ayanamsa|string", HouseSystem: "HouseSystem|string" }[p.type] ?? p.type;
  return p.required ? base : `${base}|null`;
}

function paramDoc(m, p) {
  if (p.kind === "object") {
    if (p.place) return PARAM_DOC.birth_place;
    if (p.shape === WINDOW_BIRTH_SHAPE) return PARAM_DOC.birth_window;
    return PARAM_DOC[p.name] ?? "";
  }
  if (p.name === "lang") return p.wires.includes("locale") ? PARAM_DOC.lang : PARAM_DOC.lang_shape;
  if (p.doc) return PARAM_DOC[p.doc];
  const ex = exampleFor(m.entry.path);
  const fromExample = ex?.docs?.[p.wires[0]] ?? (p.kind === "path" ? ex?.docs?.[p.name] : undefined);
  if (fromExample) return fromExample;
  return PARAM_DOC[p.name] ?? "";
}

const docEscape = (text) => String(text).replace(/\s+/g, " ").trim().replace(/\*\//g, "*\\/");

/** Word-wrap `text` into lines of at most `width` characters (long words stay whole). */
function wrapText(text, width) {
  const words = docEscape(text).split(" ").filter(Boolean);
  const lines = [];
  let line = "";
  for (const w of words) {
    if (line && line.length + 1 + w.length > width) {
      lines.push(line);
      line = w;
    } else {
      line = line ? `${line} ${w}` : w;
    }
  }
  if (line) lines.push(line);
  // A PHPDoc line that starts with "@" would read as a tag.
  return lines.map((l) => (l.startsWith("@") ? "\\" + l : l));
}

const DOC_WIDTH = 100;

function methodDoc(m, indent) {
  const pre = `${indent} * `;
  const out = [`${indent}/**`];
  const push = (text) => {
    for (const l of wrapText(text, DOC_WIDTH - pre.length)) out.push(pre + l);
  };
  push(m.entry.desc || m.entry.path);
  out.push(`${indent} *`);
  if (m.cost !== null && m.cost !== undefined) {
    out.push(`${pre}Cost: ${m.cost} credit${m.cost === 1 ? "" : "s"} per call.`);
    out.push(`${indent} *`);
  }
  const renamed = [];
  for (const p of m.params) {
    if (p.kind === "object" || p.kind === "path") continue;
    for (const w of p.wires) {
      if (w !== p.name && !(p.name === "lang" && w === "locale")) renamed.push(`\`${p.name}\` -> \`${w}\``);
    }
  }
  if (renamed.length) {
    push(`Wire names: ${renamed.join(", ")}.`);
    out.push(`${indent} *`);
  }
  out.push(`${pre}\`GET ${m.entry.path}\``);
  out.push(`${indent} *`);
  for (const p of ordered(m.params)) {
    const head = `@param ${docType(p)} $${p.name}`;
    const text = paramDoc(m, p);
    if (!text) {
      out.push(pre + head);
      continue;
    }
    const oneLine = `${head} ${docEscape(text)}`;
    if (pre.length + oneLine.length <= DOC_WIDTH) {
      out.push(pre + oneLine);
    } else if (pre.length + head.length > DOC_WIDTH - 20) {
      out.push(pre + head);
      for (const l of wrapText(text, DOC_WIDTH - pre.length - 4)) out.push(`${pre}    ${l}`);
    } else {
      const words = docEscape(text).split(" ");
      let first = head;
      while (words.length && pre.length + first.length + 1 + words[0].length <= DOC_WIDTH) {
        first += " " + words.shift();
      }
      out.push(pre + first);
      if (words.length) for (const l of wrapText(words.join(" "), DOC_WIDTH - pre.length - 4)) out.push(`${pre}    ${l}`);
    }
  }
  out.push(`${pre}@param RequestOptions|null $requestOptions Per-call timeout, raw body or onResponse hook.`);
  out.push(`${indent} *`);
  out.push(`${pre}@return array<mixed> The decoded JSON response.`);
  out.push(`${indent} *`);
  out.push(`${pre}@throws LifeAstroException On an API error or a network failure.`);
  out.push(`${indent} */`);
  return out;
}

const ordered = (params) => [...params.filter((p) => p.required), ...params.filter((p) => !p.required)];

function phpString(s) {
  return "'" + String(s).replace(/\\/g, "\\\\").replace(/'/g, "\\'") + "'";
}

function methodSrc(m) {
  const I = "    ";
  const lines = methodDoc(m, I);
  const params = ordered(m.params);
  const sig = params.map((p) => `${nativeType(p)} $${p.name}${p.required ? "" : " = null"}`);
  sig.push("?RequestOptions $requestOptions = null");
  if (params.length === 0) {
    lines.push(`${I}public function ${m.method}(${sig[0]}): array`);
    lines.push(`${I}{`);
  } else {
    lines.push(`${I}public function ${m.method}(`);
    for (const s of sig) lines.push(`${I}${I}${s},`);
    lines.push(`${I}): array {`);
  }
  const pathParams = m.params.filter((p) => p.kind === "path");
  const pathExpr = pathParams.length
    ? `Query::path(${phpString(m.entry.path)}, [${pathParams.map((p) => `${phpString(p.name)} => $${p.name}`).join(", ")}])`
    : phpString(m.entry.path);
  const objects = m.params.filter((p) => p.kind === "object");
  const hasQuery = m.ops.length > 0 || objects.length > 0;
  const body = [];
  if (hasQuery) {
    body.push("$query = [];");
    // Object params first (their builders run before the extras in Node), then every scalar
    // in manifest order: a later put of the same wire name wins, as in Node.
    for (const p of objects) {
      body.push(`Query::putFields($query, ${phpString(p.prefix)}, $${p.name});`);
      if (p.place) body.push(`Query::putFields($query, '', $${p.name}, ['lat', 'lon']);`);
    }
    for (const [, wire, expr] of m.ops) body.push(`Query::put($query, ${phpString(wire)}, ${expr});`);
  }
  if (pathParams.length) {
    const oneLine = `$path = ${pathExpr};`;
    if (2 * I.length + oneLine.length <= 120) {
      body.push(oneLine);
    } else {
      body.push(`$path = Query::path(${phpString(m.entry.path)}, [`);
      for (const p of pathParams) body.push(`${I}${phpString(p.name)} => $${p.name},`);
      body.push("]);");
    }
  }
  for (const l of body) lines.push(`${I}${I}${l}`);
  if (body.length) lines.push("");
  const target = pathParams.length ? "$path" : pathExpr;
  lines.push(`${I}${I}return $this->http->requestArray(${target}, ${hasQuery ? "$query" : "[]"}, $requestOptions);`);
  lines.push(`${I}}`);
  return lines.join("\n");
}

const HEADER = `<?php

/**
 * GENERATED by tool/generate.mjs from the endpoint manifest of the Node.js SDK.
 * Do not edit by hand: run \`composer generate\` (or \`node tool/generate.mjs\`).
 */

declare(strict_types=1);

namespace LifeAstroApi\\Resources;
`;

function imports(methods, extra = []) {
  const uses = new Set(["LifeAstroApi\\Exception\\LifeAstroException", "LifeAstroApi\\Http\\HttpClient", "LifeAstroApi\\RequestOptions", ...extra]);
  for (const m of methods) {
    for (const p of m.params) {
      if (p.type === "Lang") uses.add("LifeAstroApi\\Lang");
      if (p.type === "Ayanamsa") uses.add("LifeAstroApi\\Ayanamsa");
      if (p.type === "HouseSystem") uses.add("LifeAstroApi\\HouseSystem");
    }
    if (m.ops.length || m.params.some((p) => p.kind === "object" || p.kind === "path")) uses.add("LifeAstroApi\\Http\\Query");
  }
  return [...uses].sort().map((u) => `use ${u};`).join("\n");
}

function classSrc(cls, doc, methods, children = []) {
  const out = [];
  out.push("/**");
  for (const l of wrapText(doc, DOC_WIDTH - 3)) out.push(` * ${l}`);
  out.push(" */");
  out.push(`final class ${cls}`);
  out.push("{");
  for (const [prop, childCls] of children) {
    out.push(`    public readonly ${childCls} $${prop};`);
  }
  if (children.length) out.push("");
  out.push("    /** @internal Use the resource groups of {@see \\LifeAstroApi\\LifeAstro}. */");
  if (children.length) {
    out.push("    public function __construct(private readonly HttpClient $http)");
    out.push("    {");
    for (const [prop, childCls] of children) out.push(`        $this->${prop} = new ${childCls}($http);`);
    out.push("    }");
  } else {
    out.push("    public function __construct(private readonly HttpClient $http)");
    out.push("    {");
    out.push("    }");
  }
  for (const m of methods) {
    out.push("");
    out.push(methodSrc(m));
  }
  out.push("}");
  return out.join("\n");
}

// ---------------------------------------------------------------------------
// Resolve, check, write
// ---------------------------------------------------------------------------
const methods = entries.map(resolveEntry);

// PHP method names are case-insensitive: check collisions within each class that way.
const seen = new Map();
for (const m of methods) {
  const key = `${m.ns}#${m.method.toLowerCase()}`;
  if (seen.has(key)) throw new Error(`PHP method collision: ${m.ns}.${m.method} from ${seen.get(key)} and ${m.entry.path}`);
  seen.set(key, m.entry.path);
  if (m.cost === null) console.warn(`warning: no credit cost for ${m.entry.path} in tool/costs.json`);
  const said = /\((\d[\d,]*) credits?\)/.exec(m.entry.desc ?? "");
  if (said && m.cost !== null && Number(said[1].replace(/,/g, "")) !== m.cost) {
    console.warn(`warning: ${m.ns}.${m.method} description says ${said[1]} credits, tool/costs.json says ${m.cost}`);
  }
}

const byNs = new Map();
for (const m of methods) {
  if (!byNs.has(m.ns)) byNs.set(m.ns, []);
  byNs.get(m.ns).push(m);
}
for (const list of byNs.values()) list.sort((a, b) => (a.method < b.method ? -1 : a.method > b.method ? 1 : 0));

if (existsSync(OUT)) {
  for (const f of readdirSync(OUT)) if (f.endsWith(".php")) rmSync(join(OUT, f));
}
mkdirSync(OUT, { recursive: true });

const flatNs = [...byNs.keys()].filter((ns) => !ns.startsWith("western")).sort();
const westernChildNs = [...byNs.keys()].filter((ns) => ns.startsWith("western.")).sort();
const groups = []; // [prop, cls]

for (const ns of flatNs) {
  const cls = pascal(ns);
  const list = byNs.get(ns);
  const src = `${HEADER}\n${imports(list)}\n\n${classSrc(cls, `${NS_DOC[ns] ?? ns} Use \`$client->${ns}\`.`, list)}\n`;
  writeFileSync(join(OUT, `${cls}.php`), src);
  groups.push([ns, cls]);
}

const westernChildren = [];
for (const ns of westernChildNs) {
  const sub = ns.split(".")[1];
  const cls = "Western" + pascal(sub);
  const list = byNs.get(ns);
  const lang = sub === "narrative" ? "Takes `lang` for `en`, `hi` and `mr`." : "Answers in English.";
  const doc = `Western \`${sub}\` endpoints: \`$client->western->${sub}\`. ${lang}`;
  writeFileSync(join(OUT, `${cls}.php`), `${HEADER}\n${imports(list)}\n\n${classSrc(cls, doc, list)}\n`);
  westernChildren.push([sub, cls]);
}
{
  const direct = byNs.get("western") ?? [];
  const src = `${HEADER}\n${imports(direct)}\n\n${classSrc("Western", NS_DOC.western, direct, westernChildren)}\n`;
  writeFileSync(join(OUT, "Western.php"), src);
  groups.push(["western", "Western"]);
}
groups.sort((a, b) => (a[0] < b[0] ? -1 : 1));

{
  const lines = [
    HEADER,
    "use LifeAstroApi\\Http\\HttpClient;",
    "",
    "/**",
    " * Every resource group of the API as a read-only property: `$client->panchang`, `$client->chart`,",
    " * `$client->western->natal` and so on. {@see \\LifeAstroApi\\LifeAstro} extends this class.",
    " */",
    "abstract class Resources",
    "{",
  ];
  for (const [prop, cls] of groups) {
    const doc = prop === "western" ? NS_DOC.western : NS_DOC[prop] ?? prop;
    const wrapped = wrapText(doc, DOC_WIDTH - 11);
    if (wrapped.length === 1 && wrapped[0].length <= DOC_WIDTH - 12) {
      lines.push(`    /** ${wrapped[0]} */`);
    } else {
      lines.push("    /**");
      for (const l of wrapped) lines.push(`     * ${l}`);
      lines.push("     */");
    }
    lines.push(`    public readonly ${cls} $${prop};`);
    lines.push("");
  }
  lines.push("    /** @internal */");
  lines.push("    public function __construct(HttpClient $http)");
  lines.push("    {");
  for (const [prop, cls] of groups) lines.push(`        $this->${prop} = new ${cls}($http);`);
  lines.push("    }");
  lines.push("}");
  lines.push("");
  writeFileSync(join(OUT, "Resources.php"), lines.join("\n"));
}

// ---------------------------------------------------------------------------
// Resolved manifest for the tests and the live sweep
// ---------------------------------------------------------------------------
const manifestOut = methods
  .map((m) => ({
    key: `${m.ns}.${m.method}`,
    path: m.entry.path,
    shape: m.entry.shape,
    cost: m.cost,
    extras: (m.entry.extras ?? []).map((x) => ({ wire: x.wire, field: x.name ?? camelOf(x.wire), required: !!x.required })),
    params: ordered(m.params).map((p) => ({
      name: p.name,
      type: p.type,
      required: p.required,
      kind: p.kind,
      ...(p.kind === "object" ? { prefix: p.prefix, place: p.place } : { wires: p.wires }),
    })),
  }))
  .sort((a, b) => (a.key < b.key ? -1 : a.key > b.key ? 1 : 0));
writeFileSync(MANIFEST_OUT, JSON.stringify(manifestOut, null, 1) + "\n");

// Long-line guard: PSR-12 asks for 120 at most.
for (const f of readdirSync(OUT)) {
  readFileSync(join(OUT, f), "utf8")
    .split("\n")
    .forEach((l, i) => {
      if (l.length > 120) console.warn(`warning: ${f}:${i + 1} is ${l.length} characters long`);
    });
}

console.log(
  `Generated ${groups.length} groups (+${westernChildren.length} Western sub-groups) in src/Resources: ` +
    `${methods.length} methods from ${entries.length} manifest entries.`,
);
