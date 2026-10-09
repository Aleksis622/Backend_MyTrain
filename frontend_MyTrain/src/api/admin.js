import api from "./api";

export const dashboard = () => api.get("/admin/dashboard");


export const users = (params) => api.get("/admin/users", { params });
export const user = (userId) => api.get(`/admin/users/${userId}`);
export const updateUser = (userId, fields) => api.put(`/admin/users/${userId}`, fields);
export const deleteUser = (userId) => api.delete(`/admin/users/${userId}`);


export const tickets = (params) => api.get("/admin/tickets", { params });
export const ticket = (ticketId) => api.get(`/admin/tickets/${ticketId}`);
export const cancelTicket = (ticketId) => api.post(`/admin/tickets/${ticketId}/cancel`);
export const markTicketPaid = (ticketId) => api.post(`/admin/tickets/${ticketId}/mark-paid`);
export const refundTicket = (ticketId) => api.post(`/admin/tickets/${ticketId}/refund`);


export const trains = (date) => api.get("/admin/trains", { params: { date } });
export const setTrainStatus = (tripId, { date, status, minutes, reason }) =>
  api.put(`/admin/trains/${tripId}/status`, { date, status, minutes, reason });


export const account = () => api.get("/admin/account");
export const updateAccount = (fields) => api.put("/admin/account", fields);
export const changePassword = ({ currentPassword, password, passwordConfirmation }) =>
  api.put("/admin/account/password", {
    current_password: currentPassword,
    password,
    password_confirmation: passwordConfirmation,
  });


// type: ticket | user | train | account (empty = everything)
export const activity = (params) => api.get("/admin/activity", { params });
