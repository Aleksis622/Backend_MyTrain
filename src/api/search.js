import api from "./api";

export const searchTrains = ({ from, to, date, time }) =>
  api.get("/search-trains", { params: { from, to, date, time: time || undefined } });

export const popularRoutes = () => api.get("/popular-routes");
