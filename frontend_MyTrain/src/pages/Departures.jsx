import { useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import StationInput from "../components/StationInput";
import { departures as getDepartures } from "../api/stops";
import { getErrorMessage } from "../api/api";
import { ExpectedTime, TrainStatusBadge } from "../components/TrainStatus";
import { addMinutes, formatTime, today } from "../utils/format";
import { searchLink } from "../utils/searchLink";
import "../styles/departures.css";

const PAGE_SIZE = 20;
const MAX_LIMIT = 50; // backend maximum

/**
 * Departure board of one station: /departures/:stopId
 * Opened from a station popup on the map, or by picking a station here.
 */
function Departures() {
  const { t, i18n } = useTranslation();
  const navigate = useNavigate();
  const { stopId } = useParams();

  const [stationText, setStationText] = useState("");
  const [date, setDate] = useState(today());
  const [time, setTime] = useState("");
  const [limit, setLimit] = useState(PAGE_SIZE);

  // The last answer and the request it belongs to. While a newer request is running,
  // the previous board stays visible (e.g. when loading more trains).
  const query = stopId ? `${stopId}|${date}|${time}|${limit}` : null;
  const [result, setResult] = useState({ query: null, board: null, error: "" });

  useEffect(() => {
    if (!query) return;

    let cancelled = false;

    // Today without a time = "from now on" (decided by the backend).
    getDepartures(stopId, { date, after: time || undefined, limit })
      .then((res) => !cancelled && setResult({ query, board: res.data, error: "" }))
      .catch(
        (err) =>
          !cancelled &&
          setResult({ query, board: null, error: getErrorMessage(err, i18n.t("common.error")) }),
      );

    return () => {
      cancelled = true;
    };
  }, [query, stopId, date, time, limit, i18n]);

  const loading = query !== null && result.query !== query;
  const board = result.board;
  const error = result.query === query ? result.error : "";

  const pickStation = (station) => {
    if (!station) return;
    setLimit(PAGE_SIZE);
    navigate(`/departures/${station.stop_id}`);
  };

  const stop = stopId && board?.stop?.stop_id === stopId ? board.stop : null;
  const list = stop ? board.departures : [];

  return (
    <main className="page">
      <h1>{stop ? t("departures.title_station", { station: stop.stop_name }) : t("departures.title")}</h1>
      <p className="muted">{t("departures.subtitle")}</p>

      <div className="card departures-filters">
        <StationInput
          placeholder={t("departures.pick_station")}
          value={stationText}
          onChange={setStationText}
          onSelect={pickStation}
        />
        <label>
          {t("trains.date")}
          <input type="date" value={date} min={today()} onChange={(e) => setDate(e.target.value)} />
        </label>
        <label>
          {t("trains.depart_after")}
          <input type="time" value={time} onChange={(e) => setTime(e.target.value)} />
        </label>
        {stop && (
          <Link className="btn btn-outline" to={searchLink({ from: stop })}>
            {t("departures.search_from_here")}
          </Link>
        )}
      </div>

      {error && <p className="alert alert-error">{error}</p>}
      {!stopId && <p className="muted">{t("departures.choose_hint")}</p>}
      {stopId && loading && !stop && <p>{t("common.loading")}</p>}

      {stop && list.length === 0 && !loading && <p className="muted">{t("departures.empty")}</p>}

      {list.length > 0 && (
        <div className="card departures-board">
          <table>
            <thead>
              <tr>
                <th>{t("departures.time")}</th>
                <th>{t("departures.destination")}</th>
                <th className="hide-narrow">{t("departures.route")}</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {list.map((departure) => {
                const delay = Number(departure.delay_minutes);
                const cancelled = departure.status === "cancelled";

                return (
                  <tr
                    key={`${departure.trip_id}-${departure.departure_time}`}
                    className={cancelled ? "is-cancelled" : undefined}
                  >
                    <td className="departure-time">
                      <ExpectedTime time={departure.departure_time} status={departure.status} delay={delay} />
                    </td>
                    <td>
                      <strong>{departure.destination}</strong>
                      <div className="muted">
                        {t("departures.arrives", {
                          time: formatTime(addMinutes(departure.destination_arrival_time, delay)),
                        })}
                      </div>
                      <TrainStatusBadge status={departure.status} delay={delay} reason={departure.status_reason} />
                    </td>
                    <td className="hide-narrow muted">
                      {departure.route_short_name || departure.route_long_name || departure.trip_headsign}
                    </td>
                    <td className="departure-action">
                      {cancelled ? (
                        <button className="btn" disabled>
                          {t("status.cancelled")}
                        </button>
                      ) : (
                        <Link
                          className="btn"
                          to={searchLink({
                            from: stop,
                            to: { stop_id: departure.destination_stop_id, stop_name: departure.destination },
                            date: board.date,
                            time: formatTime(departure.departure_time),
                          })}
                        >
                          {t("departures.tickets")}
                        </Link>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>

          {list.length === limit && limit < MAX_LIMIT && (
            <button
              className="btn btn-outline departures-more"
              disabled={loading}
              onClick={() => setLimit(Math.min(limit + PAGE_SIZE, MAX_LIMIT))}
            >
              {loading ? t("common.loading") : t("departures.show_more")}
            </button>
          )}
        </div>
      )}
    </main>
  );
}

export default Departures;
