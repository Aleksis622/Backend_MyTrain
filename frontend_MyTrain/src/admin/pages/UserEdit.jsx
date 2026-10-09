import { useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import * as adminApi from "../../api/admin";
import { LANGUAGES } from "../../lang";
import ActivityList from "../ActivityList";
import { useAdminAction, useAdminQuery } from "../hooks";
import { Alert, Badge, Button, Card, Field, PageHeader, Table, TicketStatusBadge, cellClass, inputClass } from "../ui";
import { formatDateTime, formatPrice } from "../../utils/format";

/**
 * The form gets the loaded user once (the parent re-mounts it with key={user.id}).
 */
function UserForm({ user, onSaved }) {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [fields, setFields] = useState({ name: user.name ?? "", email: user.email, language: user.language });
  const { busy, message, run } = useAdminAction();

  const change = (name) => (e) => setFields({ ...fields, [name]: e.target.value });

  const save = async (e) => {
    e.preventDefault();
    const response = await run(() => adminApi.updateUser(user.id, fields), t("admin.saved"));
    if (response) onSaved(response.data.user);
  };

  const remove = async () => {
    if (!window.confirm(t("admin.confirm_delete_user", { name: user.name }))) return;
    const response = await run(() => adminApi.deleteUser(user.id));
    if (response) navigate("/admin/users");
  };

  return (
    <Card>
      <form className="flex flex-col gap-4" onSubmit={save}>
        <Field label={t("auth.name")}>
          <input className={inputClass} value={fields.name} onChange={change("name")} required />
        </Field>
        <Field label={t("auth.email")}>
          <input type="email" className={inputClass} value={fields.email} onChange={change("email")} required />
        </Field>
        <Field label={t("profile.language")}>
          <select className={inputClass} value={fields.language} onChange={change("language")}>
            {LANGUAGES.map((lang) => (
              <option key={lang} value={lang}>
                {lang.toUpperCase()}
              </option>
            ))}
          </select>
        </Field>

        <Alert type={message?.type}>{message?.text}</Alert>

        <div className="flex flex-wrap justify-between gap-2">
          <Button type="submit" disabled={busy}>
            {t("admin.save")}
          </Button>
          {!user.is_admin && (
            <Button variant="danger" disabled={busy} onClick={remove}>
              {t("admin.delete_user")}
            </Button>
          )}
        </div>
      </form>
    </Card>
  );
}

function Stat({ label, value }) {
  return (
    <Card>
      <div className="text-sm font-semibold text-subtle">{label}</div>
      <div className="mt-1 text-2xl font-extrabold text-ink">{value}</div>
    </Card>
  );
}

function UserEdit() {
  const { userId } = useParams();
  const { t, i18n } = useTranslation();
  const { data, error, setData } = useAdminQuery(adminApi.user, userId);

  if (!data) {
    return error ? <Alert>{error}</Alert> : <p className="text-subtle">{t("common.loading")}</p>;
  }

  const { user, stats, tickets, actions } = data;

  return (
    <>
      <PageHeader title={user.name || user.email}>
        <Link to="/admin/users" className="text-sm text-subtle">
          ← {t("admin.back_to_users")}
        </Link>
      </PageHeader>

      <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Stat label={t("admin.trips_taken")} value={stats.trips_taken} />
        <Stat label={t("admin.upcoming_trips")} value={stats.upcoming_trips} />
        <Stat label={t("admin.total_spent")} value={formatPrice(stats.total_spent)} />
        <Stat
          label={t("admin.last_purchase")}
          value={
            <span className="text-base">
              {stats.last_purchase_at ? formatDateTime(stats.last_purchase_at, i18n.language) : "—"}
            </span>
          }
        />
      </div>

      <div className="grid gap-6 lg:grid-cols-[minmax(0,22rem)_1fr]">
        <div>
          <div className="mb-3 flex flex-wrap gap-2 text-sm text-subtle">
            {user.is_admin && <Badge color="blue">{t("admin.role_admin")}</Badge>}
            <span>
              {t("admin.registered")}: {formatDateTime(user.created_at, i18n.language)}
            </span>
          </div>
          <UserForm key={user.id} user={user} onSaved={(saved) => setData({ ...data, user: saved })} />

          {user.is_admin && (
            <Card className="mt-6">
              <h2 className="m-0 mb-2 text-lg font-bold text-ink">{t("admin.recent_activity")}</h2>
              <ActivityList actions={actions} />
            </Card>
          )}
        </div>

        <div className="min-w-0">
          <h2 className="mt-0 mb-3 text-lg font-bold text-ink">
            {t("admin.user_tickets", { count: user.tickets_count })}
          </h2>
          {tickets.length === 0 ? (
            <p className="text-subtle">{t("admin.no_tickets")}</p>
          ) : (
            <Table head={[t("admin.ticket_code"), t("admin.journey"), t("admin.price"), t("admin.status")]}>
              {tickets.map((ticket) => (
                <tr key={ticket.id} className="hover:bg-canvas">
                  <td className={cellClass}>
                    <Link to={`/admin/tickets/${ticket.id}`} className="font-mono text-brand">
                      {ticket.ticket_code}
                    </Link>
                  </td>
                  <td className={cellClass}>
                    {ticket.journey?.from_stop?.stop_name} → {ticket.journey?.to_stop?.stop_name}
                    <div className="text-xs text-subtle">
                      {formatDateTime(ticket.journey?.departure_time, i18n.language)}
                    </div>
                  </td>
                  <td className={cellClass}>{formatPrice(ticket.price, ticket.currency)}</td>
                  <td className={cellClass}>
                    <TicketStatusBadge status={ticket.status} />
                  </td>
                </tr>
              ))}
            </Table>
          )}
        </div>
      </div>
    </>
  );
}

export default UserEdit;
