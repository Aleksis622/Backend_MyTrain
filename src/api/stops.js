import api from "./api";

export const all = () => api.get("/stops");

// Up to 10 stations whose name contains the text, for autocomplete.
export const search = (text) => api.get("/stops", { params: { search: text } });

export const one = (stopId) => api.get(`/stops/${stopId}`);

export const times = (stopId) => api.get(`/stops/${stopId}/times`);
