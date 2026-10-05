// Synthetic test data only. Never import from production code.
export function snapshotFixture() {
  const period={start:'2026-09-24',end:'2026-09-30',label:'last_7d'};
  const comparison={start:'2026-09-17',end:'2026-09-23',label:'prev_7d'};
  const metric=(name,value,window)=>({name,value,provider:'gsc',provenance:'first_party',dataDate:window.end,coverage:{start:window.start,end:window.end,complete:true,observedDates:[window.end]}});
  const section=(window,multiplier)=>({key:'search',status:'ok',freshness:window.end,lastSyncAt:'2026-10-01T01:00:00Z',warning:null,metrics:[metric('clicks',10*multiplier,window),metric('impressions',100*multiplier,window),metric('ctr',0.1,window),metric('averagePosition',4.5,window)]});
  return {schemaVersion:'ms-robot.reporting.v1',projectId:'synthetic-project',site:'synthetic.invalid',period,comparison,generatedAt:'2026-10-01T01:00:00Z',requestedAt:'2026-10-01T02:00:00Z',correlationId:'synthetic-only',etag:'"'+ 'a'.repeat(64)+'"',sections:[section(period,2)],comparisonSections:[section(comparison,1)],providerHealth:{key:'providerHealth',status:'unavailable',freshness:null,lastSyncAt:null,warning:null,metrics:[]}};
}


export function multiSourceSnapshotFixture() {
  const raw=snapshotFixture();
  const current=raw.period, previous=raw.comparison;
  const metric=(name,value,provider,window)=>({name,value,provider,provenance:'first_party',dataDate:window.end,coverage:{start:window.start,end:window.end,complete:true,observedDates:[window.end]}});
  const section=(key,window,metrics)=>({key,status:'ok',freshness:window.end,lastSyncAt:'2026-10-01T01:00:00Z',warning:null,metrics});

  raw.sections[0].metrics.push(
    metric('clicks',6,'bing_webmaster',current),
    metric('impressions',90,'bing_webmaster',current),
    metric('ctr',0.0667,'bing_webmaster',current),
    metric('averagePosition',6.2,'bing_webmaster',current)
  );
  raw.comparisonSections[0].metrics.push(
    metric('clicks',3,'bing_webmaster',previous),
    metric('impressions',60,'bing_webmaster',previous),
    metric('ctr',0.05,'bing_webmaster',previous),
    metric('averagePosition',7.1,'bing_webmaster',previous)
  );
  raw.sections.push(
    section('acquisition',current,[
      metric('activeUsers',70,'ga4',current),
      metric('sessions',95,'ga4',current),
      metric('engagementRate',0.68,'ga4',current),
      metric('keyEvents',12,'ga4',current)
    ]),
    section('experience',current,[
      metric('sessions',81,'clarity',current),
      metric('users',62,'clarity',current),
      metric('engagementTime',74.5,'clarity',current),
      metric('scrollDepth',0.61,'clarity',current),
      metric('rageClicks',4,'clarity',current),
      metric('deadClicks',7,'clarity',current)
    ])
  );
  raw.comparisonSections.push(
    section('acquisition',previous,[
      metric('activeUsers',55,'ga4',previous),
      metric('sessions',80,'ga4',previous),
      metric('engagementRate',0.62,'ga4',previous),
      metric('keyEvents',8,'ga4',previous)
    ]),
    section('experience',previous,[
      metric('sessions',72,'clarity',previous),
      metric('users',58,'clarity',previous),
      metric('engagementTime',66,'clarity',previous),
      metric('scrollDepth',0.54,'clarity',previous),
      metric('rageClicks',6,'clarity',previous),
      metric('deadClicks',9,'clarity',previous)
    ])
  );
  raw.etag='"'+ 'b'.repeat(64)+'"';
  return raw;
}
