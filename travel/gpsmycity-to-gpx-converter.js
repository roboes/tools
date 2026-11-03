// GPSmyCity to GPX converter
// Last update: 2026-11-03

(() => {
  // Get the map data (global `jarr`, with a fallback that parses it from the page source)
  let data = window.jarr;
  if (!data) {
    const src = [...document.scripts].map((s) => s.textContent).find((t) => /jarr\s*=/.test(t));
    const m = src && src.match(/jarr\s*=\s*(\{[\s\S]*?\})\s*;/);
    if (!m) return console.error('Could not find jarr on this page.');
    data = JSON.parse(m[1]);
  }

  // Helpers
  const toList = (v) => (v == null ? [] : Array.isArray(v) ? v : Object.values(v));
  const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  const clean = (v) => (v == null || v === 'None' ? null : v);

  // If an entry comes as a string like "['48.1', '11.5', ...]", turn it into an array
  const asArray = (e) => {
    if (Array.isArray(e)) return e;
    if (typeof e === 'string') {
      try {
        return JSON.parse(e.replace(/'/g, '"'));
      } catch {
        return null;
      }
    }
    return null;
  };

  const tourName = document.title.trim();

  // Track points: each entry is [lat, lon]
  const trackPoints = toList(data.path)
    .map((e) => asArray(clean(e)))
    .filter((a) => a && a.length >= 2)
    .map((a) => ({ lat: parseFloat(a[0]), lon: parseFloat(a[1]) }))
    .filter((p) => !isNaN(p.lat) && !isNaN(p.lon));

  // Waypoints: each entry is [lat, lon, name, number, id]
  const waypoints = toList(data.pins)
    .map((e) => asArray(clean(e)))
    .filter((a) => a && a.length >= 3)
    .map((a) => ({ lat: parseFloat(a[0]), lon: parseFloat(a[1]), name: a[2] }))
    .filter((p) => !isNaN(p.lat) && !isNaN(p.lon));

  // Build GPX
  let gpx = `<?xml version="1.0" encoding="UTF-8"?>\n` + `<gpx version="1.1" creator="GPSmyCity" xmlns="http://www.topografix.com/GPX/1/1">\n` + `  <metadata><desc>${esc(tourName)}</desc></metadata>\n`;

  for (const w of waypoints) {
    gpx += `  <wpt lat="${w.lat}" lon="${w.lon}"><name>${esc(w.name)}</name></wpt>\n`;
  }
  if (trackPoints.length) {
    gpx += `  <trk>\n    <trkseg>\n`;
    for (const p of trackPoints) gpx += `      <trkpt lat="${p.lat}" lon="${p.lon}"></trkpt>\n`;
    gpx += `    </trkseg>\n  </trk>\n`;
  }
  gpx += `</gpx>\n`;

  // Download
  const filename = tourName.replace(/[^\w\-]+/g, '_').replace(/^_+|_+$/g, '') + '.gpx';
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([gpx], { type: 'application/gpx+xml' }));
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();

  console.log(`Saved ${filename}: ${waypoints.length} waypoints, ${trackPoints.length} track points`);
})();
