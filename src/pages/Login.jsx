import { useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../context/Auth";
import { getErrorMessage } from "../api/api";
import "../styles/auth.css";

function Login() {
  const { t } = useTranslation();
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      await login(email, password);
      // Go back to the page that sent us here (e.g. "Buy ticket"), else the profile.
      navigate(location.state?.from || "/profile", { replace: true });
    } catch (err) {
      setError(getErrorMessage(err, t("common.error")));
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="page page-narrow auth-page">
      <h1>{t("auth.login")}</h1>

      <form className="form" onSubmit={handleSubmit}>
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
          placeholder={t("auth.password")}
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
        />

        <button className="btn btn-block" type="submit" disabled={loading}>
          {t("auth.login")}
        </button>
      </form>

      <div className="auth-links">
        <Link to="/forgot-password">{t("auth.forgot_password")}</Link>
        <span>
          {t("auth.no_account")}{" "}
          <Link to="/register" state={location.state}>
            {t("auth.register")}
          </Link>
        </span>
      </div>
    </main>
  );
}

export default Login;
