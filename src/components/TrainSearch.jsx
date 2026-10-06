import { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import StationInput from "./StationInput";
import { useAuth } from "../context/Auth";
import { searchTrains } from "../api/search";
import { create as createTicket } from "../api/tickets";
import { getErrorMessage } from "../api/api";
import { formatPrice, formatTime, toMinutes, today } from "../utils/format";
import "../styles/trains.css";

const duration = (trip) => toMinutes(trip.arrival_time) - toMinutes(trip.departure_time);


const SORTERS = {
  departure: (a, b) => toMinutes(a.departure_time) - toMinutes(b.departure_time),
  duration: (a, b) => duration(a) - duration(b),
  price: (a, b) => (a.price === null) - (b.price === null) || Number(a.price) - Number(b.price),
};


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
  const [time, setTime] = useState("");
  const [sortBy, setSortBy] = useState("departure");
  const [onlyWithPrice, setOnlyWithPrice] = useState(false);

  const [results, setResults] = useState(null); // null = not searched yet
  const [searchedDate, setSearchedDate] = useState(date);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const swapStations = () => {
    setFromText(toText);
    setToText(fromText);
    setFromStation(toStation);
    setToStation(fromStation);
  };

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
        time,
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

  const shownResults = results
    ?.filter((trip) => !onlyWithPrice || trip.price !== null)
    .sort(SORTERS[sortBy]);

  return (
    <section>
      <form className="search-form card" onSubmit={handleSearch}>
        <div className="search-stations">
          <StationInput
            placeholder={t("trains.from")}
            value={fromText}
            onChange={setFromText}
            onSelect={setFromStation}
          />
          <button
            type="button"
            className="btn btn-outline swap-button"
            onClick={swapStations}
            title={t("trains.swap")}
            aria-label={t("trains.swap")}
          >
            ⇄
          </button>
          <StationInput
            placeholder={t("trains.to")}
            value={toText}
            onChange={setToText}
            onSelect={setToStation}
          />
        </div>

        <div className="search-filters">
          <label>
            {t("trains.date")}
            <input type="date" value={date} min={today()} onChange={(e) => setDate(e.target.value)} />
          </label>
          <label>
            {t("trains.depart_after")}
            <input type="time" value={time} onChange={(e) => setTime(e.target.value)} />
          </label>
          <label>
            {t("trains.sort_by")}
            <select value={sortBy} onChange={(e) => setSortBy(e.target.value)}>
              <option value="departure">{t("trains.sort_departure")}</option>
              <option value="duration">{t("trains.sort_duration")}</option>
              <option value="price">{t("trains.sort_price")}</option>
            </select>
          </label>
          <label className="checkbox-label">
            <input
              type="checkbox"
              checked={onlyWithPrice}
              onChange={(e) => setOnlyWithPrice(e.target.checked)}
            />
            {t("trains.only_buyable")}
          </label>

          <button className="btn" type="submit" disabled={loading}>
            {loading ? t("common.loading") : t("trains.search")}
          </button>
        </div>
      </form>

      {error && <p className="alert alert-error">{error}</p>}

      {shownResults && (
        <>
          <h2>{t("trains.results")}</h2>

          {shownResults.length === 0 && <p className="muted">{t("trains.no_results")}</p>}

          <div className="card-list">
            {shownResults.map((trip) => (
              <div key={`${trip.trip_id}-${trip.from_stop_id}`} className="card train-result">
                <div>
                  <div className="train-times">
                    {formatTime(trip.departure_time)} → {formatTime(trip.arrival_time)}
                  </div>
                  <div className="train-stations">
                    {trip.from_station} → {trip.to_station}
                  </div>
                  <div className="muted">
                    {t("trains.duration", { minutes: duration(trip) })} ·{" "}
                    {trip.route_long_name || trip.trip_headsign}
                  </div>
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
