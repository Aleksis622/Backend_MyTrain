import api, { BACKEND_URL } from "./api";

// Sets the XSRF-TOKEN cookie. Needed before every login/register.
const getCsrfCookie = () => api.get(`${BACKEND_URL}/sanctum/csrf-cookie`);

export const register = async ({ name, email, password, language }) => {
  await getCsrfCookie();
  return api.post("/register", { name, email, password, language });
};

export const login = async (email, password) => {
  await getCsrfCookie();
  return api.post("/login", { email, password });
};

export const logout = () => api.post("/logout");

export const me = () => api.get("/user");

export const changeLanguage = (language) => api.post("/user/language", { language });

export const resendVerificationEmail = () => api.post("/email/resend");

export const forgotPassword = (email) => api.post("/forgot-password", { email });

export const resetPassword = ({ token, email, password, passwordConfirmation }) =>
  api.post("/reset-password", {
    token,
    email,
    password,
    password_confirmation: passwordConfirmation,
  });
