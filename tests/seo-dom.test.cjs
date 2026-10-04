// Synthetic DOM acceptance tests. No production network or credentials.
// Run with jsdom available: node --experimental-vm-modules --test tests/seo-dom.test.cjs
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { JSDOM, VirtualConsole } = require('jsdom');
const root = path.resolve(__dirname, '../app.drbastaninejad.com/Frontend');
const wait = () => new Promise(resolve => setImmediate(resolve));

async function fixture(role = 'admin', responses = [503], identityName = 'حساب آزمایشی') {
  const console = new VirtualConsole();
  console.on('jsdomError',error=>{if(!error.message.startsWith('Not implemented: navigation'))throw error;});
  const dom = new JSDOM(fs.readFileSync(path.join(root,'pages/staff/seo.html'),'utf8'), { url:'https://app.drbastaninejad.com/Frontend/pages/staff/seo.html', runScripts:'outside-only',virtualConsole:console });
  const win = dom.window;
  if(typeof responses[0]==='object') {
    win.document.querySelector('#seo-period').value=responses[0].period.label;
    win.document.querySelector('#seo-end-date').value=responses[0].period.end;
    win.document.querySelector('#seo-comparison').value=responses[0].comparison?'previous':'none';
  }
  const requests=[];
  win.sessionStorage.setItem('mz_staff_auth_token','synthetic-test-token');
  win.fetch=async (url,options) => {
    requests.push({url,options});
    assert.equal(new URL(url).hostname,'dashboard.drbastaninejad.com');
    if (url.endsWith('/auth/me')) return {ok:true,json:async()=>({ok:true,data:{id:10,clinic_id:7,user_type:'staff',name:identityName,role,roles:[role]}})};
    if (url.endsWith('/auth/logout')) return {status:200};
    const status=responses.length>1?responses.shift():responses[0];
    if(typeof status==='object') return {status:200,json:async()=>({ok:true,data:status})};
    return {status,json:async()=>({ok:false,data:null,meta:{code:'reporting_unconfigured'}})};
  };
  const context=dom.getInternalVMContext();
  const cache=new Map();
  async function moduleFor(file) {
    if(cache.has(file)) return cache.get(file);
    const mod=new vm.SourceTextModule(fs.readFileSync(file,'utf8'),{context,identifier:file});
    cache.set(file,mod);
    await mod.link((name,ref)=>moduleFor(path.resolve(path.dirname(ref.identifier),name)));
    return mod;
  }
  new vm.Script(fs.readFileSync(path.join(root,'assets/js/chrome.js'),'utf8')).runInContext(context);
  const mod=await moduleFor(path.join(root,'assets/js/seo.mjs'));
  await mod.evaluate();
  await wait(); await wait();
  return {dom,win,requests};
}

test('admin sees truthful unconfigured section and both menu links',async()=>{
  const f=await fixture(); const doc=f.win.document;
  assert.equal(doc.querySelector('[data-testid="seo-state"]').dataset.state,'unconfigured');
  assert.equal(doc.querySelector('#seo-authorized-content').hidden,false);
  assert.equal(doc.querySelectorAll('.seo-source').length,6);
  assert.equal(doc.querySelectorAll('[data-seo-navigation]').length,2);
  assert.equal(doc.querySelector('.bottom-nav').children.length,6);
  assert.equal(doc.querySelector('.bottom-nav').style.overflowX,'auto');
  assert.equal(doc.querySelectorAll('canvas,svg[data-chart],[data-metric-value]').length,0);
  assert.equal(f.requests.filter(x=>x.url.includes('/analytics/seo')).length,1);
  f.win.close();
});
test('doctor cannot see source panels or SEO navigation',async()=>{
  const f=await fixture('doctor',[403]); const doc=f.win.document;
  assert.equal(doc.querySelector('[data-testid="seo-state"]').dataset.state,'forbidden');
  assert.equal(doc.querySelector('#seo-authorized-content').hidden,true);
  assert.equal(doc.querySelectorAll('[data-seo-navigation]').length,0);
  f.win.close();
});
test('temporary failure has a working retry without exposing raw error data',async()=>{
  const f=await fixture('admin',[500,503]); const doc=f.win.document;
  assert.equal(doc.querySelector('[data-testid="seo-state"]').dataset.state,'unavailable');
  assert.equal(doc.querySelector('#seo-status-retry').hidden,false);
  doc.querySelector('#seo-status-retry').click(); await wait();await wait();
  assert.equal(doc.querySelector('[data-testid="seo-state"]').dataset.state,'unconfigured');
  f.win.close();
});
test('range recheck sends only the selected period and preserves empty data',async()=>{
  const f=await fixture();const doc=f.win.document;
  doc.querySelector('#seo-period').value='last_7d';
  doc.querySelector('#seo-controls').dispatchEvent(new f.win.Event('submit',{cancelable:true,bubbles:true}));
  await wait();await wait();
  assert.ok(f.requests.at(-1).url.endsWith('period=last_7d&comparison=previous'));
  assert.equal(doc.querySelector('[data-testid="seo-state"]').dataset.state,'unconfigured');
  f.win.close();
});
test('SEO logout revokes once and clears only the canonical staff session',async()=>{
  const f=await fixture();
  f.win.sessionStorage.setItem('mz_patient_auth_token','synthetic-patient-token');
  f.win.document.querySelector('.logout-btn').click();await wait();await wait();
  assert.equal(f.win.sessionStorage.getItem('mz_staff_auth_token'),null);
  assert.equal(f.win.sessionStorage.getItem('mz_patient_auth_token'),'synthetic-patient-token');
  const calls=f.requests.filter(x=>x.url.endsWith('/auth/logout'));
  assert.equal(calls.length,1);assert.equal(calls[0].options.method,'POST');
  f.win.close();
});
test('adversarial identity stays text without an element or handler injection',async()=>{
  const value='<img src=x onerror="window.syntheticLeak=1"> & "test"';
  const f=await fixture('admin',[503],value);
  assert.equal(f.win.document.querySelectorAll('.sidebar-user img').length,0);
  assert.ok(f.win.document.querySelector('.sidebar-user .who').textContent.includes(value));
  f.win.close();
});
test('canonical synthetic report renders real supplied values, comparison and provenance',async()=>{
  const {snapshotFixture}=await import('./fixtures/reporting-snapshot.mjs');
  const raw=snapshotFixture();const f=await fixture('admin',[raw]);const doc=f.win.document;
  assert.equal(doc.querySelector('[data-testid="seo-state"]').dataset.state,'ready');
  assert.equal(doc.querySelectorAll('.seo-metric-card').length,4);
  assert.equal(doc.querySelector('.seo-metric-value').textContent,'۲۰');
  assert.equal(doc.querySelectorAll('#seo-rendered-report tbody tr').length,4);
  assert.ok(doc.querySelector('#seo-rendered-report').textContent.includes('داده مستقیم'));
  assert.ok(doc.querySelector('#seo-rendered-report').textContent.includes('۱۰۰٪'));
  assert.equal(doc.querySelector('#seo-connection-details').hidden,true);
  for(const sensitive of ['synthetic-project','synthetic.invalid','synthetic-only'])assert.ok(!doc.querySelector('#seo-rendered-report').textContent.includes(sensitive));
  f.win.close();
});
test('malformed snapshot renders no cards, tables or raw metadata',async()=>{
  const {snapshotFixture}=await import('./fixtures/reporting-snapshot.mjs');
  const raw=snapshotFixture();raw.sections[0].metrics[0].value='<img src=x onerror=alert(1)>';
  const f=await fixture('admin',[raw]);const doc=f.win.document;
  assert.equal(doc.querySelector('[data-testid="seo-state"]').dataset.state,'unavailable');
  assert.equal(doc.querySelectorAll('.seo-metric-card,#seo-rendered-report table').length,0);
  f.win.close();
});
test('invalid historical window leaves controls usable and can recover without sending it',async()=>{
  const f=await fixture();const doc=f.win.document;const initial=f.requests.length;
  doc.querySelector('#seo-period').value='last_7d';doc.querySelector('#seo-end-date').value='0001-01-07';
  doc.querySelector('#seo-controls').dispatchEvent(new f.win.Event('submit',{cancelable:true,bubbles:true}));await wait();
  assert.equal(doc.querySelector('[data-testid="seo-state"]').dataset.state,'invalid_request');
  assert.equal(doc.querySelector('#seo-authorized-content').hidden,false);
  assert.equal(f.requests.length,initial);
  doc.querySelector('#seo-end-date').value='2026-09-30';
  doc.querySelector('#seo-controls').dispatchEvent(new f.win.Event('submit',{cancelable:true,bubbles:true}));await wait();await wait();
  assert.equal(doc.querySelector('[data-testid="seo-state"]').dataset.state,'unconfigured');
  f.win.close();
});
test('coverage completeness and interval stay visible when comparison is disabled',async()=>{
  const {snapshotFixture}=await import('./fixtures/reporting-snapshot.mjs');
  const raw=snapshotFixture();raw.comparison=null;delete raw.comparisonSections;
  raw.sections[0].status='partial';raw.sections[0].metrics[0].coverage.complete=false;
  const f=await fixture('admin',[raw]);const doc=f.win.document;
  assert.ok([...doc.querySelectorAll('#seo-rendered-report th')].some(node=>node.textContent==='پوشش داده'));
  const first=doc.querySelector('#seo-rendered-report tbody tr');
  assert.ok(first.textContent.includes('ناقص · 2026-09-24 تا 2026-09-30'));
  assert.ok(first.textContent.includes('بدون مقایسه'));
  f.win.close();
});
