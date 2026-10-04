/* MAZ//ID · DOM-only rendering of validated reporting projections. */
import { PROVIDER_LABELS, STATUS_LABELS, metricComparison, formatMetric } from './seo-snapshot.mjs';

export function renderReport(container, model) {
  const doc = container.ownerDocument;
  const element = (tag, text, className) => {
    const node = doc.createElement(tag);
    if (text !== undefined) node.textContent = text;
    if (className) node.className = className;
    return node;
  };
  container.replaceChildren();
  const period = model.period.start + ' تا ' + model.period.end;
  container.append(element('p', 'بازه داده: ' + period + ' (UTC)', 'seo-report-period'));
  if (model.comparison) container.append(element('p', 'بازه مقایسه: ' + model.comparison.start + ' تا ' + model.comparison.end + ' (UTC)', 'seo-report-period'));
  const search = model.sections.find(section => section.key === 'search');
  if (search?.metrics.length) {
    const cards = element('div', undefined, 'seo-metric-grid');
    for (const name of ['clicks', 'impressions', 'ctr', 'averagePosition']) {
      const metric = search.metrics.find(item => item.name === name && item.provider === 'gsc');
      if (!metric) continue;
      const card = element('article', undefined, 'seo-metric-card');
      card.append(element('h3', metric.label), element('strong', formatMetric(metric), 'seo-metric-value'), element('p', STATUS_LABELS[search.status]), element('small', PROVIDER_LABELS[metric.provider] + ' · ' + (metric.dataDate || 'تاریخ داده در دسترس نیست')));
      cards.append(card);
    }
    container.append(cards);
  }
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
