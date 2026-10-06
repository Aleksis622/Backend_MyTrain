import { useState } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { forgotPassword } from "../api/auth";
import { getErrorMessage } from "../api/api";
import "../styles/auth.css";

function ForgotPassword() {
  const { t } = useTranslation();
  const [email, setEmail] = useState("");
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMessage("");
    setError("");
    setLoading(true);

    try {
      await forgotPassword(email);
      setMessage(t("auth.link_sent"));
    } catch (err) {
      setError(getErrorMessage(err, t("common.error")));
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="page page-narrow auth-page">
      <h1>{t("auth.forgot_title")}</h1>

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

        <button className="btn btn-block" type="submit" disabled={loading}>
          {t("auth.send_link")}
        </button>
      </form>

      <div className="auth-links">
        <Link to="/login">{t("auth.back_to_login")}</Link>
      </div>
    </main>
  );
}

export default ForgotPassword;
