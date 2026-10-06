import api from "./api";

export const trains = () => api.get("/map/trains");

export const route = (tripId) => api.get(`/map/train-route/${tripId}`);

export const history = (trainId) =>
  api.get(`/map/trains/${trainId}/history`);
