from fastapi import FastAPI, APIRouter, HTTPException
from dotenv import load_dotenv
from starlette.middleware.cors import CORSMiddleware
from motor.motor_asyncio import AsyncIOMotorClient
import os
import logging
from pathlib import Path
from pydantic import BaseModel, Field, ConfigDict
from typing import List, Optional
import uuid
from datetime import datetime, timezone

ROOT_DIR = Path(__file__).parent
load_dotenv(ROOT_DIR / '.env')

mongo_url = os.environ['MONGO_URL']
client = AsyncIOMotorClient(mongo_url)
db = client[os.environ['DB_NAME']]

app = FastAPI(title="Dr. Bastaninejad Clinic API")
api_router = APIRouter(prefix="/api")


# ---------- Models ----------
class Appointment(BaseModel):
    model_config = ConfigDict(extra="ignore")
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    full_name: str
    phone: str
    national_id: Optional[str] = None
    service: str
    preferred_date: Optional[str] = None
    notes: Optional[str] = None
    created_at: str = Field(default_factory=lambda: datetime.now(timezone.utc).isoformat())


class AppointmentCreate(BaseModel):
    full_name: str
    phone: str
    national_id: Optional[str] = None
    service: str
    preferred_date: Optional[str] = None
    notes: Optional[str] = None


class ContactMessage(BaseModel):
    model_config = ConfigDict(extra="ignore")
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    name: str
    phone: str
    email: Optional[str] = None
    message: str
    created_at: str = Field(default_factory=lambda: datetime.now(timezone.utc).isoformat())


class ContactCreate(BaseModel):
    name: str
    phone: str
    email: Optional[str] = None
    message: str


# ---------- Google Sheets placeholder ----------
# TODO(client): Enable Google Sheets sync for submissions.
# The client will wire this up later. Steps:
#   1) pip install gspread google-auth
#   2) Add a service-account JSON + SHEET_ID to backend/.env
#   3) Uncomment append_to_sheet() below and call it inside the create endpoints.
#
# import gspread
# from google.oauth2.service_account import Credentials
# def append_to_sheet(worksheet_name: str, row: list):
#     creds = Credentials.from_service_account_file(
#         os.environ["GOOGLE_SA_JSON"],
#         scopes=["https://www.googleapis.com/auth/spreadsheets"],
#     )
#     gc = gspread.authorize(creds)
#     sh = gc.open_by_key(os.environ["SHEET_ID"])
#     sh.worksheet(worksheet_name).append_row(row)


@api_router.get("/")
async def root():
    return {"message": "Dr. Bastaninejad Clinic API"}


@api_router.post("/appointments", response_model=Appointment)
async def create_appointment(payload: AppointmentCreate):
    if not payload.full_name.strip() or not payload.phone.strip():
        raise HTTPException(status_code=422, detail="name and phone are required")
    obj = Appointment(**payload.model_dump())
    await db.appointments.insert_one(obj.model_dump())
    # append_to_sheet("Appointments", [obj.created_at, obj.full_name, obj.phone, obj.service, obj.preferred_date or "", obj.notes or ""])
    return obj


@api_router.get("/appointments", response_model=List[Appointment])
async def list_appointments():
    docs = await db.appointments.find({}, {"_id": 0}).sort("created_at", -1).to_list(500)
    return docs


@api_router.post("/contact", response_model=ContactMessage)
async def create_contact(payload: ContactCreate):
    obj = ContactMessage(**payload.model_dump())
    await db.contacts.insert_one(obj.model_dump())
    # append_to_sheet("Contacts", [obj.created_at, obj.name, obj.phone, obj.email or "", obj.message])
    return obj


@api_router.get("/contact", response_model=List[ContactMessage])
async def list_contacts():
    docs = await db.contacts.find({}, {"_id": 0}).sort("created_at", -1).to_list(500)
    return docs


app.include_router(api_router)

app.add_middleware(
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=os.environ.get('CORS_ORIGINS', '*').split(','),
    allow_methods=["*"],
    allow_headers=["*"],
)

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)


@app.on_event("shutdown")
async def shutdown_db_client():
    client.close()
