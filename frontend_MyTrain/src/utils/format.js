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

// GTFS time + minutes, still as GTFS time ("08:55:00" + 10 -> "09:05:00"; may pass "24:00").
export function addMinutes(gtfsTime, minutes) {
  if (!gtfsTime || !minutes) return gtfsTime;

  const total = toMinutes(gtfsTime) + Number(minutes);
  const pad = (n) => String(n).padStart(2, "0");
  return `${pad(Math.floor(total / 60))}:${pad(total % 60)}:00`;
}

/**
 * Minutes from now until a GTFS time of today's (or the overnight part of yesterday's) timetable.
 * Negative = already passed. "24:40:00" at 00:30 -> 10.
 */
export function minutesFromNow(gtfsTime) {
  const now = new Date();
  let diff = toMinutes(gtfsTime) - (now.getHours() * 60 + now.getMinutes());

  if (diff > 720) diff -= 1440;
  if (diff < -720) diff += 1440;
  return diff;
}

// GTFS time "08:15:00" -> 495 minutes since midnight. "25:10:00" -> 1510, so past-midnight times still sort last.
export function toMinutes(gtfsTime) {
  const [hours, minutes] = gtfsTime.split(":").map(Number);
  return hours * 60 + minutes;
}
