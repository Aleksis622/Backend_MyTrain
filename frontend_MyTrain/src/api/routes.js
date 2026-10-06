import api from "./api";

export const all = () => api.get("/routes");

export const one = (routeId) => api.get(`/routes/${routeId}`);

export const trips = (routeId) => api.get(`/routes/${routeId}/trips`);
