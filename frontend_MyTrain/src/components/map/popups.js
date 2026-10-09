import { el, trainIconSvg } from "./dom";
import { departures as getDepartures } from "../../api/stops";
import { addMinutes, formatTime, minutesFromNow, today } from "../../utils/format";
import { searchLink } from "../../utils/searchLink";

const POPUP_DEPARTURES = 4;

// "in 5 min" / "now"; nothing for times an hour or more away.
function relativeTime(gtfsTime, t) {
  const minutes = minutesFromNow(gtfsTime);
  if (minutes <= 0) return t("map.now");
  if (minutes < 60) return t("map.in_minutes", { count: minutes });
  return "";
}

// "On time" / "Delayed ~9 min" / "Cancelled" chip (same look as TrainStatusBadge on the pages).
function statusChip(status, delay, t) {
  const label =
    status === "cancelled"
      ? t("status.cancelled")
      : status === "delayed"
        ? t("status.delayed", { count: delay })
        : t("status.on_time");
  return el("span", `status-badge is-${status}`, label);
}

// Expected time; for a late train the planned time is shown crossed out before it.
function expectedTime(gtfsTime, delay) {
  if (!delay) return el("strong", null, formatTime(gtfsTime));
  return el(
    "span",
    "time-delayed",
    el("s", null, formatTime(gtfsTime)),
    " ",
    el("strong", null, formatTime(addMinutes(gtfsTime, delay))),
  );
}

function header(variant, badge, title, subtitle) {
  return el(
    "div",
    `popup-header is-${variant}`,
    el("div", "popup-badge", trainIconSvg(14), badge),
    el("div", "popup-title", title),
    subtitle ? el("div", "popup-subtitle", subtitle) : null,
  );
}

// One "label / station ... time (in X min)" line; delay moves the time later.
function stopRow(label, station, gtfsTime, delay, t) {
  return el(
    "div",
    "popup-row",
    el("div", null, el("div", "popup-label", label), el("div", "popup-value", station)),
    el(
      "div",
      "popup-time",
      expectedTime(gtfsTime, delay),
      el("span", null, relativeTime(addMinutes(gtfsTime, delay), t)),
    ),
  );
}

function actionButton(label, onClick, outline = false) {
  const button = el("button", outline ? "btn btn-outline" : "btn", label);
  button.type = "button";
  button.addEventListener("click", onClick);
  return button;
}

/**
 * Train card: status, origin → destination, next and final stop with times, progress, ticket search.
 */
export function trainPopup(train, { t, navigate }) {
  const moving = train.status === "moving";
  const delay = Number(train.delay_minutes) || 0;
  const departure = addMinutes(train.current_stop_departure, delay);

  const now = moving
    ? t("map.between", { from: train.previous_stop, to: train.next_stop })
    : t("map.at_station", { station: train.current_stop });

  const departs =
    !moving && train.next_stop
      ? el(
          "div",
          "popup-departs",
          t("map.departs", { time: formatTime(departure) }),
          " ",
          el("span", null, relativeTime(departure, t)),
        )
      : null;

  // Progress along the trip: stops passed, plus half a stop while between two stations.
  const segments = Math.max(1, train.stops_total - 1);
  const percent = Math.min(100, ((train.stop_number - 1 + (moving ? 0.5 : 0)) / segments) * 100);
  const bar = el("div", "popup-progress-bar");
  bar.style.width = `${percent}%`;

  // Board at the next stop and ride to the end; past-midnight times ("24:10") belong to yesterday.
  const canSearch =
    train.next_stop_id &&
    train.next_stop_id !== train.destination_stop_id &&
    Number(train.next_arrival?.slice(0, 2)) < 24;

  return el(
    "div",
    "popup-card",
    header(
      moving ? "moving" : "at-station",
      moving ? t("map.status_moving") : t("map.status_at_station"),
      `${train.origin} → ${train.destination}`,
      train.route_name || train.headsign,
    ),
    el(
      "div",
      "popup-body",
      el(
        "div",
        "popup-service",
        statusChip(train.service_status, delay, t),
        train.status_reason && delay ? el("span", "status-reason", train.status_reason) : null,
      ),
      el("div", "popup-now", now, departs),
      train.next_stop ? stopRow(t("map.next_stop"), train.next_stop, train.next_arrival, delay, t) : null,
      stopRow(t("map.final_stop"), train.destination, train.destination_arrival, delay, t),
      el(
        "div",
        "popup-progress",
        el("div", "popup-progress-track", bar),
        el("span", null, t("map.progress", { number: train.stop_number, total: train.stops_total })),
      ),
      // Honest about the source: the position comes from the timetable (+ delays), not from GPS.
      // The card is rebuilt on every refresh, so "now" is the time of the last update.
      el(
        "p",
        "popup-source",
        t("map.estimated_position", {
          time: new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", second: "2-digit" }),
        }),
      ),
    ),
    canSearch
      ? el(
          "div",
          "popup-actions",
          actionButton(t("map.find_tickets"), () =>
            navigate(
              searchLink({
                from: { stop_id: train.next_stop_id, stop_name: train.next_stop },
                to: { stop_id: train.destination_stop_id, stop_name: train.destination },
                date: today(),
                time: formatTime(train.next_arrival),
              }),
            ),
          ),
        )
      : null,
  );
}

/**
 * Station card: name, the next few departures with "in X min", links to the board and the search.
 */
export function stationPopup(station, { t, navigate }) {
  const list = el("div", "popup-departures", el("p", "popup-muted", t("common.loading")));

  getDepartures(station.stop_id, { limit: POPUP_DEPARTURES })
    .then(({ data }) => {
      if (data.departures.length === 0) {
        list.replaceChildren(el("p", "popup-muted", t("map.station_no_departures")));
        return;
      }
      list.replaceChildren(
        ...data.departures.map((departure) => {
          const delay = Number(departure.delay_minutes) || 0;
          const cancelled = departure.status === "cancelled";

          // Right column: "Cancelled", "+9 min" or "in 5 min".
          const info = cancelled
            ? el("span", "status-badge is-cancelled", t("status.cancelled"))
            : delay
              ? el("span", "status-badge is-delayed", t("status.delay_short", { count: delay }))
              : el("span", "popup-departure-in", relativeTime(departure.departure_time, t));

          return el(
            "div",
            `popup-departure${cancelled ? " is-cancelled" : ""}`,
            el("span", "popup-departure-time", expectedTime(departure.departure_time, delay)),
            el("span", "popup-departure-destination", departure.destination),
            info,
          );
        }),
      );
    })
    .catch(() => list.replaceChildren(el("p", "popup-muted", t("common.error"))));

  return el(
    "div",
    "popup-card",
    header("station", t("map.legend_station"), station.stop_name),
    el("div", "popup-body", el("div", "popup-label", t("map.next_departures")), list),
    el(
      "div",
      "popup-actions",
      actionButton(t("map.station_all_departures"), () => navigate(`/departures/${station.stop_id}`)),
      actionButton(t("map.station_search_from"), () => navigate(searchLink({ from: station })), true),
    ),
  );
}
