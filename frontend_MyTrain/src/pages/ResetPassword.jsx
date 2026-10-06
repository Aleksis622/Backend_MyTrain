import { useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { resetPassword } from "../api/auth";
import { getErrorMessage } from "../api/api";
import "../styles/auth.css";

// Opened from the link in the reset email: /reset-password?token=...&email=...
function ResetPassword() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [params] = useSearchParams();

  const [email, setEmail] = useState(params.get("email") || "");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMessage("");
    setError("");

    if (password !== passwordConfirmation) {
      setError(t("auth.passwords_differ"));
      return;
    }

    try {
      await resetPassword({ token: params.get("token"), email, password, passwordConfirmation });
      setMessage(t("auth.reset_success"));
      setTimeout(() => navigate("/login"), 1500);
    } catch (err) {
      setError(getErrorMessage(err, t("common.error")));
    }
  };

  return (
    <main className="page page-narrow auth-page">
      <h1>{t("auth.reset_title")}</h1>

      <form className="form" onSubmit={handleSubmit}>
        {message && <p className="alert alert-success">{message}</p>}
        {error && <p className="alert alert-error">{error}</p>}

        <input
          type="email"
          placeholder={t("auth.email")}
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
        <input
          type="password"
          placeholder={t("auth.new_password")}
          value={password}
          minLength={6}
          onChange={(e) => setPassword(e.target.value)}
          required
        />
        <input
          type="password"
          placeholder={t("auth.confirm_password")}
          value={passwordConfirmation}
          onChange={(e) => setPasswordConfirmation(e.target.value)}
          required
        />

        <button className="btn btn-block" type="submit">
          {t("auth.reset_button")}
        </button>
      </form>

      <div className="auth-links">
        <Link to="/login">{t("auth.back_to_login")}</Link>
      </div>
    </main>
  );
}

export default ResetPassword;
