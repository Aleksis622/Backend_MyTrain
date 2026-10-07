import api from "./api";

// Up to 10 stations whose name contains the text, for autocomplete.
export const search = (text) => api.get("/stops", { params: { search: text } });

export const one = (stopId) => api.get(`/stops/${stopId}`);

/**
 * Departure board of a station. Params (all optional): date "YYYY-MM-DD", after "HH:MM", limit.
 * Without a date it returns today's trains from now on.
 */
export const departures = (stopId, params = {}) =>
  api.get(`/stops/${stopId}/departures`, { params });
