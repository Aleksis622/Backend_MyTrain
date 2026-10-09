import { Link, NavLink, useLocation } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../context/Auth";
import Icon from "./Icon";
import LanguageSwitch from "./LanguageSwitch";
import "../styles/navbar.css";

const LINKS = [
  { to: "/", label: "navigation.home" },
  { to: "/map", label: "navigation.map" },
];

function Navbar() {
  const { t } = useTranslation();
  const { user } = useAuth();
  const { pathname } = useLocation();

  // The admin panel has its own sidebar and top bar.
  if (pathname.startsWith("/admin")) return null;

  return (
    <nav className="nav">
      <Link to="/" className="nav-logo">
        <span className="nav-logo-icon">
          <Icon name="train" size={20} />
        </span>
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

        {user?.is_admin && (
          <li>
            <NavLink to="/admin">{t("navigation.admin")}</NavLink>
          </li>
        )}

        <li>
          {user ? (
            // Admins manage their account in the admin panel.
            <NavLink to={user.is_admin ? "/admin/account" : "/profile"}>{t("navigation.profile")}</NavLink>
          ) : (
            <NavLink to="/login">{t("navigation.login")}</NavLink>
          )}
        </li>
      </ul>

      <LanguageSwitch />
    </nav>
  );
}

export default Navbar;
