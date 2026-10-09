import api from "./api";

export const trains = () => api.get("/map/trains");

// Every station with coordinates, for the station layer.
export const stations = () => api.get("/map/stations");

export const route = (tripId) => api.get(`/map/train-route/${tripId}`);
