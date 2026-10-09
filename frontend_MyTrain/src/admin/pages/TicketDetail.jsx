import { Link, useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import * as adminApi from "../../api/admin";
import { useAdminAction, useAdminQuery } from "../hooks";
import { Alert, Badge, Button, Card, PageHeader, Table, TicketStatusBadge, cellClass } from "../ui";
import { formatDateTime, formatPrice } from "../../utils/format";

const PAYMENT_COLORS = { paid: "green", pending: "amber", refunded: "gray", failed: "red", cancelled: "gray" };

function Row({ label, children }) {
  return (
    <div className="flex justify-between gap-4 border-b border-line py-2 text-sm last:border-b-0">
      <span className="text-subtle">{label}</span>
      <span className="text-right font-medium text-ink">{children}</span>
    </div>
  );
}

/**
 * One ticket with its passenger, journey and payments, plus the allowed actions:
 * unpaid -> mark paid / cancel, paid -> refund. Price and route are never edited.
 */
function TicketDetail() {
  const { ticketId } = useParams();
  const { t, i18n } = useTranslation();
  const { data: ticket, error, setData } = useAdminQuery(adminApi.ticket, ticketId);
  const { busy, message, run } = useAdminAction();

  const act = async (action, confirmText, successText) => {
    if (confirmText && !window.confirm(confirmText)) return;
    const response = await run(() => action(ticketId), successText);
    if (response) setData(response.data.ticket);
  };

  if (!ticket) {
    return error ? <Alert>{error}</Alert> : <p className="text-subtle">{t("common.loading")}</p>;
  }

  const journey = ticket.journey;

  return (
    <>
      <PageHeader title={`${t("admin.ticket")} ${ticket.ticket_code}`}>
        <Link to="/admin/tickets" className="text-sm text-subtle">
          ← {t("admin.back_to_tickets")}
        </Link>
      </PageHeader>

      <Alert type={message?.type}>{message?.text}</Alert>

      <div className="mb-4 flex flex-wrap gap-2">
        {ticket.status === "pending" && (
          <>
            <Button
              variant="success"
              disabled={busy}
              onClick={() => act(adminApi.markTicketPaid, t("admin.confirm_mark_paid"), t("admin.marked_paid"))}
            >
              {t("admin.mark_paid")}
            </Button>
            <Button
              variant="danger"
              disabled={busy}
              onClick={() => act(adminApi.cancelTicket, t("admin.confirm_cancel"), t("admin.cancelled"))}
            >
              {t("admin.cancel_ticket")}
            </Button>
          </>
        )}
        {ticket.status === "paid" && (
          <Button
            variant="danger"
            disabled={busy}
            onClick={() => act(adminApi.refundTicket, t("admin.confirm_refund"), t("admin.refunded"))}
          >
            {t("admin.refund")}
          </Button>
        )}
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <Card>
          <h2 className="mt-0 mb-2 text-base font-semibold">{t("admin.ticket")}</h2>
          <Row label={t("admin.status")}>
            <TicketStatusBadge status={ticket.status} />
          </Row>
          <Row label={t("admin.price")}>{formatPrice(ticket.price, ticket.currency)}</Row>
          <Row label={t("admin.purchased_at")}>{formatDateTime(ticket.purchased_at, i18n.language)}</Row>
        </Card>

        <Card>
          <h2 className="mt-0 mb-2 text-base font-semibold">{t("admin.passenger")}</h2>
          <Row label={t("auth.name")}>
            <Link to={`/admin/users/${ticket.user?.id}`} className="text-brand">
              {ticket.user?.name}
            </Link>
          </Row>
          <Row label={t("auth.email")}>{ticket.user?.email}</Row>
        </Card>

        <Card>
          <h2 className="mt-0 mb-2 text-base font-semibold">{t("admin.journey")}</h2>
          <Row label={t("admin.from")}>{journey?.from_stop?.stop_name}</Row>
          <Row label={t("admin.to")}>{journey?.to_stop?.stop_name}</Row>
          <Row label={t("admin.departure")}>{formatDateTime(journey?.departure_time, i18n.language)}</Row>
          <Row label={t("admin.arrival")}>{formatDateTime(journey?.arrival_time, i18n.language)}</Row>
          <Row label={t("admin.train")}>
            {journey?.trip?.route?.route_long_name || journey?.trip?.trip_headsign} ({journey?.trip_id})
          </Row>
        </Card>
      </div>

      <h2 className="mt-8 mb-3 text-lg font-bold text-ink">{t("admin.payments")}</h2>
      {ticket.payments.length === 0 ? (
        <p className="text-subtle">{t("admin.no_payments")}</p>
      ) : (
        <Table head={[t("admin.provider"), t("admin.amount"), t("admin.status"), t("admin.paid_at")]}>
          {ticket.payments.map((payment) => (
            <tr key={payment.id}>
              <td className={cellClass}>{payment.provider}</td>
              <td className={cellClass}>{formatPrice(payment.amount, payment.currency)}</td>
              <td className={cellClass}>
                <Badge color={PAYMENT_COLORS[payment.status]}>{t(`admin.payment_status.${payment.status}`)}</Badge>
              </td>
              <td className={cellClass}>{formatDateTime(payment.paid_at, i18n.language) || "—"}</td>
            </tr>
          ))}
        </Table>
      )}
    </>
  );
}

export default TicketDetail;
