/* MAZ//ID · DOM-only rendering of validated reporting projections. */
import { PROVIDER_LABELS, STATUS_LABELS, metricComparison, formatMetric } from './seo-snapshot.mjs';

const BASELINE_PROVIDERS = ['gsc', 'ga4', 'clarity', 'bing_webmaster'];
const PROVIDER_METRIC_ORDER = {
  gsc:['clicks','impressions','ctr','averagePosition'],
  ga4:['activeUsers','sessions','engagementRate','keyEvents','conversions','views'],
  clarity:['sessions','users','engagementTime','scrollDepth','rageClicks','deadClicks','quickbacks','errorClicks'],
  bing_webmaster:['clicks','impressions','ctr','averagePosition','crawledPages','crawlErrors','indexedPages'],
};

export function renderReport(container, model) {
  const doc = container.ownerDocument;
  const element = (tag, text, className) => {
    const node = doc.createElement(tag);
    if (text !== undefined) node.textContent = text;
    if (className) node.className = className;
    return node;
  };
  const allMetrics = model.sections.flatMap(section => section.metrics.map(metric => ({ section, metric })));
  const providerOrder = [
    ...BASELINE_PROVIDERS,
    ...Object.keys(PROVIDER_LABELS).filter(provider => !BASELINE_PROVIDERS.includes(provider) && allMetrics.some(item => item.metric.provider === provider)),
  ];
  const providerItems = provider => {
    const preferred = PROVIDER_METRIC_ORDER[provider] || [];
    return allMetrics
      .filter(item => item.metric.provider === provider)
      .sort((a,b) => {
        const ai=preferred.indexOf(a.metric.name), bi=preferred.indexOf(b.metric.name);
        return (ai===-1?999:ai)-(bi===-1?999:bi);
      });
  };

  container.replaceChildren();
  const period = model.period.start + ' تا ' + model.period.end;
  container.append(element('p', 'بازه داده: ' + period + ' (UTC)', 'seo-report-period'));
  if (model.comparison) container.append(element('p', 'بازه مقایسه: ' + model.comparison.start + ' تا ' + model.comparison.end + ' (UTC)', 'seo-report-period'));

  const summary = element('section', undefined, 'seo-summary-board');
  summary.setAttribute('aria-labelledby','seo-summary-heading');
  const summaryHead = element('div', undefined, 'seo-summary-heading');
  const summaryText = element('div');
  const summaryTitle = element('h2','نمای کلی چندمنبعی');
  summaryTitle.id='seo-summary-heading';
  summaryText.append(summaryTitle,element('p','اعداد هر منبع جدا نگه داشته می‌شوند تا کلیک، نشست و رفتار کاربر با هم جمع نشوند.','seo-report-note'));
  summaryHead.append(summaryText,element('span','MS Robot · داده تجمیعی و بدون اطلاعات هویتی','seo-summary-badge'));
  summary.append(summaryHead);
  const providerLines = element('div', undefined, 'seo-provider-lines');
  for (const provider of providerOrder) {
    const items = providerItems(provider);
    const lane = element('article', undefined, 'seo-provider-line');
    lane.dataset.provider=provider;
    const laneHead = element('div', undefined, 'seo-provider-line-head');
    const providerName = element('h3', PROVIDER_LABELS[provider] || provider);
    const statuses=[...new Set(items.map(item=>STATUS_LABELS[item.section.status]))];
    laneHead.append(providerName,element('span',items.length?(statuses.join(' · ')||'داده موجود'):'در انتظار داده','seo-provider-status'));
    lane.append(laneHead);
    if (!items.length) {
      lane.classList.add('is-empty');
      lane.append(element('p','برای این منبع در بازه انتخاب‌شده داده‌ای دریافت نشده است.','seo-empty'));
      providerLines.append(lane);
      continue;
    }
    const metrics = element('div', undefined, 'seo-provider-metrics');
    for (const {metric} of items.slice(0,8)) {
      const cell=element('div',undefined,'seo-provider-metric');
      cell.append(element('span',metric.label),element('strong',formatMetric(metric)),element('small',metric.dataDate||'تاریخ نامشخص'));
      metrics.append(cell);
    }
    lane.append(metrics);
    providerLines.append(lane);
  }
  summary.append(providerLines);
  container.append(summary);

  const details = model.sections.filter(section => section.key !== 'overview');
  if (!details.length) container.append(element('p', 'برای بخش‌های مجاز این گزارش، داده‌ای در دسترس نیست.', 'seo-empty'));
  for (const section of details) {
    const block = element('section', undefined, 'seo-report-section');
    block.append(element('h2', section.label + ' · ' + STATUS_LABELS[section.status]));
    block.append(element('p', 'تاریخ داده: ' + (section.freshness || 'نامشخص') + ' · آخرین همگام‌سازی: ' + (section.lastSyncAt || 'نامشخص') + ' (UTC)', 'seo-report-period'));
    if (!section.metrics.length) {
      block.append(element('p', 'مقدار قابل نمایشی برای این بخش دریافت نشده است.', 'seo-empty'));
      container.append(block); continue;
    }
    const scroller = element('div', undefined, 'seo-table-scroll');
    scroller.setAttribute('role', 'region');scroller.setAttribute('aria-label', 'جدول ' + section.label);scroller.tabIndex = 0;
    const table = element('table');table.append(element('caption', section.label + '؛ ' + period));
    const head = element('thead');const headers = element('tr');
    for (const name of ['شاخص', 'مقدار', 'بازه قبل', 'تغییر نسبی', 'اعتبار مقایسه', 'منبع', 'نوع داده', 'تاریخ داده', 'پوشش داده']) {
      const th=element('th',name);th.scope='col';headers.append(th);
    }
    head.append(headers);table.append(head);const body=element('tbody');
    for (const metric of section.metrics) {
      const compare=metricComparison(model,section,metric);
      const reasons={comparable:'قابل مقایسه',incomplete:'پوشش ناقص',unavailable:'در دسترس نیست',zero_baseline:'مبنای قبلی صفر است'};
      const delta=compare.relativeChange===null?'—':new Intl.NumberFormat('fa-IR',{maximumFractionDigits:2,signDisplay:'always'}).format(compare.relativeChange*100)+'٪';
      const row=element('tr');const label=element('th',metric.label);label.scope='row';row.append(label);
      const metricCoverage = metric.coverage ? (metric.coverage.complete ? 'کامل' : 'ناقص') + ' · ' + metric.coverage.start + ' تا ' + metric.coverage.end : 'نامشخص';
      for (const value of [formatMetric(metric),formatMetric({...metric,value:compare.previous}),delta,model.comparison?reasons[compare.reason]:'بدون مقایسه',PROVIDER_LABELS[metric.provider],metric.provenance==='first_party'?'داده مستقیم':'برآورد سرویس',metric.dataDate||'نامشخص',metricCoverage]) row.append(element('td',value));
      body.append(row);
    }
    table.append(body);scroller.append(table);block.append(scroller);container.append(block);
  }
  container.append(element('p', 'علامت — یعنی مقدار یا مقایسه معتبر در دسترس نیست. تغییر جایگاه به‌تنهایی نشان‌دهنده بهتر یا بدتر شدن عملکرد نیست.', 'seo-report-note'));
}
