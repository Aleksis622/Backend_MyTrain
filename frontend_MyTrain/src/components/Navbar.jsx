import { Link, NavLink } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../context/Auth";
import { changeLanguage } from "../api/auth";
import { LANGUAGES } from "../lang";
import "../styles/navbar.css";

const LINKS = [
  { to: "/", label: "navigation.home" },
  { to: "/map", label: "navigation.map" },
  { to: "/routes", label: "navigation.routes" },
  { to: "/stops", label: "navigation.stops" },
];

function Navbar() {
  const { t, i18n } = useTranslation();
  const { user } = useAuth();

  const switchLanguage = (lang) => {
    i18n.changeLanguage(lang);

    // Also save it on the account, so backend emails use the same language.
    if (user) {
      changeLanguage(lang).catch(() => {});
    }
  };

  return (
    <nav className="nav">
      <Link to="/" className="nav-logo">
        MyTrain
      </Link>

      <ul className="nav-links">
        {LINKS.map(({ to, label }) => (
          <li key={to}>
            <NavLink to={to} end={to === "/"}>
              {t(label)}
            </NavLink>
          </li>
        ))}

        <li>
          {user ? (
            <NavLink to="/profile">{t("navigation.profile")}</NavLink>
          ) : (
            <NavLink to="/login">{t("navigation.login")}</NavLink>
          )}
        </li>
      </ul>

      <div className="lang-switch">
        {LANGUAGES.map((lang) => (
          <button
            key={lang}
            className={i18n.language === lang ? "active" : ""}
            onClick={() => switchLanguage(lang)}
          >
            {lang.toUpperCase()}
          </button>
        ))}
      </div>
    </nav>
  );
}

export default Navbar;
