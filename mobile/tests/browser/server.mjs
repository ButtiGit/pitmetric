import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { resolve, extname, sep } from 'node:path';
import { session, snapshot } from './fixtures.mjs';

// Local test fixtures are served over HTTP because WebKit routing does not intercept
// all fetches from a service-worker-controlled page. This server is never deployed.
const root = fileURLToPath(new URL('../../../public/', import.meta.url));
const processed = new Map();
const received = [];
let originUnavailable = false;
const mime = { '.js': 'text/javascript', '.css': 'text/css', '.html': 'text/html', '.json': 'application/json', '.svg': 'image/svg+xml', '.png': 'image/png', '.webmanifest': 'application/manifest+json' };
createServer(async (req, res) => {
  const pathname = new URL(req.url, 'http://127.0.0.1:4174').pathname;
  const json = body => { res.setHeader('Content-Type', 'application/json'); res.end(JSON.stringify(body)); };
  if (pathname === '/__test/reset') { processed.clear(); received.length = 0; originUnavailable = false; return json({ ok: true }); }
  if (pathname === '/__test/offline') { originUnavailable = true; return json({ ok: true }); }
  if (pathname === '/__test/online') { originUnavailable = false; return json({ ok: true }); }
  if (pathname === '/__test/operations') return json(received);
  if (originUnavailable) { req.socket.destroy(); return; }
  if (pathname.startsWith('/api/mobile/')) {
    let raw = '';
    for await (const chunk of req) raw += chunk;
    const body = raw ? JSON.parse(raw) : {};
    if (pathname.endsWith('/login')) return json({ token: 'browser-test', session });
    if (pathname.endsWith('/me')) return json({ session });
    if (pathname.endsWith('/bootstrap')) return json({ data: snapshot, synced_at: '2026-10-30T09:30:00Z' });
    if (pathname.endsWith('/sync')) {
      received.push(body);
      const duplicate = processed.has(body.client_id);
      const result = processed.get(body.client_id) || { id: processed.size + 1, entity: 'timing_lap' };
      processed.set(body.client_id, result);
      return json({ duplicate, result });
    }
    return json({ ok: true });
  }
  const path = resolve(root, `.${pathname === '/mobile/' ? '/mobile/index.html' : pathname}`);
  if (!path.startsWith(`${resolve(root)}${sep}`)) { res.writeHead(403); return res.end(); }
  try {
    const bytes = await readFile(path);
    res.setHeader('Content-Type', mime[extname(path)] || 'application/octet-stream');
    res.setHeader('Vary', 'Origin');
    if (req.headers.origin) res.setHeader('Access-Control-Allow-Origin', req.headers.origin);
    res.end(bytes);
  } catch {
    res.writeHead(404); res.end();
  }
}).listen(4174, '127.0.0.1');
