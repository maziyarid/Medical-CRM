// Canonical synthetic snapshots are confined to tests. Never loaded by the page.
import test from 'node:test';
import assert from 'node:assert/strict';
import { parseReportingSnapshot, metricComparison, formatMetric } from '../app.drbastaninejad.com/Frontend/assets/js/seo-snapshot.mjs';

import { snapshotFixture, multiSourceSnapshotFixture } from './fixtures/reporting-snapshot.mjs';

test('canonical measured GSC values retain fractions, zeros and provenance',()=>{
  const raw=snapshotFixture();raw.sections[0].metrics[0].value=0;
  const model=parseReportingSnapshot(raw);
  assert.equal(model.sections[0].metrics[0].value,0);
  assert.equal(model.sections[0].metrics[2].value,0.1);
  assert.equal(model.sections[0].metrics[2].provenance,'first_party');
  assert.equal(formatMetric(model.sections[0].metrics[2]),'۱۰٪');
});
test('null stays unavailable and never turns into a measured zero',()=>{
  const raw=snapshotFixture();raw.sections[0].metrics[0].value=null;
  const model=parseReportingSnapshot(raw);
  assert.equal(formatMetric(model.sections[0].metrics[0]),'—');
  assert.equal(metricComparison(model,model.sections[0],model.sections[0].metrics[0]).reason,'unavailable');
});
test('unknown schema, malformed period and nonnumeric values fail closed',()=>{
  for(const alter of [x=>x.schemaVersion='future.v2',x=>x.period.end='2026-02-30',x=>x.sections[0].metrics[0].value='20',x=>x.sections[0].metrics[0].value=Infinity,x=>x.sections[0].metrics[2].value=20,x=>x.sections[0].metrics[0].value=-1]){
    const raw=snapshotFixture();alter(raw);assert.equal(parseReportingSnapshot(raw),null);
  }
});
test('unavailable or unknown section states never expose supplied metrics',()=>{
  for(const status of ['unavailable','no_data','future-unknown']){
    const raw=snapshotFixture();raw.sections[0].status=status;
    const model=parseReportingSnapshot(raw);assert.deepEqual(model.sections[0].metrics,[]);
  }
});
test('raw warnings, account identifiers, unknown provider names and extra fields are not projected',()=>{
  const raw=snapshotFixture();raw.sections[0].warning='private upstream value';raw.secret='synthetic-secret';
  raw.sections[0].metrics.push({name:'private_metric',value:999,provider:'private_provider',provenance:'third_party_estimate',dataDate:'2026-09-30'});
  const model=parseReportingSnapshot(raw);const text=JSON.stringify(model);
  for(const privateValue of ['private upstream value','synthetic-secret','synthetic-project','synthetic.invalid','private_provider','private_metric']) assert.ok(!text.includes(privateValue));
});
test('complete like-for-like comparison computes measured relative change',()=>{
  const model=parseReportingSnapshot(snapshotFixture());
  assert.deepEqual(metricComparison(model,model.sections[0],model.sections[0].metrics[0]),{previous:10,difference:10,relativeChange:1,reason:'comparable'});
});
test('partial, mismatched window, definition or zero baseline never implies growth',()=>{
  for(const alter of [x=>x.sections[0].status='partial',x=>x.sections[0].metrics[0].coverage.complete=false,x=>x.comparisonSections[0].metrics[0].coverage.start='2026-09-18',x=>x.comparisonSections[0].metrics[0].provenance='third_party_estimate',x=>x.comparisonSections[0].metrics[0].value=0]){
    const raw=snapshotFixture();alter(raw);const model=parseReportingSnapshot(raw);
    assert.equal(metricComparison(model,model.sections[0],model.sections[0].metrics[0]).relativeChange,null);
  }
});
test('only returned sections are projected; missing acquisition remains missing',()=>{
  const model=parseReportingSnapshot(snapshotFixture());assert.equal(model.sections.length,1);assert.equal(model.sections[0].key,'search');
});
test('required canonical envelope fields cannot be omitted or changed to HTTP weak tags',()=>{
  for(const key of ['generatedAt','requestedAt','etag','correlationId','providerHealth']){
    const raw=snapshotFixture();delete raw[key];assert.equal(parseReportingSnapshot(raw),null);
  }
  const raw=snapshotFixture();raw.etag='W/'+raw.etag;assert.equal(parseReportingSnapshot(raw),null);
});


test('multi-source snapshot keeps same-named metrics separated by provider',()=>{
  const model=parseReportingSnapshot(multiSourceSnapshotFixture());
  const search=model.sections.find(section=>section.key==='search');
  const gscClicks=search.metrics.find(metric=>metric.name==='clicks'&&metric.provider==='gsc');
  const bingClicks=search.metrics.find(metric=>metric.name==='clicks'&&metric.provider==='bing_webmaster');
  const acquisition=model.sections.find(section=>section.key==='acquisition');
  const experience=model.sections.find(section=>section.key==='experience');
  assert.equal(gscClicks.value,20);
  assert.equal(bingClicks.value,6);
  assert.equal(acquisition.metrics.find(metric=>metric.name==='sessions'&&metric.provider==='ga4').value,95);
  assert.equal(experience.metrics.find(metric=>metric.name==='scrollDepth'&&metric.provider==='clarity').value,0.61);
  assert.equal(formatMetric(experience.metrics.find(metric=>metric.name==='engagementTime')),'۷۴٫۵ ثانیه');
});

test('normalized ratio metrics still fail closed above one',()=>{
  const raw=multiSourceSnapshotFixture();
  const experience=raw.sections.find(section=>section.key==='experience');
  experience.metrics.find(metric=>metric.name==='scrollDepth').value=61;
  assert.equal(parseReportingSnapshot(raw),null);
});
