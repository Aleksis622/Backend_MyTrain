import { useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../context/Auth";
import { getErrorMessage } from "../api/api";
import "../styles/auth.css";

function Register() {
  const { t, i18n } = useTranslation();
  const { register } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");

    if (!name || !email || !password) {
      setError(t("auth.fill_all"));
      return;
    }

    setLoading(true);
    try {
      // The backend logs the new user in, so no separate login is needed.
      await register({ name, email, password, language: i18n.language });
      navigate(location.state?.from || "/profile", { replace: true });
    } catch (err) {
      setError(getErrorMessage(err, t("common.error")));
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="page page-narrow auth-page">
      <h1>{t("auth.register")}</h1>

      <form className="form" onSubmit={handleSubmit}>
        {error && <p className="alert alert-error">{error}</p>}

        <input
          type="text"
          placeholder={t("auth.name")}
          value={name}
          onChange={(e) => setName(e.target.value)}
        />
        <input
          type="email"
          placeholder={t("auth.email")}
          value={email}
          onChange={(e) => setEmail(e.target.value)}
        />
        <input
          type="password"
          placeholder={t("auth.password")}
          value={password}
          minLength={6}
          onChange={(e) => setPassword(e.target.value)}
        />

        <button className="btn btn-block" type="submit" disabled={loading}>
          {t("auth.register")}
        </button>
      </form>

      <div className="auth-links">
        <span>
          {t("auth.have_account")}{" "}
          <Link to="/login" state={location.state}>
            {t("auth.login")}
          </Link>
        </span>
      </div>
    </main>
  );
}

export default Register;
