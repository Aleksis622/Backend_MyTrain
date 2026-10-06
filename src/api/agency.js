import api from "./api";

export const all = () => api.get("/agency");

export const one = (agencyId) => api.get(`/agency/${agencyId}`);
