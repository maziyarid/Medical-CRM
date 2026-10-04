/* MAZ//ID · Safe presentation projection of canonical ms-robot.reporting.v1. */
export const SECTION_LABELS = { overview:'نمای کلی', search:'جست‌وجوی گوگل', acquisition:'ورود و بازدید', conversions:'رویدادهای هدف' };
export const STATUS_LABELS = { ok:'داده موجود', stale:'داده قدیمی', partial:'داده ناقص', degraded:'اختلال در منبع', no_data:'داده ثبت نشده', unavailable:'داده در دسترس نیست', unknown:'وضعیت نامشخص' };
export const PROVIDER_LABELS = {gsc:'Google Search Console',ga4:'Google Analytics 4',clarity:'Microsoft Clarity',bing_webmaster:'Bing Webmaster',semrush:'Semrush',mangools:'Mangools',ubersuggest:'Ubersuggest'};
const RULES = {
  clicks:['کلیک','count'],impressions:['نمایش','count'],ctr:['نرخ کلیک','ratio'],averagePosition:['میانگین جایگاه','decimal'],
  users:['کاربران','count'],activeUsers:['کاربران فعال','count'],totalUsers:['کل کاربران','count'],newUsers:['کاربران جدید','count'],sessions:['نشست‌ها','count'],engagedSessions:['نشست‌های دارای تعامل','count'],engagementRate:['نرخ تعامل','ratio'],conversions:['تبدیل‌ها','count'],keyEvents:['رویدادهای کلیدی','count'],
};
const object = value => value !== null && typeof value === 'object' && !Array.isArray(value);
export function validDate(value) {
  if (typeof value !== 'string' || !/^(?!0000)\d{4}-\d{2}-\d{2}$/.test(value)) return false;
  const time=Date.parse(value+'T00:00:00Z');
  return Number.isFinite(time) && new Date(time).toISOString().slice(0,10)===value;
}
function timestamp(value) {
  if (value===null) return null;
  if (validDate(value)) return value;
  if (typeof value!=='string' || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z$/.test(value) || !validDate(value.slice(0,10))) throw Error('Invalid date');
  const time=Date.parse(value);if(!Number.isFinite(time))throw Error('Invalid time');
  return new Date(time).toISOString();
}
function period(value) {
  if(!object(value)||!validDate(value.start)||!validDate(value.end)||value.start>value.end||typeof value.label!=='string'||value.label.length>40)throw Error('Invalid period');
  const days=(Date.parse(value.end+'T00:00:00Z')-Date.parse(value.start+'T00:00:00Z'))/86400000+1;
  if(days<1||days>90)throw Error('Invalid span');
  return {start:value.start,end:value.end};
}
function coverage(value) {
  if(value===undefined)return null;
  if(!object(value)||!validDate(value.start)||!validDate(value.end)||value.start>value.end||typeof value.complete!=='boolean'||!Array.isArray(value.observedDates)||value.observedDates.length>90||!value.observedDates.every(validDate))throw Error('Invalid coverage');
  return {start:value.start,end:value.end,complete:value.complete};
}
function sections(value) {
  if(!Array.isArray(value)||value.length>10)throw Error('Invalid sections');
  const seen=new Set();
  return value.flatMap(section=>{
    if(!object(section)||typeof section.key!=='string')throw Error('Invalid section');
    if(!Object.hasOwn(SECTION_LABELS,section.key))return [];
    if(seen.has(section.key))throw Error('Duplicate section');seen.add(section.key);
    if(!Array.isArray(section.metrics)||section.metrics.length>100)throw Error('Invalid metrics');
    const status=Object.hasOwn(STATUS_LABELS,section.status)?section.status:'unknown';
    const keys=new Set();
    const metrics=section.metrics.flatMap(metric=>{
      if(!object(metric))throw Error('Invalid metric');
      if(!Object.hasOwn(RULES,metric.name)||!Object.hasOwn(PROVIDER_LABELS,metric.provider))return [];
      if(!['first_party','third_party_estimate'].includes(metric.provenance))throw Error('Invalid provenance');
      const identity=metric.name+'|'+metric.provider+'|'+metric.provenance;
      if(keys.has(identity))throw Error('Duplicate metric');keys.add(identity);
      const [label,unit]=RULES[metric.name];const value=metric.value;
      if(value!==null && (typeof value!=='number'||!Number.isFinite(value)||value<0||(unit==='ratio'&&value>1)||(unit==='count'&&!Number.isSafeInteger(value))))throw Error('Invalid value');
      if(metric.dataDate!==null&&!validDate(metric.dataDate))throw Error('Invalid data date');
      return [{name:metric.name,label,unit,value,provider:metric.provider,provenance:metric.provenance,dataDate:metric.dataDate,coverage:coverage(metric.coverage)}];
    });
    return [{key:section.key,label:SECTION_LABELS[section.key],status,freshness:timestamp(section.freshness),lastSyncAt:timestamp(section.lastSyncAt),metrics:['unavailable','no_data','unknown'].includes(status)?[]:metrics}];
  });
}
/** Does not establish tenant authorization: that remains the CRM backend's job. */
export function parseReportingSnapshot(raw) {
  try {
    if(!object(raw)||raw.schemaVersion!=='ms-robot.reporting.v1'||typeof raw.projectId!=='string'||!raw.projectId||raw.projectId.length>80||typeof raw.site!=='string'||!raw.site||raw.site.length>255) return null;
    if(typeof raw.generatedAt!=='string'||typeof raw.requestedAt!=='string'||!raw.generatedAt.includes('T')||!raw.requestedAt.includes('T')||typeof raw.etag!=='string'||!/^"[a-f0-9]{64}"$/i.test(raw.etag)||typeof raw.correlationId!=='string'||!raw.correlationId||raw.correlationId.length>80||!object(raw.providerHealth)||raw.providerHealth.key!=='providerHealth')return null;
    timestamp(raw.requestedAt);
    const current=period(raw.period);const previous=raw.comparison===null?null:period(raw.comparison);
    if(previous && (Date.parse(current.start+'T00:00:00Z')-Date.parse(previous.end+'T00:00:00Z')!==86400000||Date.parse(current.end)-Date.parse(current.start)!==Date.parse(previous.end)-Date.parse(previous.start)))return null;
    const model={period:current,comparison:previous,generatedAt:timestamp(raw.generatedAt),sections:sections(raw.sections),comparisonSections:previous?sections(raw.comparisonSections??[]):[]};
    // Raw warnings, IDs, credentials, unrecognized fields and provider internals
    // never leave this allowlisted projection and are never inserted in the DOM.
    return model;
  } catch { return null; }
}
export function metricComparison(model,section,metric) {
  const result={previous:null,difference:null,relativeChange:null,reason:'unavailable'};
  if(!model.comparison)return result;
  const oldSection=model.comparisonSections.find(item=>item.key===section.key);
  const old=oldSection?.metrics.find(item=>item.name===metric.name&&item.provider===metric.provider&&item.provenance===metric.provenance);
  if(!old||metric.value===null||old.value===null||[section.status,oldSection.status].some(status=>['unavailable','no_data','degraded','unknown'].includes(status)))return result;
  result.previous=old.value;
  if(!metric.coverage?.complete||!old.coverage?.complete||metric.coverage.start!==model.period.start||metric.coverage.end!==model.period.end||old.coverage.start!==model.comparison.start||old.coverage.end!==model.comparison.end||[section.status,oldSection.status].includes('partial'))return {...result,reason:'incomplete'};
  return {...result,difference:metric.value-old.value,relativeChange:old.value===0?null:(metric.value-old.value)/old.value,reason:old.value===0?'zero_baseline':'comparable'};
}
export function formatMetric(metric) {
  if(metric.value===null)return '—';
  const n=new Intl.NumberFormat('fa-IR',{maximumFractionDigits:metric.unit==='count'?0:2});
  return n.format(metric.unit==='ratio'?metric.value*100:metric.value)+(metric.unit==='ratio'?'٪':'');
}
