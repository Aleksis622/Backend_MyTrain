import { NavLink, Outlet } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../../context/Auth";
import "../../styles/profile.css";

/**
 * Profile layout with tabs. The active tab (Account or My tickets) renders in <Outlet />.
 * Only rendered for logged-in users (see RequireAuth in App.jsx).
 */
function Profile() {
  const { t } = useTranslation();
  const { user } = useAuth();

  return (
    <main className="page">
      <h1>
        {t("profile.title")}: {user.name}
      </h1>

      <nav className="tabs">
        <NavLink to="/profile" end>
          {t("profile.tab_account")}
        </NavLink>
        <NavLink to="/profile/tickets">{t("tickets.title")}</NavLink>
      </nav>

      <Outlet />
    </main>
  );
}

export default Profile;
