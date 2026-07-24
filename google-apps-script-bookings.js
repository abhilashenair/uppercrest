const SHEET_ID = "1h9MvSrn-GdQD_z4Jw2QJfUHelJSjGukHSCuZcG3QxHA";
const SHEET_NAME = "Sheet1";
const DEFAULT_HEADERS = ["Timestamp", "Booking ID", "Name", "Phone", "From Date", "To Date", "Status"];
const STATUS_VALUES = ["Pending", "Confirmed", "Cancelled"];

function doGet() {
  const sheet = getSheet();
  setupSheet(sheet);
  const values = sheet.getDataRange().getValues();
  const headerRow = values.shift() || [];
  const headers = headerRow.map(normalizeHeader);

  const bookings = values
    .map(rowToObject(headers))
    .filter(row => normalizeStatus(row.status).toLowerCase() === "confirmed")
    .map(row => ({
      booking_id: row.booking_id || "",
      check_in: toIsoDate(row.from_date || row.check_in),
      check_out: toIsoDate(row.to_date || row.check_out),
      status: "confirmed"
    }))
    .filter(row => row.check_in && row.check_out);

  return json({ ok: true, bookedDates: bookings, bookings: bookings });
}

function doPost(e) {
  const sheet = getSheet();
  setupSheet(sheet);
  const data = JSON.parse((e && e.postData && e.postData.contents) || "{}");
  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0].map(normalizeHeader);
  const row = headers.map(header => valueForHeader(header, data));
  const existingRow = findRowByBookingId(sheet, headers, data.booking_id);

  if (existingRow) {
    sheet.getRange(existingRow, 1, 1, row.length).setValues([mergeRows(sheet, headers, existingRow, row)]);
  } else {
    sheet.appendRow(row);
  }

  return json({ ok: true, booking_id: data.booking_id || valueFromRow(headers, row, "booking_id") });
}

function setupReservationSheet() {
  const sheet = getSheet();
  setupSheet(sheet);
  protectStatusColumn(sheet);
  return json({ ok: true, message: "Reservation sheet setup complete." });
}

function getSheet() {
  const spreadsheet = SpreadsheetApp.openById(SHEET_ID);
  return spreadsheet.getSheetByName(SHEET_NAME) || spreadsheet.insertSheet(SHEET_NAME);
}

function setupSheet(sheet) {
  ensureHeaders(sheet);
  formatDateColumns(sheet);
  addStatusDropdown(sheet);
}

function ensureHeaders(sheet) {
  if (sheet.getLastRow() === 0) {
    sheet.appendRow(DEFAULT_HEADERS);
  }

  const headerRange = sheet.getRange(1, 1, 1, Math.max(sheet.getLastColumn(), DEFAULT_HEADERS.length));
  const headers = headerRange.getValues()[0];
  if (headers.every(cell => cell === "")) {
    sheet.getRange(1, 1, 1, DEFAULT_HEADERS.length).setValues([DEFAULT_HEADERS]);
  }

  const normalized = sheet.getRange(1, 1, 1, Math.max(sheet.getLastColumn(), DEFAULT_HEADERS.length)).getValues()[0].map(normalizeHeader);
  DEFAULT_HEADERS.forEach(function(header) {
    if (normalized.indexOf(normalizeHeader(header)) === -1) {
      sheet.getRange(1, sheet.getLastColumn() + 1).setValue(header);
    }
  });

  sheet.getRange(1, 1, 1, sheet.getLastColumn()).setFontWeight("bold");
  sheet.setFrozenRows(1);
}

function addStatusDropdown(sheet) {
  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0].map(normalizeHeader);
  const statusCol = headers.indexOf("status") + 1;
  if (!statusCol) return;
  const rule = SpreadsheetApp.newDataValidation()
    .requireValueInList(STATUS_VALUES, true)
    .setAllowInvalid(false)
    .build();
  sheet.getRange(2, statusCol, Math.max(sheet.getMaxRows() - 1, 1), 1).setDataValidation(rule);
}

function formatDateColumns(sheet) {
  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0].map(normalizeHeader);
  ["from_date", "to_date"].forEach(function(header) {
    const col = headers.indexOf(header) + 1;
    if (col) sheet.getRange(2, col, Math.max(sheet.getMaxRows() - 1, 1), 1).setNumberFormat("dd/mm/yyyy");
  });
}

function protectStatusColumn(sheet) {
  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0].map(normalizeHeader);
  const statusCol = headers.indexOf("status") + 1;
  if (!statusCol) return;

  sheet.getProtections(SpreadsheetApp.ProtectionType.RANGE)
    .filter(protection => protection.getDescription() === "Upper Crest Status Column")
    .forEach(protection => protection.remove());

  const protection = sheet.getRange(1, statusCol, sheet.getMaxRows(), 1).protect();
  protection.setDescription("Upper Crest Status Column");
  protection.setWarningOnly(false);
  const owner = Session.getEffectiveUser();
  protection.addEditor(owner);
  protection.getEditors().forEach(function(editor) {
    if (editor.getEmail() !== owner.getEmail()) {
      protection.removeEditor(editor);
    }
  });
}

function findRowByBookingId(sheet, headers, bookingId) {
  const bookingIdColumn = headers.indexOf("booking_id");
  if (bookingIdColumn === -1 || !bookingId) return 0;

  const lastRow = sheet.getLastRow();
  if (lastRow < 2) return 0;

  const values = sheet.getRange(2, bookingIdColumn + 1, lastRow - 1, 1).getValues();
  for (let index = 0; index < values.length; index++) {
    if (String(values[index][0]).trim() === String(bookingId).trim()) {
      return index + 2;
    }
  }
  return 0;
}

function mergeRows(sheet, headers, rowNumber, nextRow) {
  const currentRow = sheet.getRange(rowNumber, 1, 1, nextRow.length).getValues()[0];
  return nextRow.map(function(nextValue, index) {
    const header = headers[index];
    if (header === "timestamp" || header === "created_at" || header === "date") return currentRow[index] || nextValue;
    return nextValue !== "" ? nextValue : currentRow[index];
  });
}

function valueForHeader(header, data) {
  const fromDate = toSheetDate(data.from_date || data.check_in || "");
  const toDate = toSheetDate(data.to_date || data.check_out || "");
  const bookingId = data.booking_id || createBookingId();

  switch (header) {
    case "timestamp":
    case "created_at":
    case "date":
      return new Date();
    case "booking_id":
    case "bookingid":
    case "id":
      return bookingId;
    case "name":
    case "guest_name":
      return data.name || "";
    case "phone":
    case "mobile":
    case "whatsapp":
    case "phone_whatsapp":
      return data.phone || "";
    case "from_date":
    case "check_in":
    case "checkin":
    case "start_date":
      return fromDate;
    case "to_date":
    case "check_out":
    case "checkout":
    case "end_date":
      return toDate;
    case "status":
    case "booking_status":
      return normalizeStatus(data.status || "Pending", "Pending");
    default:
      return "";
  }
}

function valueFromRow(headers, row, header) {
  const index = headers.indexOf(header);
  return index === -1 ? "" : row[index];
}

function createBookingId() {
  const now = new Date();
  const stamp = Utilities.formatDate(now, Session.getScriptTimeZone(), "yyyyMMddHHmmss");
  const random = Math.random().toString(36).slice(2, 6).toUpperCase();
  return "UC-" + stamp + "-" + random;
}

function normalizeStatus(status, fallback) {
  const value = String(status || fallback || "").trim().toLowerCase();
  if (value === "confirmed") return "Confirmed";
  if (value === "cancelled" || value === "canceled") return "Cancelled";
  if (value === "pending") return "Pending";
  return fallback || value;
}

function normalizeHeader(header) {
  return String(header || "")
    .trim()
    .toLowerCase()
    .replace(/\s+/g, "_");
}

function rowToObject(headers) {
  return function(row) {
    return headers.reduce(function(obj, header, index) {
      obj[header] = row[index];
      return obj;
    }, {});
  };
}

function toIsoDate(value) {
  if (!value) return "";
  if (value instanceof Date) {
    return Utilities.formatDate(value, Session.getScriptTimeZone(), "yyyy-MM-dd");
  }
  const text = String(value).trim();
  const ddmmyyyy = text.match(/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/);
  if (ddmmyyyy) {
    return ddmmyyyy[3] + "-" + String(ddmmyyyy[2]).padStart(2, "0") + "-" + String(ddmmyyyy[1]).padStart(2, "0");
  }
  const date = new Date(value);
  return isNaN(date.getTime()) ? "" : Utilities.formatDate(date, Session.getScriptTimeZone(), "yyyy-MM-dd");
}

function toSheetDate(value) {
  if (!value) return "";
  if (value instanceof Date) {
    return Utilities.formatDate(value, Session.getScriptTimeZone(), "dd/MM/yyyy");
  }
  const text = String(value).trim();
  const iso = text.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (iso) return iso[3] + "/" + iso[2] + "/" + iso[1];
  const ddmmyyyy = text.match(/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/);
  if (ddmmyyyy) return String(ddmmyyyy[1]).padStart(2, "0") + "/" + String(ddmmyyyy[2]).padStart(2, "0") + "/" + ddmmyyyy[3];
  const date = new Date(value);
  return isNaN(date.getTime()) ? text : Utilities.formatDate(date, Session.getScriptTimeZone(), "dd/MM/yyyy");
}

function json(data) {
  return ContentService
    .createTextOutput(JSON.stringify(data))
    .setMimeType(ContentService.MimeType.JSON);
}
