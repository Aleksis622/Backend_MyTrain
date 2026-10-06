import { useEffect } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../context/Auth";
import "../styles/auth.css";

// The backend redirects here after the link in the verification email is opened.
function EmailVerified() {
  const { t } = useTranslation();
  const { user, refreshUser } = useAuth();

  // Reload the user so the profile no longer shows "not verified".
  useEffect(() => {
    refreshUser();
  }, [refreshUser]);

  return (
    <main className="page page-narrow auth-page">
      <h1>{t("auth.email_verified_title")}</h1>
      <p>{t("auth.email_verified_text")}</p>

      <Link className="btn" to={user ? "/profile" : "/login"}>
        {user ? t("navigation.profile") : t("auth.login")}
      </Link>
    </main>
  );
}

export default EmailVerified;
