import api from "./api";

// Tickets of the logged-in user, 10 per page: all({ scope: "upcoming" | "past" | "cancelled", page }).
// Each ticket has train_status { status, delay_minutes, reason } for its travel day.
export const all = (params) => api.get("/tickets", { params });

export const one = (ticketId) => api.get(`/tickets/${ticketId}`);

// Buy a ticket for one search result. The backend calculates the price.
export const create = ({ trip_id, from_stop_id, to_stop_id }, date) =>
  api.post("/tickets", { trip_id, from_stop_id, to_stop_id, date });

// Only unpaid tickets can be cancelled. Paid tickets are refunded instead
// (until departure, or any time when the train was cancelled: see ticket.refundable).
export const cancel = (ticketId) => api.post(`/tickets/${ticketId}/cancel`);

export const refund = (ticketId) => api.post(`/tickets/${ticketId}/refund`);
