import { useState } from "react";
import { Link, NavLink, Outlet, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../context/Auth";
import Icon from "../components/Icon";
import LanguageSwitch from "../components/LanguageSwitch";
import { initials } from "../utils/format";
import "../styles/admin.css";

const LINKS = [
  { to: "/admin", label: "admin.nav_dashboard", icon: "dashboard", end: true },
  { to: "/admin/tickets", label: "admin.nav_tickets", icon: "ticket" },
  { to: "/admin/users", label: "admin.nav_users", icon: "users" },
  { to: "/admin/trains", label: "admin.nav_trains", icon: "train" },
  { to: "/admin/activity", label: "admin.nav_activity", icon: "activity" },
];

const itemClass = "flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-semibold no-underline";

const linkClass = ({ isActive }) =>
  `${itemClass} ${isActive ? "bg-brand-soft text-brand" : "text-subtle hover:bg-canvas hover:text-ink"}`;

/**
 * Admin panel frame: sidebar menu on the left (slides in on phones), top bar, current page.
 */
function AdminLayout() {
  const { t } = useTranslation();
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [menuOpen, setMenuOpen] = useState(false);
  const closeMenu = () => setMenuOpen(false);

  const logOut = async () => {
    await logout();
    navigate("/");
  };

  return (
    <div className="flex min-h-screen flex-1 bg-canvas text-ink">
      <aside
        className={`fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r border-line bg-card px-4 py-6 transition-transform md:sticky md:top-0 md:h-screen md:translate-x-0 ${
          menuOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        <Link to="/admin" onClick={closeMenu} className="mb-8 flex items-center gap-2 px-2 no-underline">
          <span className="grid size-9 place-items-center rounded-[10px] bg-brand text-white">
            <Icon name="train" />
          </span>
          <span className="text-lg font-extrabold tracking-tight text-ink">MyTrain</span>
          <span className="rounded-full bg-brand-soft px-2 py-0.5 text-xs font-semibold text-brand">Admin</span>
        </Link>

        <nav className="flex flex-col gap-1">
          {LINKS.map(({ to, label, icon, end }) => (
            <NavLink key={to} to={to} end={end} className={linkClass} onClick={closeMenu}>
              <Icon name={icon} />
              {t(label)}
            </NavLink>
          ))}
        </nav>

        <div className="mt-auto flex flex-col gap-1 border-t border-line pt-4">
          <NavLink to="/admin/account" className={linkClass} onClick={closeMenu}>
            <Icon name="user" />
            {t("admin.my_account")}
          </NavLink>
          <Link to="/" className={`${itemClass} text-subtle hover:bg-canvas hover:text-ink`}>
            <Icon name="back" />
            {t("admin.back_to_site")}
          </Link>
          <button
            type="button"
            onClick={logOut}
            className={`${itemClass} cursor-pointer border-0 bg-transparent font-[inherit] text-subtle hover:bg-bad-soft hover:text-bad`}
          >
            <Icon name="logout" />
            {t("admin.logout")}
          </button>
        </div>
      </aside>

      {menuOpen && <div className="fixed inset-0 z-20 bg-ink/30 md:hidden" onClick={closeMenu} />}

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-10 flex items-center gap-3 border-b border-line bg-card px-4 py-3 md:px-8">
          <button
            type="button"
            onClick={() => setMenuOpen(true)}
            className="grid size-9 cursor-pointer place-items-center rounded-[10px] border border-line bg-card text-ink md:hidden"
            aria-label={t("admin.menu")}
          >
            <Icon name="menu" />
          </button>

          <div className="ml-auto flex items-center gap-4">
            <LanguageSwitch />
            <Link
              to="/admin/account"
              className="flex items-center gap-3 rounded-[10px] px-1 py-1 text-ink no-underline hover:bg-canvas"
              title={t("admin.my_account")}
            >
              <span className="grid size-9 place-items-center rounded-full bg-brand text-sm font-bold text-white">
                {initials(user.name) || "A"}
              </span>
              <span className="hidden leading-tight sm:block">
                <span className="block text-sm font-semibold">{user.name}</span>
                <span className="block text-xs text-subtle">{user.email}</span>
              </span>
            </Link>
          </div>
        </header>

        <main className="flex-1 px-4 py-6 md:px-8 md:py-8">
          <div className="mx-auto max-w-7xl">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}

export default AdminLayout;
