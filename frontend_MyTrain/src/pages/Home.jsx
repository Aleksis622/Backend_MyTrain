import { useEffect, useState } from "react";
import { useLocation } from "react-router-dom";
import { useTranslation } from "react-i18next";
import TrainSearch from "../components/TrainSearch";
import { popularRoutes } from "../api/search";

function Home() {
  const { t } = useTranslation();
  const location = useLocation();
  const [popular, setPopular] = useState([]);

  useEffect(() => {
    popularRoutes()
      .then((res) => setPopular(res.data))
      .catch(() => setPopular([]));
  }, []);

  return (
    <main className="page">
      <h1>{t("home.welcome")}</h1>
      <p className="muted">{t("home.welcome_sub")}</p>

      {/* New link parameters (e.g. ?from=...&to=...) start a fresh search form. */}
      <TrainSearch key={location.search} />

      {popular.length > 0 && (
        <>
          <h2>{t("home.popular_routes")}</h2>
          <div className="popular-routes">
            {popular.map((route) => (
              <div key={route.id} className="card">
                <h3>{route.name}</h3>
                <p className="muted">{route.description}</p>
              </div>
            ))}
          </div>
        </>
      )}
    </main>
  );
}

export default Home;
