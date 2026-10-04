// Synthetic response fixtures only. No live provider, patient, or account data.
import test from 'node:test';
import assert from 'node:assert/strict';
import { buildSeoQuery, createSeoClient } from '../app.drbastaninejad.com/Frontend/assets/js/seo-client.mjs';
import { snapshotFixture } from './fixtures/reporting-snapshot.mjs';

test('query defaults match the agreed MS Robot reporting windows', () => {
  assert.equal(buildSeoQuery({}), 'period=last_28d&comparison=previous');
});
test('query cannot select a different project, clinic, site or arbitrary window', () => {
  for (const input of [{ projectId: 'test-other' }, { clinicId: 2 }, { site: 'test.invalid' }, { period: 'all' }, { comparison: 'year' }, { endDate: '2026-02-30' }, { endDate: '2099-01-01' }]) {
    assert.throws(() => buildSeoQuery(input), /Invalid/);
  }
});
test('valid historical date and no comparison round-trip without timezone drift', () => {
  assert.equal(buildSeoQuery({ period: 'last_7d', comparison: 'none', endDate: '2026-01-31' }), 'period=last_7d&comparison=none&endDate=2026-01-31');
});
test('unconfigured remains unconfigured with no metrics or raw provider details', async () => {
  let request;
  const client = createSeoClient({ base: 'https://dashboard.drbastaninejad.com/api/v1', token: () => 'synthetic-test-token', fetcher: async (url, init) => {
    request = { url, init };
    return { status: 503, json: async () => ({ ok: false, data: null, meta: { code: 'reporting_unconfigured' }, errors: [{ message: 'raw-secret-error' }] }) };
  } });
  assert.deepEqual(await client.read({}), { state: 'unconfigured' });
  assert.equal(request.url, 'https://dashboard.drbastaninejad.com/api/v1/analytics/seo?period=last_28d&comparison=previous');
  assert.equal(request.init.headers.Authorization, 'Bearer synthetic-test-token');
  assert.equal(request.init.credentials, 'omit');
  assert.equal(request.init.cache, 'no-store');
  assert.equal(request.init.redirect, 'error');
});
test('missing staff session does not issue a request', async () => {
  let calls = 0;
  const client = createSeoClient({ base: 'https://dashboard.drbastaninejad.com/api/v1', token: () => null, fetcher: async () => { calls++; } });
  assert.deepEqual(await client.read({}), { state: 'unauthorized' });
  assert.equal(calls, 0);
});
test('bearer token is never sent to an unapproved host', async () => {
  for (const base of ['https://msrobot.maziyarid.com/api/v1', 'https://evil.invalid/api/v1', 'http://dashboard.drbastaninejad.com/api/v1', 'https://dashboard.drbastaninejad.com.evil.invalid/api/v1']) {
    assert.throws(() => createSeoClient({ base, token: () => 'synthetic-test-token' }), /Invalid/);
  }
});
test('only known error states are displayed and raw messages are ignored', async () => {
  for (const [status, state] of [[401, 'unauthorized'], [403, 'forbidden'], [404, 'unavailable'], [422, 'invalid_request'], [500, 'unavailable']]) {
    const client = createSeoClient({ base: 'https://dashboard.drbastaninejad.com/api/v1', token: () => 'synthetic', fetcher: async () => ({ status, json: async () => ({ errors: [{message:'<script>private</script>'}] }) }) });
    assert.deepEqual(await client.read({}), { state });
  }
});
test('unexpected success or schema never activates report rendering', async () => {
  const client = createSeoClient({ base: 'https://dashboard.drbastaninejad.com/api/v1', token: () => 'synthetic', fetcher: async () => ({ status: 200, json: async () => ({ ok:true, data:{schemaVersion:'unknown',metrics:[{value:123}] } }) }) });
  assert.deepEqual(await client.read({}), { state: 'unavailable' });
});
test('network and malformed JSON failures fail closed', async () => {
  for (const fetcher of [async () => { throw Error('secret'); }, async () => ({ status:503,json:async () => { throw Error('secret'); } })]) {
    const client = createSeoClient({ base:'https://dashboard.drbastaninejad.com/api/v1',token:()=> 'synthetic',fetcher });
    assert.deepEqual(await client.read({}), { state:'unavailable' });
  }
});
test('year0000 and previous-window underflow are rejected, valid year0001 bounds work',()=>{
  for(const query of [{endDate:'0000-01-01'}, {period:'last_7d',comparison:'previous',endDate:'0001-01-07'}, {period:'last_7d',comparison:'none',endDate:'0001-01-06'}]) assert.throws(()=>buildSeoQuery(query),/Invalid/);
  assert.ok(buildSeoQuery({period:'last_7d',comparison:'none',endDate:'0001-01-07'}).includes('0001-01-07'));
  assert.ok(buildSeoQuery({period:'last_7d',comparison:'previous',endDate:'0001-01-14'}).includes('0001-01-14'));
});
test('logout clears staff state immediately and revokes only at the CRM API',async()=>{
  const events=[];
  const client=createSeoClient({base:'https://dashboard.drbastaninejad.com/api/v1',token:()=> 'synthetic-staff-token',fetcher:async(url,options)=>{events.push({url,options});return {status:200};}});
  assert.equal(await client.logout(()=>events.push('cleared')),true);
  assert.equal(events[0],'cleared');
  assert.equal(events[1].url,'https://dashboard.drbastaninejad.com/api/v1/auth/logout');
  assert.equal(events[1].options.headers.Authorization,'Bearer synthetic-staff-token');
  assert.equal(events[1].options.method,'POST');
});
test('canonical successful snapshot yields only its safe presentation projection',async()=>{
  const client=createSeoClient({base:'https://dashboard.drbastaninejad.com/api/v1',token:()=> 'synthetic',fetcher:async()=>({status:200,json:async()=>({ok:true,data:snapshotFixture()})})});
  const result=await client.read({period:'last_7d',endDate:'2026-09-30'});
  assert.equal(result.state,'ready');assert.equal(result.report.sections[0].metrics[0].value,20);
  assert.ok(!JSON.stringify(result).includes('synthetic-project'));
});
test('a valid snapshot for the wrong requested period is not rendered',async()=>{
  const client=createSeoClient({base:'https://dashboard.drbastaninejad.com/api/v1',token:()=> 'synthetic',fetcher:async()=>({status:200,json:async()=>({ok:true,data:snapshotFixture()})})});
  assert.deepEqual(await client.read({period:'last_28d',endDate:'2026-09-30'}),{state:'unavailable'});
});
