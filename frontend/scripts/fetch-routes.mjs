// Run once: node scripts/fetch-routes.mjs
// Fetches real street routes from OSRM (no API key needed) and prints updated routes.ts
// ponytail: pre-baked fixtures so mock mode has zero runtime external deps

const ROUTES = [
  { name: 'La Rambla',                  from: [41.3863, 2.1734], to: [41.3791, 2.1776] },
  { name: 'Passeig de Gràcia',          from: [41.3975, 2.1635], to: [41.3851, 2.1636] },
  { name: 'Avinguda Diagonal',          from: [41.3887, 2.1280], to: [41.3935, 2.1880] },
  { name: 'Gran Via',                   from: [41.3797, 2.1170], to: [41.3798, 2.1820] },
  { name: 'Sagrada Família → Barceloneta', from: [41.4035, 2.1744], to: [41.3810, 2.1900] },
  { name: 'Gràcia → Eixample',          from: [41.4023, 2.1562], to: [41.3910, 2.1540] },
  { name: 'Poblenou → Glòries',         from: [41.3997, 2.2003], to: [41.3903, 2.1824] },
  { name: 'Sants → Plaça Espanya',      from: [41.3784, 2.1409], to: [41.3749, 2.1543] },
  { name: 'Carrer de Balmes',           from: [41.3996, 2.1535], to: [41.3882, 2.1521] },
  { name: "Avinguda del Paral·lel",     from: [41.3749, 2.1543], to: [41.3781, 2.1719] },
  { name: 'Travessera de Gràcia',       from: [41.4025, 2.1419], to: [41.4020, 2.1669] },
  { name: 'Passeig de Sant Joan',       from: [41.4044, 2.1733], to: [41.3924, 2.1733] },
  { name: 'Via Laietana',               from: [41.3882, 2.1780], to: [41.3815, 2.1776] },
  { name: "Carrer d'Aragó",             from: [41.3911, 2.1176], to: [41.3916, 2.1676] },
  { name: 'Avinguda Meridiana',         from: [41.4168, 2.1872], to: [41.3918, 2.1712] },
  { name: "Montjuïc → Paral·lel",      from: [41.3644, 2.1532], to: [41.3749, 2.1543] },
]

// Thin coords: keep at most maxPts evenly spaced (streets still followed, file stays small)
function thin(coords, maxPts = 30) {
  if (coords.length <= maxPts) return coords
  const step = (coords.length - 1) / (maxPts - 1)
  return Array.from({ length: maxPts }, (_, i) => coords[Math.round(i * step)])
}

async function fetchRoute(from, to) {
  // OSRM coords are [lng, lat]
  const url = `https://router.project-osrm.org/route/v1/driving/${from[1]},${from[0]};${to[1]},${to[0]}?overview=full&geometries=geojson`
  const res = await fetch(url)
  if (!res.ok) throw new Error(`OSRM error ${res.status} for ${url}`)
  const data = await res.json()
  // GeoJSON coords are [lng, lat] — swap to [lat, lng] for Leaflet
  return data.routes[0].geometry.coordinates.map(([lng, lat]) => [
    Math.round(lat * 1e6) / 1e6,
    Math.round(lng * 1e6) / 1e6,
  ])
}

const results = []
for (const route of ROUTES) {
  process.stdout.write(`Fetching: ${route.name}... `)
  try {
    const coords = thin(await fetchRoute(route.from, route.to))
    results.push({ name: route.name, coords })
    console.log(`${coords.length} pts`)
  } catch (e) {
    console.error(`FAILED: ${e.message}`)
    process.exit(1)
  }
  await new Promise(r => setTimeout(r, 200)) // be polite to the demo server
}

// Emit updated routes.ts
const lines = [
  '// Real Barcelona street routes via OSRM — pre-baked, no runtime API calls',
  '// Regenerate: node scripts/fetch-routes.mjs > src/fixtures/routes.ts',
  '',
  'export const BARCELONA_ROUTES: { name: string; coords: [number, number][] }[] = [',
]
for (const { name, coords } of results) {
  const escaped = name.replace(/'/g, "\\'")
  lines.push(`  {`)
  lines.push(`    name: '${escaped}',`)
  lines.push(`    coords: [`)
  for (const [lat, lng] of coords) {
    lines.push(`      [${lat}, ${lng}],`)
  }
  lines.push(`    ],`)
  lines.push(`  },`)
}
lines.push(']')
console.log('\n---routes.ts---\n' + lines.join('\n'))
