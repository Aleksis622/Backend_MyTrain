import api from "./api";

export const all = () => api.get("/calendar");

export const one = (serviceId) => api.get(`/calendar/${serviceId}`);
