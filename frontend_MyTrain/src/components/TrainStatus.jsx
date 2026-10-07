import { useTranslation } from "react-i18next";
import { addMinutes, formatTime } from "../utils/format";

/**
 * "On time" / "Delayed ~9 min" / "Cancelled" badge, with the reason when there is one.
 * Uses status, delay_minutes and status_reason from the API.
 */
export function TrainStatusBadge({ status, delay, reason, compact = false }) {
  const { t } = useTranslation();

  const label =
    status === "cancelled"
      ? t("status.cancelled")
      : status === "delayed"
        ? t("status.delayed", { count: delay })
        : t("status.on_time");

  return (
    <span className="train-status">
      <span className={`status-badge is-${status}`}>{label}</span>
      {!compact && reason && status !== "on_time" && <span className="status-reason">{reason}</span>}
    </span>
  );
}

/**
 * A timetable time; when the train is late, the planned time is crossed out next to the expected one.
 */
export function ExpectedTime({ time, status, delay }) {
  if (status === "cancelled") {
    return <s className="time-cancelled">{formatTime(time)}</s>;
  }
  if (status === "delayed" && delay > 0) {
    return (
      <span className="time-delayed">
        <s>{formatTime(time)}</s> <strong>{formatTime(addMinutes(time, delay))}</strong>
      </span>
    );
  }
  return <>{formatTime(time)}</>;
}
