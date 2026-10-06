import api from "./api";

export const attributes = () => api.get("/fare_attributes");

export const attribute = (fareId) => api.get(`/fare_attributes/${fareId}`);

export const rules = () => api.get("/fare_rules");

export const rule = (fareId) => api.get(`/fare_rules/${fareId}`);
