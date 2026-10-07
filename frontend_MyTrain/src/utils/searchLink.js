/**
 * Link to the home page train search with stations (and optionally date/time) filled in.
 * Stations are { stop_id, stop_name }. With both "from" and "to" the search runs right away.
 */
export function searchLink({ from, to, date, time }) {
  const params = new URLSearchParams();

  if (from) {
    params.set("from", from.stop_id);
    params.set("from_name", from.stop_name);
  }
  if (to) {
    params.set("to", to.stop_id);
    params.set("to_name", to.stop_name);
  }
  if (date) params.set("date", date);
  if (time) params.set("time", time);

  return `/?${params.toString()}`;
}

/**
 * Reads what searchLink() put in the URL. Missing values come back as null.
 */
export function readSearchLink(searchParams) {
  const station = (prefix) => {
    const stopId = searchParams.get(prefix);
    return stopId ? { stop_id: stopId, stop_name: searchParams.get(`${prefix}_name`) || stopId } : null;
  };

  return {
    from: station("from"),
    to: station("to"),
    date: searchParams.get("date"),
    time: searchParams.get("time"),
  };
}
