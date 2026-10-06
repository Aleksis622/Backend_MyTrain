import api from "./api";

export const all = () => api.get("/payments");

export const one = (paymentId) => api.get(`/payments/${paymentId}`);

// Starts a pending payment for a ticket (re-uses an existing pending one).
export const create = (ticketId, provider) =>
  api.post("/payments", { ticket_id: ticketId, provider });

export const confirm = (paymentId) => api.post(`/payments/${paymentId}/confirm`);

export const refund = (paymentId) => api.post(`/payments/${paymentId}/refund`);
