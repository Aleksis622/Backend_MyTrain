import api from "./api";

// Tickets of the logged-in user (paginated, newest first).
export const all = () => api.get("/tickets");

export const one = (ticketId) => api.get(`/tickets/${ticketId}`);

// Buy a ticket for one search result. The backend calculates the price.
export const create = ({ trip_id, from_stop_id, to_stop_id }, date) =>
  api.post("/tickets", { trip_id, from_stop_id, to_stop_id, date });

// Only unpaid tickets can be cancelled. Paid tickets are refunded through their payment.
export const cancel = (ticketId) => api.post(`/tickets/${ticketId}/cancel`);
