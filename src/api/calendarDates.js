import api from "./api";

export const all = () => api.get("/calendar_dates");

export const one = (serviceId) => api.get(`/calendar_dates/${serviceId}`);
