import { useState } from "react";
import { Navigate, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../../context/Auth";
import { resendVerificationEmail } from "../../api/auth";
import { getErrorMessage } from "../../api/api";

// "Account" tab of the profile: user details, email verification and logout.
function Account() {
  const { t } = useTranslation();
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  // Admins have a fuller "My account" page in the admin panel ("My tickets" stays here for them).
  if (user.is_admin) {
    return <Navigate to="/admin/account" replace />;
  }

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
        <button className="btn btn-danger" onClick={handleLogout}>
          {t("profile.logout")}
        </button>
      </div>
    </div>
  );
}

export default Account;
