import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { all as getStops } from "../api/stops";
import { getErrorMessage, listFrom } from "../api/api";

function Stops() {
  const { t } = useTranslation();
  const [stops, setStops] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    getStops()
      .then((res) => setStops(listFrom(res)))
      .catch((err) => setError(getErrorMessage(err, t("common.error"))))
      .finally(() => setLoading(false));
  }, [t]);

  if (loading) {
    return <main className="page">{t("common.loading")}</main>;
  }

  return (
    <main className="page">
      <h1>{t("stops.title")}</h1>

      {error && <p className="alert alert-error">{error}</p>}
      {!error && stops.length === 0 && <p className="muted">{t("stops.empty")}</p>}

      <div className="card-list">
        {stops.map((stop) => (
          <div key={stop.stop_id} className="card">
            <h3>{stop.stop_name}</h3>
            <p className="muted">
              {t("stops.coordinates")}: {stop.stop_lat}, {stop.stop_lon}
            </p>
          </div>
        ))}
      </div>
    </main>
  );
}

export default Stops;
