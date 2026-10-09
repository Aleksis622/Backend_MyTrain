import { useCallback, useEffect, useState } from "react";
import { Link, NavLink, Outlet } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../../context/Auth";
import { overview as getOverview } from "../../api/profile";
import { formatPrice, initials } from "../../utils/format";
import NextTrip from "./NextTrip";
import "../../styles/profile.css";

/**
 * Profile layout: who I am + my numbers, my next trip, then the tabs (Account / My tickets).
 * The tabs get { overview, reloadOverview } through the outlet context.
 * Only rendered for logged-in users (see RequireAuth in App.jsx).
 */
function Profile() {
  const { t, i18n } = useTranslation();
  const { user } = useAuth();
  const [overview, setOverview] = useState(null);

  const reloadOverview = useCallback(
    () =>
      getOverview()
        .then((res) => setOverview(res.data))
        .catch(() => {}), // the header simply stays without numbers
    [],
  );

  useEffect(() => {
    reloadOverview();
  }, [reloadOverview]);

  const memberSince = new Date(user.created_at).toLocaleDateString(i18n.language, { month: "long", year: "numeric" });

  return (
    <main className="page">
      <section className="card profile-header">
        <span className="profile-avatar">{initials(user.name) || "?"}</span>
        <div className="profile-who">
          <h1>{user.name}</h1>
          <p className="muted">{user.email}</p>
          <p className="muted profile-since">{t("profile.member_since", { date: memberSince })}</p>
        </div>

        {overview && (
          <dl className="profile-stats">
            <div>
              <dt>{t("profile.trips_taken")}</dt>
              <dd>{overview.stats.trips_taken}</dd>
            </div>
            <div>
              <dt>{t("profile.upcoming_trips")}</dt>
              <dd>{overview.stats.upcoming_trips}</dd>
            </div>
            <div>
              <dt>{t("profile.total_spent")}</dt>
              <dd>{formatPrice(overview.stats.total_spent)}</dd>
            </div>
          </dl>
        )}
      </section>

      {overview?.counts.unpaid > 0 && (
        <p className="alert alert-warning">
          {t("profile.unpaid_notice", { count: overview.counts.unpaid })}{" "}
          <Link to="/profile/tickets">{t("profile.unpaid_show")}</Link>
        </p>
      )}

      {overview?.next_trip && <NextTrip ticket={overview.next_trip} />}

      <nav className="tabs">
        <NavLink to="/profile" end>
          {t("profile.tab_account")}
        </NavLink>
        <NavLink to="/profile/tickets">{t("tickets.title")}</NavLink>
      </nav>

      <Outlet context={{ overview, reloadOverview }} />
    </main>
  );
}

export default Profile;
