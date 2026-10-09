import api from "./api";

export const all = () => api.get("/payments");

// The payment's real status (the server asks Stripe itself while it is unfinished).
export const one = (paymentId) => api.get(`/payments/${paymentId}`);

// Starts (or continues) a Stripe Checkout for a ticket: { payment, checkout_url }.
export const create = (ticketId) => api.post("/payments", { ticket_id: ticketId });

// The user left Stripe's page with "cancel": closes the checkout.
export const cancel = (paymentId) => api.post(`/payments/${paymentId}/cancel`);

export const refund = (paymentId) => api.post(`/payments/${paymentId}/refund`);
