import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { one as getTicket } from "../api/tickets";
import { create as createPayment } from "../api/payments";
import { getErrorMessage } from "../api/api";
import { formatDateTime, formatPrice } from "../utils/format";
import "../styles/payment.css";

/**
 * Step 1 of paying: the ticket summary and one button that opens Stripe's checkout page.
 * Stripe runs in test mode: no real money is charged (the notice says so).
 */
function Payment() {
  const { t, i18n } = useTranslation();
  const { ticketId } = useParams();

  const [ticket, setTicket] = useState(null);
  const [error, setError] = useState("");
  const [redirecting, setRedirecting] = useState(false);

  useEffect(() => {
    getTicket(ticketId)
      .then((res) => setTicket(res.data))
      .catch((err) => setError(getErrorMessage(err, t("common.error"))));
  }, [ticketId, t]);

  const pay = async () => {
    setError("");
    setRedirecting(true);
    try {
      const res = await createPayment(ticketId);
      window.location.assign(res.data.checkout_url); // Stripe's own page
    } catch (err) {
      setError(getErrorMessage(err, t("common.error")));
      setRedirecting(false);
    }
  };

  return (
    <main className="page page-narrow payment-page">
      <h1>{t("payment.title")}</h1>

      {error && <p className="alert alert-error">{error}</p>}

      {ticket && (
        <div className="card payment-summary">
          <h3>
            {ticket.journey?.from_stop?.stop_name} → {ticket.journey?.to_stop?.stop_name}
          </h3>
          <p className="muted">{formatDateTime(ticket.journey?.departure_time, i18n.language)}</p>
          <p className="payment-amount">{formatPrice(ticket.price, ticket.currency)}</p>
        </div>
      )}

      {ticket?.status === "pending" && (
        <>
          <button className="btn btn-block" onClick={pay} disabled={redirecting}>
            {redirecting ? t("payment.redirecting") : t("payment.pay_with_card")}
          </button>

          <div className="alert alert-warning payment-test-note">
            <strong>{t("payment.test_mode_title")}</strong>
            <p>{t("payment.test_mode_text")}</p>
            <code>4242 4242 4242 4242</code>
          </div>
        </>
      )}

      {ticket && ticket.status !== "pending" && (
        <p className="alert alert-success">{t(`payment.ticket_is_${ticket.status}`)}</p>
      )}
    </main>
  );
}

export default Payment;
