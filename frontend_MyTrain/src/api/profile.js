import api from "./api";

// Profile header numbers, ticket tab counts and the next paid trip (or null).
export const overview = () => api.get("/profile/overview");
