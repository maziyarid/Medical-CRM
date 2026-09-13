#!/usr/bin/env python3
import hmac, json, os, sys
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import urlparse, parse_qs
from google.oauth2 import service_account
from googleapiclient.discovery import build
from googleapiclient.errors import HttpError

HOST=os.environ.get('DRB_GOOGLE_BRIDGE_HOST','127.0.0.1')
PORT=int(os.environ.get('DRB_GOOGLE_BRIDGE_PORT','9118'))
TOKEN=os.environ.get('DRB_GOOGLE_BRIDGE_TOKEN','')
CREDENTIALS=os.environ.get('DRB_GOOGLE_CREDENTIALS','/srv/maziyar-wp-mcp/state/secrets/google-medical-service-account.json')
SPREADSHEET_ID=os.environ.get('DRB_BOOKING_SPREADSHEET_ID','')
SHEET_NAME=os.environ.get('DRB_BOOKING_SHEET_NAME','ScheduledVisits')
HEADERS=[
 'VisitUUID','BookingID','AppointmentID','PatientID','PatientName','Mobile','ScheduledAtTehran','DurationMinutes',
 'BookingSource','RegistrationChannel','RegisteredByUserID','RegisteredByName','RegisteredByRole','PaymentStatus',
 'PaymentGateway','AmountRials','PaymentReference','PaymentVerifiedAt','ConfirmationStatus','StaffFollowupRequired',
 'OpenDayID','CalendarEventID','CreatedAtUTC','ConfirmedAtUTC','UpdatedAtUTC','LastSyncedAtUTC','Notes'
]
SCOPES=['https://www.googleapis.com/auth/spreadsheets','https://www.googleapis.com/auth/calendar']
_creds=service_account.Credentials.from_service_account_file(CREDENTIALS,scopes=SCOPES)
_sheets=build('sheets','v4',credentials=_creds,cache_discovery=False)
_calendar=build('calendar','v3',credentials=_creds,cache_discovery=False)

def auth_ok(header):
    expected='Bearer '+TOKEN
    return bool(TOKEN) and hmac.compare_digest(header or '',expected)

def safe_cell(v):
    if v is None: return ''
    s=str(v)[:5000]
    if s.startswith(('=','+','-','@')): s="'"+s
    return s

def sheet_upsert(body):
    if not SPREADSHEET_ID: raise RuntimeError('spreadsheet_not_configured')
    visit=str(body.get('visit_uuid','')).strip()
    row=body.get('row') or {}
    if not visit or len(visit)>64: raise ValueError('invalid_visit_uuid')
    values=[safe_cell(row.get(h,'')) for h in HEADERS]
    if values[0] != visit: values[0]=visit
    col=_sheets.spreadsheets().values().get(spreadsheetId=SPREADSHEET_ID,range=f"'{SHEET_NAME}'!A2:A").execute().get('values',[])
    rownum=None
    for i,r in enumerate(col,start=2):
        if r and str(r[0]).strip()==visit:
            rownum=i; break
    if rownum is None:
        result=_sheets.spreadsheets().values().append(spreadsheetId=SPREADSHEET_ID,range=f"'{SHEET_NAME}'!A:AA",valueInputOption='RAW',insertDataOption='INSERT_ROWS',body={'values':[values]}).execute()
        return {'ok':True,'idempotent':False,'updatedRange':result.get('updates',{}).get('updatedRange')}
    result=_sheets.spreadsheets().values().update(spreadsheetId=SPREADSHEET_ID,range=f"'{SHEET_NAME}'!A{rownum}:AA{rownum}",valueInputOption='RAW',body={'values':[values]}).execute()
    return {'ok':True,'idempotent':True,'updatedRange':result.get('updatedRange')}

def calendar_upsert(body):
    cid=str(body.get('calendar_id','')).strip(); eid=str(body.get('event_id','')).strip(); event=body.get('event') or {}
    if not cid or not eid: raise ValueError('calendar_id_and_event_id_required')
    try:
        saved=_calendar.events().update(calendarId=cid,eventId=eid,body=event).execute()
    except HttpError as e:
        if getattr(e.resp,'status',0)!=404: raise
        payload=dict(event); payload['id']=eid
        saved=_calendar.events().insert(calendarId=cid,body=payload).execute()
    return {'ok':True,'event':saved}

def calendar_delete(body):
    cid=str(body.get('calendar_id','')).strip(); eid=str(body.get('event_id','')).strip()
    if not cid or not eid: raise ValueError('calendar_id_and_event_id_required')
    try: _calendar.events().delete(calendarId=cid,eventId=eid).execute()
    except HttpError as e:
        if getattr(e.resp,'status',0) not in (404,410): raise
    return {'ok':True}

def calendar_changes(body):
    cid=str(body.get('calendar_id','')).strip(); token=body.get('sync_token') or None
    if not cid: raise ValueError('calendar_id_required')
    events=[]; page=None; next_sync=token
    while True:
        kwargs={'calendarId':cid,'maxResults':2500,'showDeleted':True}
        if token: kwargs['syncToken']=token
        else: kwargs['singleEvents']=True
        if page: kwargs['pageToken']=page
        try: res=_calendar.events().list(**kwargs).execute()
        except HttpError as e:
            if getattr(e.resp,'status',0)==410 and token:
                return {'ok':True,'events':[],'next_sync_token':None,'reset':True}
            raise
        events.extend(res.get('items',[])); page=res.get('nextPageToken'); next_sync=res.get('nextSyncToken',next_sync)
        if not page: break
    return {'ok':True,'events':events,'next_sync_token':next_sync,'reset':False}

class Handler(BaseHTTPRequestHandler):
    server_version='DRBGoogleBridge/1.0'
    def log_message(self,fmt,*args): sys.stderr.write('%s - %s\n'%(self.address_string(),fmt%args))
    def reply(self,status,payload):
        raw=json.dumps(payload,ensure_ascii=False,separators=(',',':')).encode(); self.send_response(status); self.send_header('Content-Type','application/json; charset=utf-8'); self.send_header('Content-Length',str(len(raw))); self.end_headers(); self.wfile.write(raw)
    def do_GET(self):
        if not auth_ok(self.headers.get('Authorization')): return self.reply(401,{'ok':False,'error':'unauthorised'})
        if self.path.split('?',1)[0]!='/health': return self.reply(404,{'ok':False,'error':'not_found'})
        sheet=False
        try:
            m=_sheets.spreadsheets().get(spreadsheetId=SPREADSHEET_ID,fields='sheets.properties.title').execute(); sheet=any(x.get('properties',{}).get('title')==SHEET_NAME for x in m.get('sheets',[]))
        except Exception: pass
        self.reply(200,{'ok':True,'sheet_access':sheet,'calendar_bridge':True})
    def do_POST(self):
        if not auth_ok(self.headers.get('Authorization')): return self.reply(401,{'ok':False,'error':'unauthorised'})
        try:
            n=int(self.headers.get('Content-Length','0'))
            if n<1 or n>131072: return self.reply(413,{'ok':False,'error':'invalid_payload_size'})
            body=json.loads(self.rfile.read(n))
            path=self.path.split('?',1)[0]
            if path=='/sheet/upsert': out=sheet_upsert(body)
            elif path=='/calendar/upsert': out=calendar_upsert(body)
            elif path=='/calendar/delete': out=calendar_delete(body)
            elif path=='/calendar/changes': out=calendar_changes(body)
            else: return self.reply(404,{'ok':False,'error':'not_found'})
            self.reply(200,out)
        except ValueError as e: self.reply(422,{'ok':False,'error':str(e)})
        except HttpError as e: self.reply(502,{'ok':False,'error':'google_api_error','status':getattr(e.resp,'status',0)})
        except Exception as e: self.reply(500,{'ok':False,'error':'bridge_error'})

if __name__=='__main__':
    if not TOKEN: raise SystemExit('DRB_GOOGLE_BRIDGE_TOKEN is required')
    ThreadingHTTPServer((HOST,PORT),Handler).serve_forever()
