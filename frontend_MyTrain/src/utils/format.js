// Today's date as YYYY-MM-DD in local time (the format <input type="date"> and the API use).
export function today() {
  const now = new Date();
  const pad = (n) => String(n).padStart(2, "0");

  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

// GTFS time "25:10:00" -> "01:10" (GTFS times can go past midnight).
export function formatTime(gtfsTime) {
  if (!gtfsTime) return "";

  const [hours, minutes] = gtfsTime.split(":");
  return `${String(Number(hours) % 24).padStart(2, "0")}:${minutes}`;
}

export function formatDateTime(isoString, lang) {
  if (!isoString) return "";

  return new Date(isoString).toLocaleString(lang, { dateStyle: "medium", timeStyle: "short" });
}

export function formatPrice(amount, currency = "EUR") {
  return `${Number(amount).toFixed(2)} ${currency}`;
}

// GTFS time "08:15:00" -> 495 minutes since midnight. "25:10:00" -> 1510, so past-midnight times still sort last.
export function toMinutes(gtfsTime) {
  const [hours, minutes] = gtfsTime.split(":").map(Number);
  return hours * 60 + minutes;
}
