import { Navigate, useLocation } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../context/Auth";

/**
 * Admin pages: guests go to /login, logged-in non-admins go to the home page.
 * (The backend checks this too; this only keeps the UI tidy.)
 */
function RequireAdmin({ children }) {
  const { user, loading } = useAuth();
  const location = useLocation();
  const { t } = useTranslation();

  if (loading) {
    return <main className="page">{t("common.loading")}</main>;
  }

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  if (!user.is_admin) {
    return <Navigate to="/" replace />;
  }

  return children;
}

export default RequireAdmin;
