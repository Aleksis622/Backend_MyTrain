import api from "./api";

export const all = () => api.get("/trips");

export const one = (tripId) => api.get(`/trips/${tripId}`);

export const times = (tripId) => api.get(`/trips/${tripId}/times`);
