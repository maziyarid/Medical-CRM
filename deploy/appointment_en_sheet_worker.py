#!/usr/bin/env python3
from __future__ import annotations

import json
import os
import re
import time
import urllib.parse
import urllib.request
from datetime import datetime, timezone
from pathlib import Path

import jwt

SPREADSHEET_ID = "1skaf_j5lkfKY1l3UiwWwgl9M-laW_gllQFMpxOlQuIg"
SHEET_NAME = "SmartFormat"
APP_ROOT = Path("/home/drbastaninejad/public_html/app_private")
PENDING = APP_ROOT / "storage" / "english_pending"
SUBMITTED = APP_ROOT / "storage" / "english_submitted"
HEADERS = [
    "FirstName", "LastName", "FatherName", "TavalodDay", "TavalodMonth", "TavalodYear",
    "HomeTel", "Mobile", "Mobile2", "CodeAshnaei", "CodeBimeh", "CodeMeli", "CodeJob",
    "HomeAd", "Description", "IsTransfer", "drugs", "difficult", "morefmob",
]


def now_iso() -> str:
    return datetime.now(timezone.utc).replace(microsecond=0).isoformat()


def credential_path() -> Path:
    raw = os.environ.get("GOOGLE_APPLICATION_CREDENTIALS", "").strip()
    if not raw:
        raise RuntimeError("GOOGLE_APPLICATION_CREDENTIALS is not configured")
    path = Path(raw)
    if not path.is_file():
        raise RuntimeError("Google service-account credential file is unavailable")
    return path


def access_token() -> str:
    creds = json.loads(credential_path().read_text(encoding="utf-8"))
    now = int(time.time())
    assertion = jwt.encode(
        {
            "iss": creds["client_email"],
            "scope": "https://www.googleapis.com/auth/spreadsheets",
            "aud": "https://oauth2.googleapis.com/token",
            "iat": now,
            "exp": now + 3600,
        },
        creds["private_key"],
        algorithm="RS256",
    )
    body = urllib.parse.urlencode(
        {"grant_type": "urn:ietf:params:oauth:grant-type:jwt-bearer", "assertion": assertion}
    ).encode()
    req = urllib.request.Request(
        "https://oauth2.googleapis.com/token",
        data=body,
        headers={"Content-Type": "application/x-www-form-urlencoded"},
    )
    with urllib.request.urlopen(req, timeout=30) as response:
        return json.loads(response.read())["access_token"]


def api_get(token: str, rng: str) -> list[list[str]]:
    url = (
        f"https://sheets.googleapis.com/v4/spreadsheets/{SPREADSHEET_ID}/values/"
        + urllib.parse.quote(rng, safe="!:")
        + "?majorDimension=ROWS"
    )
    req = urllib.request.Request(url, headers={"Authorization": "Bearer " + token})
    with urllib.request.urlopen(req, timeout=30) as response:
        return json.loads(response.read()).get("values", [])


def api_append(token: str, values: list[str]) -> dict:
    rng = f"{SHEET_NAME}!A:S"
    url = (
        f"https://sheets.googleapis.com/v4/spreadsheets/{SPREADSHEET_ID}/values/"
        + urllib.parse.quote(rng, safe="!:")
        + ":append?valueInputOption=RAW&insertDataOption=INSERT_ROWS"
    )
    body = json.dumps(
        {"range": rng, "majorDimension": "ROWS", "values": [values]},
        ensure_ascii=False,
    ).encode()
    req = urllib.request.Request(
        url,
        data=body,
        method="POST",
        headers={"Authorization": "Bearer " + token, "Content-Type": "application/json; charset=utf-8"},
    )
    with urllib.request.urlopen(req, timeout=30) as response:
        return json.loads(response.read())


def marker(uuid: str) -> str:
    return f"SubmissionRef:{uuid}"


def existing_markers(token: str) -> set[str]:
    rows = api_get(token, f"{SHEET_NAME}!O2:O")
    found: set[str] = set()
    pattern = re.compile(r"SubmissionRef:([0-9a-f]{32})")
    for row in rows:
        if not row:
            continue
        for match in pattern.finditer(str(row[0])):
            found.add(match.group(1))
    return found


def load_record(path: Path) -> dict:
    data = json.loads(path.read_text(encoding="utf-8"))
    if data.get("locale") != "en" or not isinstance(data.get("row"), dict):
        raise RuntimeError("invalid English intake queue record")
    uuid = str(data.get("uuid") or "")
    if not re.fullmatch(r"[0-9a-f]{32}", uuid):
        raise RuntimeError("invalid submission UUID")
    return data


def ensure_contract(token: str) -> None:
    rows = api_get(token, f"{SHEET_NAME}!A1:S1")
    actual = rows[0] if rows else []
    if actual != HEADERS:
        raise RuntimeError("SmartFormat headers do not match the required 19-column contract")


def sync_one(token: str, path: Path, known: set[str]) -> tuple[str, str]:
    record = load_record(path)
    uuid = record["uuid"]
    row = record["row"]
    ref_marker = marker(uuid)
    desc = str(row.get("Description") or "").strip()
    if ref_marker not in desc:
        row["Description"] = " | ".join(x for x in [desc, ref_marker] if x)
    if uuid in known:
        result = {"deduplicated": True}
    else:
        values = [str(row.get(header, "")) for header in HEADERS]
        result = api_append(token, values)
        known.add(uuid)
    record["row"] = row
    record["sheet_synced_at"] = now_iso()
    record["sheet_sync_result"] = result
    SUBMITTED.mkdir(parents=True, exist_ok=True)
    destination = SUBMITTED / path.name
    tmp = path.with_suffix(".json.tmp")
    tmp.write_text(json.dumps(record, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    tmp.replace(path)
    path.replace(destination)
    return uuid, str(result.get("updates", {}).get("updatedRange", ""))


def main() -> int:
    PENDING.mkdir(parents=True, exist_ok=True)
    SUBMITTED.mkdir(parents=True, exist_ok=True)
    token = access_token()
    ensure_contract(token)
    known = existing_markers(token)
    synced = []
    failed = []
    for path in sorted(PENDING.glob("*.json")):
        try:
            uuid, updated_range = sync_one(token, path, known)
            synced.append({"uuid": uuid, "range": updated_range})
        except Exception as exc:
            failed.append({"file": path.name, "error": str(exc)})
    print(json.dumps({"ok": not failed, "synced": synced, "failed": failed, "pending": len(list(PENDING.glob('*.json')))}, ensure_ascii=False))
    return 1 if failed else 0


if __name__ == "__main__":
    raise SystemExit(main())
