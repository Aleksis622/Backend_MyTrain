import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../context/Auth";
import { resendVerificationEmail } from "../api/auth";
import { getErrorMessage } from "../api/api";
import "../styles/profile.css";

// Only rendered for logged-in users (see RequireAuth in App.jsx).
function Profile() {
  const { t } = useTranslation();
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  const handleLogout = async () => {
    await logout();
    navigate("/login");
  };

  const handleResend = async () => {
    setMessage("");
    setError("");
    try {
      await resendVerificationEmail();
      setMessage(t("profile.resend_sent"));
    } catch (err) {
      setError(getErrorMessage(err, t("common.error")));
    }
  };

  return (
    <main className="page page-narrow">
      <h1>{t("profile.title")}</h1>

      <div className="card profile-card">
        <dl>
          <dt>{t("auth.name")}</dt>
          <dd>{user.name}</dd>
          <dt>{t("auth.email")}</dt>
          <dd>{user.email}</dd>
          <dt>{t("profile.language")}</dt>
          <dd>{user.language?.toUpperCase()}</dd>
        </dl>

        {user.email_verified_at ? (
          <p className="badge badge-paid">{t("profile.verified")}</p>
        ) : (
          <div className="verify-box">
            <p className="alert alert-error">{t("profile.not_verified")}</p>
            {message && <p className="alert alert-success">{message}</p>}
            {error && <p className="alert alert-error">{error}</p>}
            <button className="btn btn-outline" onClick={handleResend}>
              {t("profile.resend")}
            </button>
          </div>
        )}

        <div className="card-actions">
          <Link className="btn" to="/tickets">
            {t("tickets.title")}
          </Link>
          <button className="btn btn-danger" onClick={handleLogout}>
            {t("profile.logout")}
          </button>
        </div>
      </div>
    </main>
  );
}

export default Profile;
