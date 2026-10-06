import { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import StationInput from "./StationInput";
import { useAuth } from "../context/Auth";
import { searchTrains } from "../api/search";
import { create as createTicket } from "../api/tickets";
import { getErrorMessage } from "../api/api";
import { formatPrice, formatTime, today } from "../utils/format";
import "../styles/trains.css";

/**
 * Search form + results. "Buy ticket" creates an unpaid ticket and opens the payment page.
 */
function TrainSearch() {
  const { t } = useTranslation();
  const { user } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const [fromText, setFromText] = useState("");
  const [toText, setToText] = useState("");
  const [fromStation, setFromStation] = useState(null);
  const [toStation, setToStation] = useState(null);
  const [date, setDate] = useState(today());

  const [results, setResults] = useState(null); // null = not searched yet
  const [searchedDate, setSearchedDate] = useState(date);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const handleSearch = async (e) => {
    e.preventDefault();
    setError("");

    if (!fromText.trim() || !toText.trim()) {
      setError(t("trains.validation"));
      return;
    }

    setLoading(true);
    try {
      // A picked suggestion is exact (stop_id); typed text matches by name.
      const res = await searchTrains({
        from: fromStation?.stop_id ?? fromText.trim(),
        to: toStation?.stop_id ?? toText.trim(),
        date,
      });
      setResults(res.data);
      setSearchedDate(date);
    } catch (err) {
      setError(getErrorMessage(err, t("common.error")));
    } finally {
      setLoading(false);
    }
  };

  const handleBuy = async (trip) => {
    if (!user) {
      navigate("/login", { state: { from: location.pathname } });
      return;
    }

    setError("");
    try {
      const res = await createTicket(trip, searchedDate);
      navigate(`/payment/${res.data.ticket.id}`);
    } catch (err) {
      setError(getErrorMessage(err, t("common.error")));
    }
  };

  return (
    <section>
      <form className="search-form" onSubmit={handleSearch}>
        <StationInput
          placeholder={t("trains.from")}
          value={fromText}
          onChange={setFromText}
          onSelect={setFromStation}
        />
        <StationInput
          placeholder={t("trains.to")}
          value={toText}
          onChange={setToText}
          onSelect={setToStation}
        />
        <input type="date" value={date} min={today()} onChange={(e) => setDate(e.target.value)} />
        <button className="btn" type="submit" disabled={loading}>
          {loading ? t("common.loading") : t("trains.search")}
        </button>
      </form>

      {error && <p className="alert alert-error">{error}</p>}

      {results && (
        <>
          <h2>{t("trains.results")}</h2>

          {results.length === 0 && <p className="muted">{t("trains.no_results")}</p>}

          <div className="card-list">
            {results.map((trip) => (
              <div key={`${trip.trip_id}-${trip.from_stop_id}`} className="card train-result">
                <div>
                  <div className="train-times">
                    {formatTime(trip.departure_time)} → {formatTime(trip.arrival_time)}
                  </div>
                  <div className="train-stations">
                    {trip.from_station} → {trip.to_station}
                  </div>
                  <div className="muted">{trip.route_long_name || trip.trip_headsign}</div>
                </div>

                <div className="train-buy">
                  <div className="train-price">
                    {trip.price !== null ? formatPrice(trip.price, trip.currency) : t("trains.no_price")}
                  </div>
                  <button
                    className="btn"
                    disabled={trip.price === null}
                    onClick={() => handleBuy(trip)}
                  >
                    {t("trains.buy")}
                  </button>
                </div>
              </div>
            ))}
          </div>
        </>
      )}
    </section>
  );
}

export default TrainSearch;
