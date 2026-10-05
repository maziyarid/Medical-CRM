/* MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad — optional local Chromium QA */
import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { readFile, stat } from 'node:fs/promises';
import { resolve, extname } from 'node:path';
import { pathToFileURL, fileURLToPath } from 'node:url';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = resolve(fileURLToPath(new URL('..', import.meta.url)));
const html = `<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/css/tokens.css"><link rel="stylesheet" href="/assets/css/base.css"><link rel="stylesheet" href="/assets/css/recording.css"></head><body style="padding:20px"><main><div id="recorder"></div><div id="turns"></div></main><script type="module">import * as panel from '/assets/js/recording/panel.js'; import {createDraft} from '/assets/js/recording/session.js'; window.panel=panel; window.createDraft=createDraft; window.ready=true;</script></body></html>`;
const server = createServer(async (request, response) => {
  try {
    if (request.url === '/__recorder-test') { response.setHeader('Content-Type', 'text/html'); response.end(html); return; }
    const path = resolve(root, '.' + new URL(request.url, 'http://localhost').pathname);
    if (!path.startsWith(root + '/')) { response.writeHead(403); response.end(); return; }
    await stat(path); response.setHeader('Content-Type', ({ '.js': 'text/javascript', '.css': 'text/css' })[extname(path)] || 'text/plain'); response.end(await readFile(path));
  } catch { response.writeHead(404); response.end(); }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROMIUM_EXECUTABLE || '/usr/bin/chromium', args: ['--no-sandbox'] });
try {
  const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
  const external = []; await page.route('**/*', route => { if (route.request().url().startsWith(origin)) route.continue(); else { external.push(route.request().url()); route.abort(); } });
  await page.goto(origin + '/__recorder-test'); await page.waitForFunction(() => window.ready);
  await page.evaluate(() => window.panel.mountRecorderPanel(document.querySelector('#recorder'), { patientId: '42' }));
  assert.equal(await page.locator('#recorder button').count(), 5);
  assert.equal(await page.locator('#recorder button:enabled').count(), 0);
  assert.equal(await page.locator('#recorder input[type=checkbox]:disabled').count(), 1);
  assert.match(await page.locator('#recorder').innerText(), /هنوز فعال نشده/);
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
  assert.equal(await page.locator('#recorder [role=status]').count(), 1);
  await page.evaluate(() => {
    const draft = window.createDraft({ sessionId: 'synthetic-session', audioDurationMs: 1000, provider: { name: 'synthetic', version: '1' }, segments: [{ id: 'turn-1', startMs: 0, endMs: 1000, speakerId: null, text: '<img src=x onerror="window.attacked=true"> متن ساختگی' }] });
    window.lastDraft = draft;
    window.panel.renderDraftTurns(document.querySelector('#turns'), draft, { actorId: '9', onChange: value => { window.lastDraft = value; } });
  });
  assert.equal(await page.locator('#turns img').count(), 0);
  assert.equal(await page.evaluate(() => window.attacked), undefined);
  assert.match(await page.locator('#turns').innerText(), /گوینده نامشخص/);
  await page.locator('#turns select').selectOption('doctor');
  await page.locator('#turns input').fill('پزشک ساختگی');
  await page.getByRole('button', { name: 'ثبت دستی گوینده' }).click();
  assert.equal(await page.evaluate(() => window.lastDraft.segments[0].speaker.role), 'doctor');
  await page.locator('#turns textarea').fill('متن اصلاح‌شده ساختگی');
  await page.getByRole('button', { name: 'ثبت اصلاح متن' }).click();
  assert.equal(await page.evaluate(() => window.lastDraft.segments[0].text), 'متن اصلاح‌شده ساختگی');
  assert.equal(await page.evaluate(() => window.lastDraft.reviewStatus), 'unreviewed');
  assert.equal(external.length, 0);
  await page.locator('#turns').evaluate(node => node.remove());
  if (process.env.SCREENSHOT_PATH) await page.screenshot({ path: process.env.SCREENSHOT_PATH, fullPage: true });
  console.log('PASS: Chromium mobile-width RTL panel, fail-closed controls, manual draft editing, literal unsafe text, no external requests');
} finally { await browser.close(); await new Promise(resolve => server.close(resolve)); }
