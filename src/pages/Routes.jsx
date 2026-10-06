import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { all as getRoutes } from "../api/routes";
import { getErrorMessage, listFrom } from "../api/api";

function RoutesPage() {
  const { t } = useTranslation();
  const [routes, setRoutes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    getRoutes()
      .then((res) => setRoutes(listFrom(res)))
      .catch((err) => setError(getErrorMessage(err, t("common.error"))))
      .finally(() => setLoading(false));
  }, [t]);

  if (loading) {
    return <main className="page">{t("common.loading")}</main>;
  }

  return (
    <main className="page">
      <h1>{t("routes.title")}</h1>

      {error && <p className="alert alert-error">{error}</p>}
      {!error && routes.length === 0 && <p className="muted">{t("routes.empty")}</p>}

      <div className="card-list">
        {routes.map((route) => (
          <div key={route.route_id} className="card">
            <h3>
              {route.route_short_name} {route.route_long_name}
            </h3>
            {route.agency && (
              <p className="muted">
                {t("routes.agency")}: {route.agency.agency_name}
              </p>
            )}
          </div>
        ))}
      </div>
    </main>
  );
}

export default RoutesPage;
