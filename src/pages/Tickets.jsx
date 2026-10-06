import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { all as getTickets, cancel as cancelTicket } from "../api/tickets";
import { refund as refundPayment } from "../api/payments";
import { getErrorMessage, listFrom } from "../api/api";
import { formatDateTime, formatPrice } from "../utils/format";

function Tickets() {
  const { t, i18n } = useTranslation();
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const loadTickets = useCallback(
    () =>
      getTickets()
        .then((res) => setTickets(listFrom(res)))
        .catch((err) => setError(getErrorMessage(err, t("common.error"))))
        .finally(() => setLoading(false)),
    [t]
  );

  useEffect(() => {
    loadTickets();
  }, [loadTickets]);

  // Runs a cancel/refund after the user confirms, then reloads the list.
  const runAction = async (confirmText, action) => {
    if (!window.confirm(confirmText)) return;

    setError("");
    try {
      await action();
      await loadTickets();
    } catch (err) {
      setError(getErrorMessage(err, t("common.error")));
    }
  };

  if (loading) {
    return <main className="page">{t("common.loading")}</main>;
  }

  return (
    <main className="page">
      <h1>{t("tickets.title")}</h1>

      {error && <p className="alert alert-error">{error}</p>}
      {tickets.length === 0 && <p className="muted">{t("tickets.empty")}</p>}

      <div className="card-list">
        {tickets.map((ticket) => {
          const { journey, latest_payment: payment } = ticket;

          return (
            <div key={ticket.id} className="card">
              <h3>
                {journey?.from_stop?.stop_name} → {journey?.to_stop?.stop_name}
              </h3>

              <p>
                <span className={`badge badge-${ticket.status}`}>
                  {t(`tickets.status.${ticket.status}`)}
                </span>
              </p>
              <p>
                {t("tickets.departure")}: {formatDateTime(journey?.departure_time, i18n.language)}
              </p>
              <p>
                {t("tickets.price")}: {formatPrice(ticket.price, ticket.currency)}
              </p>
              <p className="muted">
                {t("tickets.code")}: {ticket.ticket_code}
              </p>

              <div className="card-actions">
                {ticket.status === "pending" && (
                  <>
                    <Link className="btn" to={`/payment/${ticket.id}`}>
                      {t("tickets.pay")}
                    </Link>
                    <button
                      className="btn btn-outline"
                      onClick={() => runAction(t("tickets.confirm_cancel"), () => cancelTicket(ticket.id))}
                    >
                      {t("tickets.cancel")}
                    </button>
                  </>
                )}

                {ticket.status === "paid" && payment?.status === "paid" && (
                  <button
                    className="btn btn-outline"
                    onClick={() => runAction(t("tickets.confirm_refund"), () => refundPayment(payment.id))}
                  >
                    {t("tickets.refund")}
                  </button>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </main>
  );
}

export default Tickets;
