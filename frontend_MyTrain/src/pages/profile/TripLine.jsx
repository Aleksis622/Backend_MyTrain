import { useTranslation } from "react-i18next";
import { formatClock } from "../../utils/format";
import useDurationText from "./useDurationText";

/**
 * One end of the trip: time (planned crossed out + expected when late) and station.
 */
function Stop({ time, station, delay, cancelled, align }) {
  const { i18n } = useTranslation();
  const late = !cancelled && delay > 0;

  return (
    <div className={`trip-stop ${align === "end" ? "is-end" : ""}`}>
      <span className="trip-time">
        {late && <s>{formatClock(time, i18n.language)}</s>}
        <strong className={late ? "is-late" : cancelled ? "is-cancelled" : ""}>
          {formatClock(time, i18n.language, late ? delay : 0)}
        </strong>
      </span>
      <span className="trip-station">{station}</span>
    </div>
  );
}

/**
 * "08:00 Riga ——1 h 15 min—— 09:15 Tukums", with the delay applied to both times.
 */
function TripLine({ journey, delay = 0, cancelled = false }) {
  const durationText = useDurationText();
  const minutes = (new Date(journey.arrival_time) - new Date(journey.departure_time)) / 60000;

  return (
    <div className="trip-line">
      <Stop time={journey.departure_time} station={journey.from_stop?.stop_name} delay={delay} cancelled={cancelled} />
      <div className="trip-duration">
        <span>{durationText(minutes)}</span>
      </div>
      <Stop
        time={journey.arrival_time}
        station={journey.to_stop?.stop_name}
        delay={delay}
        cancelled={cancelled}
        align="end"
      />
    </div>
  );
}

export default TripLine;
