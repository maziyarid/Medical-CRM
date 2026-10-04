/* MAZ//ID · Dr. Shahin Bastaninejad · Read-only SEO integration boundary. */
import { parseReportingSnapshot, validDate } from './seo-snapshot.mjs';
const PERIODS = new Set(['last_7d', 'last_14d', 'last_28d', 'last_30d', 'last_90d']);
const QUERY_KEYS = new Set(['period', 'comparison', 'endDate']);

export function buildSeoQuery(input = {}) {
  if (!input || typeof input !== 'object' || Array.isArray(input) || Object.keys(input).some(key => !QUERY_KEYS.has(key))) throw Error('Invalid report query');
  const period = input.period ?? 'last_28d';
  const comparison = input.comparison ?? 'previous';
  if (!PERIODS.has(period) || !['previous', 'none'].includes(comparison)) throw Error('Invalid report window');
  const query = new URLSearchParams({ period, comparison });
  if (input.endDate !== undefined && input.endDate !== '') {
    const date = input.endDate;
    const time = validDate(date) ? Date.parse(date + 'T00:00:00Z') : NaN;
    if (!Number.isFinite(time) || new Date(time).toISOString().slice(0, 10) !== date || date > new Date().toISOString().slice(0, 10)) throw Error('Invalid report date');
    query.set('endDate', date);
  }
  const closing = query.get('endDate') || new Date().toISOString().slice(0, 10);
  const days = Number(period.match(/\d+/)[0]);
  const first = new Date(Date.parse(closing + 'T00:00:00Z') - ((comparison === 'previous' ? 2 * days : days) - 1) * 86400000).toISOString().slice(0, 10);
  if (!validDate(first)) throw Error('Invalid derived reporting date');
  return query.toString();
}

/** The staff token may reach only the existing CRM API; never MS Robot/Google. */
export function createSeoClient({ base, token, fetcher = globalThis.fetch }) {
  const parsed = new URL(base);
  if (parsed.protocol !== 'https:' || !['dashboard.drbastaninejad.com', 'app.drbastaninejad.com'].includes(parsed.hostname) || parsed.port || parsed.username || parsed.password || parsed.search || parsed.hash || parsed.pathname.replace(/\/$/, '') !== '/api/v1') throw Error('Invalid CRM API origin');
  const endpoint = parsed.origin + '/api/v1/analytics/seo';
  return {
    async read(input = {}, signal) {
      const query = buildSeoQuery(input);
      const staffToken = token();
      if (!staffToken) return { state: 'unauthorized' };
      try {
        const response = await fetcher(endpoint + '?' + query, {
          method: 'GET', credentials: 'omit', cache: 'no-store', redirect: 'error', signal,
          headers: { Accept: 'application/json', Authorization: 'Bearer ' + staffToken },
        });
        if (response.status === 401) return { state: 'unauthorized' };
        if (response.status === 403) return { state: 'forbidden' };
        if (response.status === 422 || response.status === 400) return { state: 'invalid_request' };
        const body = await response.json();
        if (response.status === 503 && body?.ok === false && body?.data === null && body?.meta?.code === 'reporting_unconfigured') return { state: 'unconfigured' };
        if (response.status === 200 && body?.ok === true) {
          const report = parseReportingSnapshot(body.data);
          const params = new URLSearchParams(query);
          const end = params.get('endDate') || new Date().toISOString().slice(0, 10);
          const days = Number(params.get('period').match(/\d+/)[0]);
          const start = new Date(Date.parse(end + 'T00:00:00Z') - (days - 1) * 86400000).toISOString().slice(0, 10);
          const comparisonExpected = params.get('comparison') === 'previous';
          if (report && report.period.start === start && report.period.end === end && Boolean(report.comparison) === comparisonExpected) return { state: 'ready', report };
        }
        // Unknown schemas and malformed successful responses fail closed.
        return { state: 'unavailable' };
      } catch (error) {
        if (signal?.aborted) throw error;
        return { state: 'unavailable' };
      }
    },
    async logout(clear, signal) {
      const staffToken = token();
      clear();
      if (!staffToken) return true;
      try {
        const response = await fetcher(parsed.origin + '/api/v1/auth/logout', {
          method: 'POST', credentials: 'omit', cache: 'no-store', redirect: 'error', signal,
          headers: { Accept: 'application/json', Authorization: 'Bearer ' + staffToken },
        });
        return response.status >= 200 && response.status < 300;
      } catch { return false; }
    },
  };
}
