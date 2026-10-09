import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { TrainStatusBadge } from "../../components/TrainStatus";
import { formatDay } from "../../utils/format";
import TripLine from "./TripLine";
import useDurationText from "./useDurationText";

const MINUTE = 60000;

// The current time, updated every minute (for the countdown).
function useNow() {
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    const timer = setInterval(() => setNow(Date.now()), MINUTE);
    return () => clearInterval(timer);
  }, []);

  return now;
}

/**
 * The soonest paid trip: route, live train status, countdown and a link to the train on the map.
 */
function NextTrip({ ticket }) {
  const { t, i18n } = useTranslation();
  const now = useNow();
  const durationText = useDurationText();

  const { journey, train_status: status } = ticket;
  const cancelled = status.status === "cancelled";
  const delay = status.status === "delayed" ? status.delay_minutes : 0;
  const departs = new Date(journey.departure_time).getTime() + delay * MINUTE;
  const arrives = new Date(journey.arrival_time).getTime() + delay * MINUTE;
  const minutesLeft = Math.round((departs - now) / MINUTE);

  // The train is on the map from shortly before it leaves until it arrives.
  const onMap = !cancelled && now >= departs - 10 * MINUTE && now <= arrives;

  let countdown;
  if (cancelled) countdown = t("profile.train_cancelled");
  else if (minutesLeft <= 0) countdown = t("profile.on_the_way");
  else if (minutesLeft < 48 * 60) countdown = t("profile.departs_in", { time: durationText(minutesLeft) });
  else countdown = t("profile.departs_on", { date: formatDay(journey.departure_time, i18n.language) });

  return (
    <section className={`next-trip ${cancelled ? "is-cancelled" : ""}`}>
      <div className="next-trip-head">
        <span className="next-trip-label">{t("profile.next_trip")}</span>
        <span className="muted">
          {formatDay(journey.departure_time, i18n.language)} · {t("profile.train")}{" "}
          {journey.trip?.route?.route_short_name || journey.trip_id}
        </span>
      </div>

      <TripLine journey={journey} delay={delay} cancelled={cancelled} />

      <div className="next-trip-foot">
        <div>
          <p className="next-trip-countdown">{countdown}</p>
          <TrainStatusBadge status={status.status} delay={status.delay_minutes} reason={status.reason} />
        </div>
        <div className="card-actions">
          {onMap && (
            <Link className="btn" to={`/map?train=${encodeURIComponent(journey.trip_id)}`}>
              {t("profile.show_on_map")}
            </Link>
          )}
          <Link className="btn btn-outline" to="/profile/tickets">
            {t("profile.all_tickets")}
          </Link>
        </div>
      </div>
    </section>
  );
}

export default NextTrip;
