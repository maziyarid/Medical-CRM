const BOOKING_HEADERS = Object.freeze([
  'FirstName', 'LastName', 'FatherName', 'TavalodDay', 'TavalodMonth', 'TavalodYear',
  'HomeTel', 'Mobile', 'Mobile2', 'CodeAshnaei', 'CodeBimeh', 'CodeMeli', 'CodeJob',
  'HomeAd', 'Description', 'IsTransfer', 'drugs', 'difficult', 'morefmob'
]);

function doGet() {
  return json_({ok: true, service: 'booking-sheet-bridge'});
}

function doPost(e) {
  const lock = LockService.getScriptLock();
  try {
    try {
      lock.waitLock(20000);
    } catch (lockError) {
      return json_({ok: false, error: 'lock_unavailable', retryable: true});
    }
    const body = parseBody_(e);
    const props = PropertiesService.getScriptProperties();
    const expectedSecret = String(props.getProperty('SHARED_SECRET') || '');
    if (!expectedSecret || !constantTimeEqual_(String(body.secret || ''), expectedSecret)) {
      return json_({ok: false, error: 'unauthorised'});
    }
    const submissionUuid = String(body.submission_uuid || '').trim();
    if (!/^[A-Za-z0-9._:-]{1,64}$/.test(submissionUuid)) {
      return json_({ok: false, error: 'invalid_submission_uuid'});
    }
    const sheet = bookingSheet_(props);
    assertHeaders_(sheet);
    const prior = sheet.createDeveloperMetadataFinder()
      .withKey('submission_uuid').withValue(submissionUuid).find();
    if (prior.length) {
      const location = prior[0].getLocation().getRange();
      return json_({ok: true, idempotent: true, row: location ? location.getRow() : null});
    }
    const row = body.row || {};
    const values = BOOKING_HEADERS.map(function (header) {
      const value = Object.prototype.hasOwnProperty.call(row, header) ? row[header] : '';
      return safeCell_(value);
    });
    const nextRow = Math.max(2, sheet.getLastRow() + 1);
    const target = sheet.getRange(nextRow, 1, 1, BOOKING_HEADERS.length);
    target.setValues([values]);
    target.addDeveloperMetadata('submission_uuid', submissionUuid, SpreadsheetApp.DeveloperMetadataVisibility.PROJECT);
    SpreadsheetApp.flush();
    return json_({ok: true, idempotent: false, row: nextRow});
  } catch (error) {
    console.error('Booking bridge error: ' + String(error && error.message ? error.message : error));
    return json_({ok: false, error: 'internal_error'});
  } finally {
    try { lock.releaseLock(); } catch (ignore) {}
  }
}

function safeCell_(value) {
  if (value === null || value === undefined) return '';
  const text = String(value).slice(0, 5000);
  // Google Sheets treats leading formula markers as executable formulas. An
  // apostrophe preserves the patient's text without exposing staff to CSV/
  // spreadsheet formula injection.
  return /^[=+\-@]/.test(text) ? "'" + text : text;
}

function setupBookingSheet() {
  const props = PropertiesService.getScriptProperties();
  const sheet = bookingSheet_(props);
  if (sheet.getLastRow() === 0) {
    sheet.getRange(1, 1, 1, BOOKING_HEADERS.length).setValues([BOOKING_HEADERS]);
    sheet.setFrozenRows(1);
  }
  assertHeaders_(sheet);
  return {spreadsheetId: sheet.getParent().getId(), sheetName: sheet.getName()};
}

function bookingSheet_(props) {
  const spreadsheetId = String(props.getProperty('SPREADSHEET_ID') || '').trim();
  const sheetName = String(props.getProperty('SHEET_NAME') || 'SmartFormat').trim();
  if (!spreadsheetId) throw new Error('SPREADSHEET_ID is not configured');
  const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
  if (!sheet) throw new Error('Configured sheet was not found');
  return sheet;
}

function assertHeaders_(sheet) {
  const actual = sheet.getRange(1, 1, 1, BOOKING_HEADERS.length).getDisplayValues()[0];
  if (actual.join('\u001f') !== BOOKING_HEADERS.join('\u001f')) {
    throw new Error('SmartFormat headers do not match the required 19-column contract');
  }
}

function parseBody_(e) {
  const raw = e && e.postData && e.postData.contents ? e.postData.contents : '';
  if (raw) {
    try { return JSON.parse(raw); } catch (ignore) {}
  }
  if (e && e.parameter && e.parameter.payload) {
    const parsed = JSON.parse(e.parameter.payload);
    parsed.secret = e.parameter.secret || parsed.secret;
    return parsed;
  }
  return e && e.parameter ? e.parameter : {};
}

function constantTimeEqual_(left, right) {
  if (left.length !== right.length) return false;
  let mismatch = 0;
  for (let i = 0; i < left.length; i++) mismatch |= left.charCodeAt(i) ^ right.charCodeAt(i);
  return mismatch === 0;
}

function json_(payload) {
  return ContentService.createTextOutput(JSON.stringify(payload))
    .setMimeType(ContentService.MimeType.JSON);
}
