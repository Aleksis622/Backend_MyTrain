import api from "./api";

// "from" / "to" can be a stop_id (from the station autocomplete) or part of a station name.
export const searchTrains = ({ from, to, date }) =>
  api.get("/search-trains", { params: { from, to, date } });

export const popularRoutes = () => api.get("/popular-routes");
