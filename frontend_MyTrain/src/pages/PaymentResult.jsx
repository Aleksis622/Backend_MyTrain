import { useEffect, useState } from "react";
import { Link, useParams, useSearchParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { cancel as cancelPayment, one as getPayment } from "../api/payments";
import { getErrorMessage } from "../api/api";
import { formatPrice } from "../utils/format";
import "../styles/payment.css";

const POLL_MS = 2000;
const MAX_POLLS = 15; // ~30 s, then the user can check again by hand

/**
 * Where Stripe sends the user back (/payment/:id/result, with ?cancelled=1 after "cancel").
 * The page never decides the outcome itself: it shows the status stored on the server,
 * which only Stripe can change to "paid". While the payment is still pending it asks again.
 */
function PaymentResult() {
  const { t } = useTranslation();
  const { paymentId } = useParams();
  const [searchParams] = useSearchParams();
  const cancelled = searchParams.get("cancelled") === "1";

  const [payment, setPayment] = useState(null);
  const [error, setError] = useState("");
  const [polls, setPolls] = useState(0);

  // First load: either close the checkout ("cancel" on Stripe's page) or read the status.
  useEffect(() => {
    let stopped = false;
    const load = cancelled ? cancelPayment(paymentId).then((res) => res.data.payment) : getPayment(paymentId).then((res) => res.data);

    load
      .then((data) => !stopped && setPayment(data))
      .catch((err) => !stopped && setError(getErrorMessage(err, t("common.error"))));

    return () => {
      stopped = true;
    };
  }, [paymentId, cancelled, t]);

  // Still pending (Stripe's confirmation not here yet): ask again every 2 seconds for a while.
  const waiting = payment?.status === "pending" && polls < MAX_POLLS;
  useEffect(() => {
    if (!waiting) return undefined;

    const timer = setTimeout(() => {
      getPayment(paymentId)
        .then((res) => setPayment(res.data))
        .catch(() => {})
        .finally(() => setPolls((n) => n + 1));
    }, POLL_MS);

    return () => clearTimeout(timer);
  }, [waiting, polls, paymentId]);

  const status = payment?.status;
  const ticketId = payment?.ticket_id;

  return (
    <main className="page page-narrow payment-page">
      {error && <p className="alert alert-error">{error}</p>}
      {!payment && !error && <p className="muted">{t("common.loading")}</p>}

      {payment && (
        <>
          <h1>{t(`payment.result.${status}_title`)}</h1>
          <p>{t(`payment.result.${status}_text`)}</p>
          <p className="payment-amount">{formatPrice(payment.amount, payment.currency)}</p>

          {status === "pending" && (
            <p className="muted">
              {waiting ? t("payment.result.checking") : t("payment.result.still_pending")}
            </p>
          )}

          <div className="payment-actions">
            {status === "pending" && !waiting && (
              <button className="btn btn-outline" onClick={() => setPolls(0)}>
                {t("payment.result.check_again")}
              </button>
            )}
            {(status === "cancelled" || status === "failed") && (
              <Link className="btn" to={`/payment/${ticketId}`}>
                {t("payment.result.try_again")}
              </Link>
            )}
            <Link className={status === "paid" ? "btn" : "btn btn-outline"} to="/profile/tickets">
              {t("payment.to_tickets")}
            </Link>
          </div>
        </>
      )}
    </main>
  );
}

export default PaymentResult;
