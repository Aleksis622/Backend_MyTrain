import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import * as adminApi from "../../api/admin";
import { useAuth } from "../../context/Auth";
import Icon from "../../components/Icon";
import { useAdminQuery } from "../hooks";
import { Alert, Badge, Card, TicketStatusBadge } from "../ui";
import { formatDateTime, formatPrice, formatTime } from "../../utils/format";

function greetingKey(hour) {
  if (hour >= 5 && hour < 12) return "admin.greeting_morning";
  if (hour >= 12 && hour < 18) return "admin.greeting_afternoon";
  return "admin.greeting_evening";
}

// Wraps a card in a link when it has somewhere to go.
function MaybeLink({ to, className = "", children }) {
  return to ? (
    <Link to={to} className={`no-underline transition-transform hover:-translate-y-0.5 ${className}`}>
      {children}
    </Link>
  ) : (
    <div className={className}>{children}</div>
  );
}

/**
 * Big colored tile at the top of the right column.
 */
function Tile({ color, icon, label, value, sub, to }) {
  return (
    <MaybeLink to={to} className={`flex flex-col items-center rounded-2xl px-3 py-4 text-center text-white shadow-card ${color}`}>
      <span className="text-sm leading-tight font-semibold">{label}</span>
      <span className="mt-3 grid size-14 place-items-center rounded-full bg-white/20">
        <Icon name={icon} size={28} />
      </span>
      <span className="mt-3 text-xl font-extrabold whitespace-nowrap">{value}</span>
      {sub && <span className="text-xs opacity-90">{sub}</span>}
    </MaybeLink>
  );
}

function SmallStat({ label, value, to }) {
  return (
    <MaybeLink to={to} className="block">
      <Card className="h-full">
        <div className="text-sm font-semibold text-ink">{label}</div>
        <div className="mt-2 text-2xl font-extrabold text-ink">{value}</div>
      </Card>
    </MaybeLink>
  );
}

function SectionTitle({ children, link }) {
  return (
    <div className="mb-3 flex items-center justify-between gap-3">
      <h2 className="m-0 text-lg font-bold text-ink">{children}</h2>
      {link}
    </div>
  );
}

/**
 * Today's delayed and cancelled trains, like a to-do list.
 */
function Disruptions({ trips, total }) {
  const { t } = useTranslation();

  return (
    <Card>
      <h2 className="m-0 text-lg font-bold text-ink">{t("admin.disruptions")}</h2>
      <p className="mt-1 mb-4 text-sm text-subtle">{t("admin.disruptions_count", { count: total })}</p>

      {trips.length === 0 ? (
        <p className="m-0 rounded-[10px] bg-ok-soft px-4 py-3 text-sm font-medium text-ok">{t("admin.no_disruptions")}</p>
      ) : (
        <ul className="m-0 flex list-none flex-col gap-1 p-0">
          {trips.map((trip) => {
            const cancelled = trip.status === "cancelled";
            return (
              <li key={trip.trip_id} className="flex items-center gap-3 rounded-[10px] px-2 py-2 hover:bg-canvas">
                <span
                  className={`grid size-9 shrink-0 place-items-center rounded-full ${
                    cancelled ? "bg-bad-soft text-bad" : "bg-warn-soft text-warn"
                  }`}
                >
                  <Icon name={cancelled ? "cancel" : "clock"} size={18} />
                </span>
                <span className="min-w-0 flex-1">
                  <span className="block truncate text-sm font-semibold text-ink">
                    {trip.origin} → {trip.destination}
                  </span>
                  <span className="block truncate text-xs text-subtle">
                    {formatTime(trip.departure_time)} · {trip.route_short_name || trip.trip_id}
                    {trip.status_reason && ` · ${trip.status_reason}`}
                  </span>
                </span>
                <Badge color={cancelled ? "red" : "amber"}>
                  {cancelled ? t("status.cancelled") : t("status.delay_short", { count: Number(trip.delay_minutes) })}
                </Badge>
              </li>
            );
          })}
        </ul>
      )}

      <Link to="/admin/trains" className="mt-4 block text-center text-sm font-semibold text-brand no-underline">
        {t("admin.see_all_trains")}
      </Link>
    </Card>
  );
}

// Round the chart's top up to 1, 2 or 5 × 10ⁿ (at least 4), so the grid lines get even numbers.
function niceMax(value) {
  const target = Math.max(value, 4);
  const power = 10 ** Math.floor(Math.log10(target));
  return [1, 2, 5, 10].map((step) => step * power).find((nice) => nice >= target);
}

/**
 * Bar chart of tickets sold per day. Hover (or focus) a day to see its exact number.
 */
function TicketsChart({ days }) {
  const { t, i18n } = useTranslation();
  const max = niceMax(Math.max(...days.map((day) => day.total)));
  const weekday = new Intl.DateTimeFormat(i18n.language, { weekday: "short" });
  const fullDate = new Intl.DateTimeFormat(i18n.language, { weekday: "long", day: "numeric", month: "long" });
  // Noon, so the date never shifts a day because of the time zone.
  const asDate = (iso) => new Date(`${iso}T12:00:00`);

  return (
    <Card>
      <h2 className="m-0 text-lg font-bold text-ink">{t("admin.chart_title")}</h2>

      <div className="mt-6 flex gap-3">
        {/* y axis: 0, half, max */}
        <div className="flex h-48 flex-col justify-between text-right text-xs text-subtle">
          {[max, max / 2, 0].map((tick) => (
            <span key={tick} className="-my-2 leading-4">
              {tick}
            </span>
          ))}
        </div>

        <ul className="relative m-0 flex h-48 flex-1 list-none items-end justify-around gap-2 p-0">
          {/* grid lines */}
          {[0, 50, 100].map((top) => (
            <li key={top} aria-hidden="true" className="absolute inset-x-0 border-t border-line" style={{ top: `${top}%` }} />
          ))}

          {days.map((day) => {
            const label = `${fullDate.format(asDate(day.date))}: ${t("admin.chart_tooltip", { count: day.total })}`;
            return (
              <li
                key={day.date}
                tabIndex={0}
                aria-label={label}
                className="group relative flex h-full w-full max-w-12 cursor-default items-end justify-center outline-none"
              >
                <span
                  className="w-full max-w-5 rounded-t-[4px] bg-brand transition-colors group-hover:bg-brand-dark group-focus:bg-brand-dark"
                  style={{ height: `${(day.total / max) * 100}%` }}
                />
                <span className="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 rounded-[8px] bg-ink px-2.5 py-1.5 text-xs whitespace-nowrap text-white shadow-card group-hover:block group-focus:block">
                  <span className="block font-bold">{t("admin.chart_tooltip", { count: day.total })}</span>
                  <span className="block opacity-80">{fullDate.format(asDate(day.date))}</span>
                </span>
              </li>
            );
          })}
        </ul>
      </div>

      {/* x axis: weekdays, lined up under the bars */}
      <div className="mt-2 flex gap-3">
        <span className="invisible text-xs">{max}</span>
        <div className="flex flex-1 justify-around gap-2">
          {days.map((day) => (
            <span key={day.date} className="w-full max-w-12 text-center text-xs text-subtle">
              {weekday.format(asDate(day.date))}
            </span>
          ))}
        </div>
      </div>
    </Card>
  );
}

function LatestTickets({ tickets }) {
  const { t, i18n } = useTranslation();

  if (tickets.length === 0) return <p className="text-subtle">{t("admin.no_tickets")}</p>;

  return (
    <ul className="m-0 flex list-none flex-col gap-2 p-0">
      {tickets.map((ticket) => (
        <li key={ticket.id}>
          <Link
            to={`/admin/tickets/${ticket.id}`}
            className="flex items-center gap-3 rounded-2xl border border-line bg-card px-4 py-3 no-underline shadow-card hover:border-brand"
          >
            <span className="grid size-10 shrink-0 place-items-center rounded-full bg-brand-soft text-brand">
              <Icon name="ticket" size={18} />
            </span>
            <span className="min-w-0 flex-1">
              <span className="block truncate text-sm font-semibold text-ink">
                {ticket.journey?.from_stop?.stop_name} → {ticket.journey?.to_stop?.stop_name}
              </span>
              <span className="block truncate text-xs text-subtle">
                <span className="font-mono">{ticket.ticket_code}</span> · {ticket.user?.name} ·{" "}
                {formatDateTime(ticket.journey?.departure_time, i18n.language)}
              </span>
            </span>
            <TicketStatusBadge status={ticket.status} />
          </Link>
        </li>
      ))}
    </ul>
  );
}

function Dashboard() {
  const { t } = useTranslation();
  const { user } = useAuth();
  const { data, error, loading } = useAdminQuery(adminApi.dashboard);
  const firstName = user.name?.split(" ")[0] ?? "";

  return (
    <>
      <h1 className="m-0 text-2xl font-extrabold tracking-tight text-ink md:text-3xl">
        {t(greetingKey(new Date().getHours()), { name: firstName })}
      </h1>
      <p className="mt-1 mb-6 text-subtle">{t("admin.dashboard_intro")}</p>

      <Alert>{error}</Alert>
      {loading && !data && <p className="text-subtle">{t("common.loading")}</p>}

      {data && (
        <div className="grid gap-6 lg:grid-cols-12">
          <div className="flex flex-col gap-6 lg:col-span-7">
            <Disruptions trips={data.disruptions_today} total={data.delayed_today + data.cancelled_today} />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <SmallStat label={t("admin.stat_users")} value={data.users} to="/admin/users" />
              <SmallStat label={t("admin.stat_trains_running")} value={data.trains_running} />
              <SmallStat label={t("admin.stat_revenue_total")} value={formatPrice(data.revenue_total)} />
            </div>

            <TicketsChart days={data.tickets_last_7_days} />
          </div>

          <div className="flex flex-col gap-6 lg:col-span-5">
            <section>
              <SectionTitle>{t("admin.overview")}</SectionTitle>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Tile
                  color="bg-brand"
                  icon="ticket"
                  label={t("admin.stat_tickets_today")}
                  value={data.tickets_today}
                  to="/admin/tickets"
                />
                <Tile color="bg-teal" icon="euro" label={t("admin.stat_revenue_today")} value={formatPrice(data.revenue_today)} />
                <Tile
                  color="bg-lilac"
                  icon="clock"
                  label={t("admin.stat_disrupted_today")}
                  value={data.delayed_today + data.cancelled_today}
                  sub={t("admin.delayed_cancelled", { delayed: data.delayed_today, cancelled: data.cancelled_today })}
                  to="/admin/trains"
                />
              </div>
            </section>

            <section>
              <SectionTitle>{t("admin.stat_tickets_by_status")}</SectionTitle>
              <Card className="flex flex-wrap gap-3">
                {Object.entries(data.tickets_by_status).map(([status, total]) => (
                  <span key={status} className="flex items-center gap-2 text-sm font-semibold text-ink">
                    <TicketStatusBadge status={status} /> {total}
                  </span>
                ))}
                {Object.keys(data.tickets_by_status).length === 0 && <span className="text-sm text-subtle">—</span>}
              </Card>
            </section>

            <section>
              <SectionTitle
                link={
                  <Link to="/admin/tickets" className="text-sm font-semibold text-brand no-underline">
                    {t("admin.see_all_tickets")}
                  </Link>
                }
              >
                {t("admin.latest_tickets")}
              </SectionTitle>
              <LatestTickets tickets={data.latest_tickets} />
            </section>
          </div>
        </div>
      )}
    </>
  );
}

export default Dashboard;
