import axios from "axios";
import i18n from "../lang";

// Must be "localhost" (not 127.0.0.1) so the Sanctum session cookie is shared with the app.
export const BACKEND_URL = import.meta.env.VITE_API_URL || "http://localhost:8000";

const api = axios.create({
  baseURL: `${BACKEND_URL}/api`,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },
});

// Lets the backend answer in the language chosen in the navbar.
api.interceptors.request.use((config) => {
  config.headers["X-Language"] = i18n.language;
  return config;
});

/**
 * Turns an axios error into a message for the user:
 * the first validation error, else the backend "error"/"message", else the fallback.
 */
export function getErrorMessage(err, fallback) {
  const data = err?.response?.data;
  const firstFieldError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;

  return firstFieldError || data?.error || data?.message || fallback;
}

/**
 * Paginated Laravel endpoints return { data: [...] }, others return a plain array.
 */
export const listFrom = (res) => (Array.isArray(res.data) ? res.data : res.data.data ?? []);

export default api;
