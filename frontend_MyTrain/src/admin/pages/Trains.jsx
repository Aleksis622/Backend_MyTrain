import { useState } from "react";
import { useTranslation } from "react-i18next";
import * as adminApi from "../../api/admin";
import { useAdminAction, useAdminQuery } from "../hooks";
import { Alert, Badge, Button, Field, PageHeader, Table, cellClass, inputClass } from "../ui";
import { formatTime, today } from "../../utils/format";

const STATUS_COLORS = { on_time: "green", delayed: "amber", cancelled: "red" };

function StatusBadge({ trip }) {
  const { t } = useTranslation();
  const label =
    trip.status === "delayed" ? t("status.delayed", { count: Number(trip.delay_minutes) }) : t(`status.${trip.status}`);

  return (
    <span className="flex flex-col items-start gap-1">
      <Badge color={STATUS_COLORS[trip.status]}>{label}</Badge>
      {trip.status_reason && <span className="text-xs text-subtle">{trip.status_reason}</span>}
    </span>
  );
}

/**
 * Inline form in the trip's row: status, minutes (for delays) and a reason.
 */
function StatusEditor({ trip, date, onSaved, onClose }) {
  const { t } = useTranslation();
  const [status, setStatus] = useState(trip.status);
  const [minutes, setMinutes] = useState(Number(trip.delay_minutes) || 5);
  const [reason, setReason] = useState(trip.status_reason ?? "");
  const { busy, message, run } = useAdminAction();

  const save = async (e) => {
    e.preventDefault();
    const response = await run(() =>
      adminApi.setTrainStatus(trip.trip_id, {
        date,
        status,
        minutes: status === "delayed" ? minutes : undefined,
        reason: status === "on_time" ? undefined : reason || undefined,
      }),
    );
    if (response) onSaved(response.data);
  };

  return (
    <form className="flex flex-wrap items-end gap-3 bg-brand-soft p-4" onSubmit={save}>
      <Field label={t("admin.status")}>
        <select className={inputClass} value={status} onChange={(e) => setStatus(e.target.value)}>
          <option value="on_time">{t("status.on_time")}</option>
          <option value="delayed">{t("admin.status_delayed")}</option>
          <option value="cancelled">{t("status.cancelled")}</option>
        </select>
      </Field>
      {status === "delayed" && (
        <Field label={t("admin.delay_minutes")}>
          <input
            type="number"
            min="1"
            max="600"
            className={`${inputClass} w-24`}
            value={minutes}
            onChange={(e) => setMinutes(e.target.value)}
            required
          />
        </Field>
      )}
      {status !== "on_time" && (
        <Field label={t("admin.reason")}>
          <input
            className={`${inputClass} min-w-56`}
            value={reason}
            maxLength={255}
            placeholder={t("admin.reason_placeholder")}
            onChange={(e) => setReason(e.target.value)}
          />
        </Field>
      )}
      <Button type="submit" disabled={busy}>
        {t("admin.save")}
      </Button>
      <Button variant="secondary" onClick={onClose}>
        {t("admin.close")}
      </Button>
      {message?.type === "error" && <p className="m-0 w-full text-sm text-bad">{message.text}</p>}
    </form>
  );
}

/**
 * Trips of one day with their status. Only the status can change here;
 * the timetable stays exactly as the seeders loaded it.
 */
function Trains() {
  const { t } = useTranslation();
  const [date, setDate] = useState(today());
  const [filterText, setFilterText] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [editing, setEditing] = useState(null); // trip_id
  const { data, error, loading, setData } = useAdminQuery(adminApi.trains, date);

  const text = filterText.trim().toLowerCase();
  const trips = (data?.trips ?? []).filter(
    (trip) =>
      (!statusFilter || trip.status === statusFilter) &&
      (!text ||
        [trip.trip_id, trip.trip_headsign, trip.origin, trip.destination].some((value) =>
          String(value ?? "").toLowerCase().includes(text),
        )),
  );

  // Put the saved status into the list without reloading the whole day.
  const saved = (tripId, result) => {
    setData({
      ...data,
      trips: data.trips.map((trip) =>
        trip.trip_id === tripId
          ? { ...trip, status: result.status, delay_minutes: result.delay_minutes, status_reason: result.status_reason }
          : trip,
      ),
    });
    setEditing(null);
  };

  return (
    <>
      <PageHeader title={t("admin.nav_trains")} />
      <p className="mt-0 mb-4 text-sm text-subtle">{t("admin.trains_hint")}</p>

      <div className="mb-4 grid gap-3 rounded-2xl border border-line bg-card p-4 shadow-card md:grid-cols-[auto_1fr_auto]">
        <Field label={t("admin.date")}>
          <input
            type="date"
            className={inputClass}
            value={date}
            onChange={(e) => {
              setEditing(null);
              setDate(e.target.value || today());
            }}
          />
        </Field>
        <Field label={t("admin.filter_trains")}>
          <input className={inputClass} value={filterText} onChange={(e) => setFilterText(e.target.value)} />
        </Field>
        <Field label={t("admin.status")}>
          <select className={inputClass} value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
            <option value="">{t("admin.all")}</option>
            <option value="on_time">{t("status.on_time")}</option>
            <option value="delayed">{t("admin.status_delayed")}</option>
            <option value="cancelled">{t("status.cancelled")}</option>
          </select>
        </Field>
      </div>

      <Alert>{error}</Alert>
      {loading && !data && <p className="text-subtle">{t("common.loading")}</p>}
      {data && trips.length === 0 && <p className="text-subtle">{t("admin.no_trains")}</p>}

      {trips.length > 0 && (
        <>
          <p className="mb-2 text-sm text-subtle">{t("admin.results", { count: trips.length })}</p>
          <Table head={[t("admin.departure"), t("admin.train"), t("admin.journey"), t("admin.status"), ""]}>
            {trips.map((trip) => (
              <TrainRow
                key={trip.trip_id}
                trip={trip}
                date={data.date}
                isEditing={editing === trip.trip_id}
                onEdit={() => setEditing(trip.trip_id)}
                onClose={() => setEditing(null)}
                onSaved={(result) => saved(trip.trip_id, result)}
              />
            ))}
          </Table>
        </>
      )}
    </>
  );
}

function TrainRow({ trip, date, isEditing, onEdit, onClose, onSaved }) {
  const { t } = useTranslation();

  return (
    <>
      <tr className={trip.status === "cancelled" ? "bg-bad-soft/40" : "hover:bg-canvas"}>
        <td className={`${cellClass} font-semibold tabular-nums`}>
          {formatTime(trip.departure_time)}
          <div className="text-xs font-normal text-subtle">→ {formatTime(trip.arrival_time)}</div>
        </td>
        <td className={cellClass}>
          <span className="font-mono">{trip.trip_id}</span>
          <div className="text-xs text-subtle">{trip.route_short_name || trip.trip_headsign}</div>
        </td>
        <td className={cellClass}>
          {trip.origin} → {trip.destination}
        </td>
        <td className={cellClass}>
          <StatusBadge trip={trip} />
        </td>
        <td className={`${cellClass} text-right`}>
          {!isEditing && (
            <Button variant="secondary" onClick={onEdit}>
              {t("admin.change_status")}
            </Button>
          )}
        </td>
      </tr>
      {isEditing && (
        <tr>
          <td colSpan={5} className="p-0">
            <StatusEditor trip={trip} date={date} onSaved={onSaved} onClose={onClose} />
          </td>
        </tr>
      )}
    </>
  );
}

export default Trains;
