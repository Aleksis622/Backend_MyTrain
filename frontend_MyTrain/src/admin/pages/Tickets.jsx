import { useState } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import * as adminApi from "../../api/admin";
import { useAdminQuery } from "../hooks";
import { Alert, Button, Field, PageHeader, Pagination, Table, TicketStatusBadge, cellClass, inputClass } from "../ui";
import { formatDateTime, formatPrice } from "../../utils/format";

const STATUSES = ["pending", "paid", "cancelled", "refunded"];

function Tickets() {
  const { t, i18n } = useTranslation();
  const [searchText, setSearchText] = useState("");
  const [filters, setFilters] = useState({ search: "", status: "", date: "", page: 1 });
  const { data, error, loading } = useAdminQuery(adminApi.tickets, filters);

  // Any filter change starts again from page 1.
  const setFilter = (name, value) => setFilters({ ...filters, [name]: value, page: 1 });

  return (
    <>
      <PageHeader title={t("admin.nav_tickets")} />

      <form
        className="mb-4 grid gap-3 rounded-2xl border border-line bg-card p-4 shadow-card md:grid-cols-[1fr_auto_auto_auto] md:items-end"
        onSubmit={(e) => {
          e.preventDefault();
          setFilter("search", searchText.trim());
        }}
      >
        <Field label={t("admin.search_tickets")}>
          <input className={inputClass} value={searchText} onChange={(e) => setSearchText(e.target.value)} />
        </Field>
        <Field label={t("admin.status")}>
          <select className={inputClass} value={filters.status} onChange={(e) => setFilter("status", e.target.value)}>
            <option value="">{t("admin.all")}</option>
            {STATUSES.map((status) => (
              <option key={status} value={status}>
                {t(`admin.ticket_status.${status}`)}
              </option>
            ))}
          </select>
        </Field>
        <Field label={t("admin.travel_date")}>
          <input
            type="date"
            className={inputClass}
            value={filters.date}
            onChange={(e) => setFilter("date", e.target.value)}
          />
        </Field>
        <Button type="submit">{t("admin.search")}</Button>
      </form>

      <Alert>{error}</Alert>
      {loading && !data && <p className="text-subtle">{t("common.loading")}</p>}

      {data && data.data.length === 0 && <p className="text-subtle">{t("admin.no_tickets")}</p>}

      {data && data.data.length > 0 && (
        <>
          <p className="mb-2 text-sm text-subtle">{t("admin.results", { count: data.total })}</p>
          <Table
            head={[
              t("admin.ticket_code"),
              t("admin.passenger"),
              t("admin.journey"),
              t("admin.price"),
              t("admin.status"),
            ]}
          >
            {data.data.map((ticket) => (
              <tr key={ticket.id} className="hover:bg-canvas">
                <td className={cellClass}>
                  <Link to={`/admin/tickets/${ticket.id}`} className="font-mono text-brand">
                    {ticket.ticket_code}
                  </Link>
                </td>
                <td className={cellClass}>
                  {ticket.user?.name}
                  <div className="text-xs text-subtle">{ticket.user?.email}</div>
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
          <Pagination page={data} onChange={(page) => setFilters({ ...filters, page })} />
        </>
      )}
    </>
  );
}

export default Tickets;
