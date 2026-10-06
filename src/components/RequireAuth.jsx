import { Navigate, useLocation } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../context/Auth";

/**
 * Wraps pages that need a logged-in user. Guests are sent to /login
 * and come back to this page after logging in.
 */
function RequireAuth({ children }) {
  const { user, loading } = useAuth();
  const location = useLocation();
  const { t } = useTranslation();

  if (loading) {
    return <main className="page">{t("common.loading")}</main>;
  }

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  return children;
}

export default RequireAuth;
