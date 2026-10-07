import { useCallback, useEffect, useState } from "react";
import { useLocation, useNavigate, useSearchParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import StationInput from "./StationInput";
import { useAuth } from "../context/Auth";
import { searchTrains } from "../api/search";
import { create as createTicket } from "../api/tickets";
import { getErrorMessage } from "../api/api";
import { ExpectedTime, TrainStatusBadge } from "./TrainStatus";
import { formatPrice, toMinutes, today } from "../utils/format";
import { readSearchLink } from "../utils/searchLink";
import "../styles/trains.css";

const duration = (trip) => toMinutes(trip.arrival_time) - toMinutes(trip.departure_time);


const SORTERS = {
  departure: (a, b) => toMinutes(a.departure_time) - toMinutes(b.departure_time),
  duration: (a, b) => duration(a) - duration(b),
  price: (a, b) => (a.price === null) - (b.price === null) || Number(a.price) - Number(b.price),
};


function TrainSearch() {
  const { t, i18n } = useTranslation();
  const { user } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  // Stations/date/time can come from a link, e.g. a station popup on the map (see utils/searchLink.js).
  const [searchParams] = useSearchParams();
  const [initial] = useState(() => readSearchLink(searchParams));

  const [fromText, setFromText] = useState(initial.from?.stop_name ?? "");
  const [toText, setToText] = useState(initial.to?.stop_name ?? "");
  const [fromStation, setFromStation] = useState(initial.from);
  const [toStation, setToStation] = useState(initial.to);

  const [date, setDate] = useState(initial.date && initial.date >= today() ? initial.date : today());
  const [time, setTime] = useState(initial.time ?? "");

  // A link with both stations (e.g. from the departure board) searches straight away.
  const [autoSearch] = useState(() =>
    initial.from && initial.to
      ? { from: initial.from.stop_id, to: initial.to.stop_id, date, time }
      : null,
  );
  const [sortBy, setSortBy] = useState("departure");
  const [onlyWithPrice, setOnlyWithPrice] = useState(false);

  const [results, setResults] = useState(null); // null = not searched yet
  const [searchedDate, setSearchedDate] = useState(date);
  const [loading, setLoading] = useState(autoSearch !== null);
  const [error, setError] = useState("");

  const swapStations = () => {
    setFromText(toText);
    setToText(fromText);
    setFromStation(toStation);
    setToStation(fromStation);
  };

  // i18n.t (not t) keeps this function stable, so a language switch doesn't repeat the search.
  const loadResults = useCallback(
    (params) =>
      searchTrains(params)
        .then((res) => {
          setResults(res.data);
          setSearchedDate(params.date);
        })
        .catch((err) => setError(getErrorMessage(err, i18n.t("common.error"))))
        .finally(() => setLoading(false)),
    [i18n],
  );

  useEffect(() => {
    if (autoSearch) loadResults(autoSearch);
  }, [autoSearch, loadResults]);

  const handleSearch = (e) => {
    e.preventDefault();

    if (!fromText.trim() || !toText.trim()) {
      setError(t("trains.validation"));
      return;
    }

    setError("");
    setLoading(true);

    // A picked suggestion is exact (stop_id); typed text matches by name.
    loadResults({
      from: fromStation?.stop_id ?? fromText.trim(),
      to: toStation?.stop_id ?? toText.trim(),
      date,
      time,
    });
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
            {shownResults.map((trip) => {
              const cancelled = trip.status === "cancelled";
              const delay = Number(trip.delay_minutes);

              return (
                <div
                  key={`${trip.trip_id}-${trip.from_stop_id}`}
                  className={`card train-result${cancelled ? " is-cancelled" : ""}`}
                >
                  <div>
                    <div className="train-times">
                      <ExpectedTime time={trip.departure_time} status={trip.status} delay={delay} /> →{" "}
                      <ExpectedTime time={trip.arrival_time} status={trip.status} delay={delay} />
                    </div>
                    <div className="train-stations">
                      {trip.from_station} → {trip.to_station}
                    </div>
                    <TrainStatusBadge status={trip.status} delay={delay} reason={trip.status_reason} />
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
                      disabled={trip.price === null || cancelled}
                      onClick={() => handleBuy(trip)}
                    >
                      {cancelled ? t("status.cancelled") : t("trains.buy")}
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        </>
      )}
    </section>
  );
}

export default TrainSearch;
