import { useState } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import * as adminApi from "../../api/admin";
import { useAuth } from "../../context/Auth";
import Icon from "../../components/Icon";
import ActivityList from "../ActivityList";
import { useAdminAction, useAdminQuery } from "../hooks";
import { Alert, Badge, Button, Card, Field, PageHeader, inputClass } from "../ui";
import { formatDateTime, initials } from "../../utils/format";

function CardTitle({ icon, children }) {
  return (
    <h2 className="m-0 mb-4 flex items-center gap-2 text-lg font-bold text-ink">
      <Icon name={icon} size={18} />
      {children}
    </h2>
  );
}

/**
 * Name and email. Saving also refreshes the user in the top bar.
 */
function DetailsForm({ user, onSaved }) {
  const { t } = useTranslation();
  const { refreshUser } = useAuth();
  const [fields, setFields] = useState({ name: user.name ?? "", email: user.email });
  const { busy, message, run } = useAdminAction();

  const change = (name) => (e) => setFields({ ...fields, [name]: e.target.value });

  const save = async (e) => {
    e.preventDefault();
    const response = await run(() => adminApi.updateAccount(fields), t("admin.saved"));
    if (response) {
      onSaved();
      refreshUser();
    }
  };

  return (
    <Card>
      <CardTitle icon="user">{t("admin.account_details")}</CardTitle>
      <form className="flex flex-col gap-4" onSubmit={save}>
        <Field label={t("auth.name")}>
          <input className={inputClass} value={fields.name} onChange={change("name")} required />
        </Field>
        <Field label={t("auth.email")}>
          <input type="email" className={inputClass} value={fields.email} onChange={change("email")} required />
        </Field>
        <Alert type={message?.type}>{message?.text}</Alert>
        <div>
          <Button type="submit" disabled={busy}>
            {t("admin.save")}
          </Button>
        </div>
      </form>
    </Card>
  );
}

const EMPTY_PASSWORDS = { currentPassword: "", password: "", passwordConfirmation: "" };

function PasswordForm({ onSaved }) {
  const { t } = useTranslation();
  const [fields, setFields] = useState(EMPTY_PASSWORDS);
  const { busy, message, run } = useAdminAction();

  const change = (name) => (e) => setFields({ ...fields, [name]: e.target.value });

  const save = async (e) => {
    e.preventDefault();
    const response = await run(() => adminApi.changePassword(fields), t("admin.password_changed"));
    if (response) {
      setFields(EMPTY_PASSWORDS);
      onSaved();
    }
  };

  return (
    <Card>
      <CardTitle icon="key">{t("admin.change_password")}</CardTitle>
      <form className="flex flex-col gap-4" onSubmit={save}>
        <Field label={t("admin.current_password")}>
          <input
            type="password"
            autoComplete="current-password"
            className={inputClass}
            value={fields.currentPassword}
            onChange={change("currentPassword")}
            required
          />
        </Field>
        <Field label={t("auth.new_password")}>
          <input
            type="password"
            autoComplete="new-password"
            minLength={8}
            className={inputClass}
            value={fields.password}
            onChange={change("password")}
            required
          />
        </Field>
        <Field label={t("admin.repeat_password")}>
          <input
            type="password"
            autoComplete="new-password"
            minLength={8}
            className={inputClass}
            value={fields.passwordConfirmation}
            onChange={change("passwordConfirmation")}
            required
          />
        </Field>
        <p className="m-0 text-xs text-subtle">{t("admin.password_hint")}</p>
        <Alert type={message?.type}>{message?.text}</Alert>
        <div>
          <Button type="submit" disabled={busy}>
            {t("admin.change_password")}
          </Button>
        </div>
      </form>
    </Card>
  );
}

/**
 * "My account": who I am, my details, password, and what I did recently.
 */
function Account() {
  const { t, i18n } = useTranslation();
  const { data, error, reload } = useAdminQuery(adminApi.account);

  if (!data) {
    return error ? <Alert>{error}</Alert> : <p className="text-subtle">{t("common.loading")}</p>;
  }

  const { user } = data;

  return (
    <>
      <PageHeader title={t("admin.my_account")} />

      <Card className="mb-6 flex flex-wrap items-center gap-5">
        <span className="grid size-16 place-items-center rounded-full bg-brand text-xl font-bold text-white">
          {initials(user.name) || "A"}
        </span>
        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center gap-2">
            <span className="text-xl font-extrabold text-ink">{user.name}</span>
            <Badge color="blue">{t("admin.role_admin")}</Badge>
          </div>
          <div className="text-sm text-subtle">{user.email}</div>
          <div className="text-xs text-subtle">
            {t("admin.member_since", { date: formatDateTime(user.created_at, i18n.language) })}
          </div>
          {/* Tickets the admin bought for themselves on the website */}
          <Link to="/profile/tickets" className="mt-1 inline-flex items-center gap-1 text-sm font-semibold text-brand no-underline">
            <Icon name="ticket" size={16} />
            {t("tickets.title")}
          </Link>
        </div>
        <div className="flex gap-6 text-center">
          <div>
            <div className="text-2xl font-extrabold text-ink">{data.actions_today}</div>
            <div className="text-xs text-subtle">{t("admin.actions_today")}</div>
          </div>
          <div>
            <div className="text-2xl font-extrabold text-ink">{data.actions_total}</div>
            <div className="text-xs text-subtle">{t("admin.actions_total")}</div>
          </div>
        </div>
      </Card>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="flex flex-col gap-6">
          <DetailsForm user={user} onSaved={reload} />
          <PasswordForm onSaved={reload} />
        </div>

        <Card className="self-start">
          <div className="mb-2 flex items-center justify-between gap-3">
            <h2 className="m-0 flex items-center gap-2 text-lg font-bold text-ink">
              <Icon name="activity" size={18} />
              {t("admin.my_activity")}
            </h2>
            <Link to="/admin/activity" className="text-sm font-semibold text-brand no-underline">
              {t("admin.see_full_log")}
            </Link>
          </div>
          <ActivityList actions={data.recent_actions} />
        </Card>
      </div>
    </>
  );
}

export default Account;
