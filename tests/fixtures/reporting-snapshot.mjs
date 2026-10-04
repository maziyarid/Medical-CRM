// Synthetic test data only. Never import from production code.
export function snapshotFixture() {
  const period={start:'2026-09-24',end:'2026-09-30',label:'last_7d'};
  const comparison={start:'2026-09-17',end:'2026-09-23',label:'prev_7d'};
  const metric=(name,value,window)=>({name,value,provider:'gsc',provenance:'first_party',dataDate:window.end,coverage:{start:window.start,end:window.end,complete:true,observedDates:[window.end]}});
  const section=(window,multiplier)=>({key:'search',status:'ok',freshness:window.end,lastSyncAt:'2026-10-01T01:00:00Z',warning:null,metrics:[metric('clicks',10*multiplier,window),metric('impressions',100*multiplier,window),metric('ctr',0.1,window),metric('averagePosition',4.5,window)]});
  return {schemaVersion:'ms-robot.reporting.v1',projectId:'synthetic-project',site:'synthetic.invalid',period,comparison,generatedAt:'2026-10-01T01:00:00Z',requestedAt:'2026-10-01T02:00:00Z',correlationId:'synthetic-only',etag:'"'+ 'a'.repeat(64)+'"',sections:[section(period,2)],comparisonSections:[section(comparison,1)],providerHealth:{key:'providerHealth',status:'unavailable',freshness:null,lastSyncAt:null,warning:null,metrics:[]}};
}
