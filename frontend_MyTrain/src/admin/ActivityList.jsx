import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import Icon from "../components/Icon";
import { formatDateTime, formatPrice } from "../utils/format";

// Icon and colors per kind of action.
const LOOK = {
  ticket: { icon: "ticket", color: "bg-brand-soft text-brand" },
  user: { icon: "users", color: "bg-teal-soft text-teal" },
  train: { icon: "train", color: "bg-warn-soft text-warn" },
  account: { icon: "user", color: "bg-canvas text-subtle" },
};

// Changed field names ("name", "email", "language") as readable, translated words.
const FIELD_LABELS = { name: "auth.name", email: "auth.email", language: "profile.language" };

// Where clicking an entry leads (deleted users have nowhere to go).
function linkFor(action) {
  if (action.subject_type === "ticket") return `/admin/tickets/${action.subject_id}`;
  if (action.subject_type === "user" && action.action !== "user_deleted") return `/admin/users/${action.subject_id}`;
  if (action.subject_type === "train") return "/admin/trains";
  return null;
}

// "5 min ago" today, the full date and time before that.
function when(iso, lang) {
  const minutes = Math.round((Date.now() - new Date(iso).getTime()) / 60000);
  if (minutes < 60 * 12) {
    const relative = new Intl.RelativeTimeFormat(lang, { numeric: "auto" });
    return minutes < 60 ? relative.format(-minutes, "minute") : relative.format(-Math.round(minutes / 60), "hour");
  }
  return formatDateTime(iso, lang);
}

/**
 * The sentence for one log entry, e.g. "Refunded €4.50 for ticket MT-1234 (Anna Liepa)".
 */
function useDescribe() {
  const { t, i18n } = useTranslation();

  return (action) => {
    const details = action.details ?? {};
    // Missing names (e.g. a trip without a headsign) show as a dash instead of "null".
    const values = Object.fromEntries(Object.entries(details).map(([key, value]) => [key, value ?? "—"]));

    if (details.fields) values.fields = details.fields.map((field) => t(FIELD_LABELS[field] ?? field)).join(", ");
    if (details.amount !== undefined) values.amount = formatPrice(details.amount);
    if (details.date) values.date = new Date(`${details.date}T12:00:00`).toLocaleDateString(i18n.language);
    if (details.status) {
      values.status =
        details.status === "delayed" ? t("status.delayed", { count: details.minutes ?? 0 }) : t(`status.${details.status}`);
    }

    return t(`admin.actions.${action.action}`, values);
  };
}

/**
 * Activity log entries. showAdmin: also print who did it (the full log, not "my activity").
 */
function ActivityList({ actions, showAdmin = false }) {
  const { t, i18n } = useTranslation();
  const describe = useDescribe();

  if (actions.length === 0) return <p className="m-0 text-sm text-subtle">{t("admin.no_activity")}</p>;

  return (
    <ul className="m-0 flex list-none flex-col p-0">
      {actions.map((action) => {
        const look = LOOK[action.subject_type] ?? LOOK.account;
        const to = linkFor(action);
        const text = describe(action);

        return (
          <li key={action.id} className="flex items-start gap-3 border-b border-line py-3 last:border-b-0">
            <span className={`grid size-9 shrink-0 place-items-center rounded-full ${look.color}`}>
              <Icon name={look.icon} size={18} />
            </span>
            <span className="min-w-0 flex-1">
              {to ? (
                <Link to={to} className="text-sm font-semibold text-ink no-underline hover:text-brand">
                  {text}
                </Link>
              ) : (
                <span className="text-sm font-semibold text-ink">{text}</span>
              )}
              {action.details?.reason && <span className="block text-xs text-subtle">{action.details.reason}</span>}
              <span className="block text-xs text-subtle">
                {showAdmin && `${action.admin?.name ?? t("admin.deleted_admin")} · `}
                <time dateTime={action.created_at} title={formatDateTime(action.created_at, i18n.language)}>
                  {when(action.created_at, i18n.language)}
                </time>
              </span>
            </span>
          </li>
        );
      })}
    </ul>
  );
}

export default ActivityList;
