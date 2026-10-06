import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { one as getTicket } from "../api/tickets";
import { create as createPayment } from "../api/payments";
import { getErrorMessage } from "../api/api";
import { formatPrice } from "../utils/format";
import "../styles/payment.css";

const PROVIDERS = [
  { id: "card", label: "payment.card" },
  { id: "swedbank", label: "payment.bank" },
  { id: "paypal", label: "payment.paypal" },
];

// Step 1 of paying: show the ticket and choose a payment method.
function Payment() {
  const { t } = useTranslation();
  const { ticketId } = useParams();
  const navigate = useNavigate();

  const [ticket, setTicket] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    getTicket(ticketId)
      .then((res) => setTicket(res.data))
      .catch((err) => setError(getErrorMessage(err, t("common.error"))));
  }, [ticketId, t]);

  const startPayment = async (provider) => {
    setError("");
    try {
      const res = await createPayment(ticketId, provider);
      navigate(`/payment/${res.data.payment.id}/provider`);
    } catch (err) {
      setError(getErrorMessage(err, t("common.error")));
    }
  };

  return (
    <main className="page page-narrow payment-page">
      <h1>{t("payment.choose_method")}</h1>

      {error && <p className="alert alert-error">{error}</p>}

      {ticket && (
        <div className="card payment-summary">
          <h3>
            {ticket.journey?.from_stop?.stop_name} → {ticket.journey?.to_stop?.stop_name}
          </h3>
          <p className="payment-amount">{formatPrice(ticket.price, ticket.currency)}</p>
        </div>
      )}

      {ticket?.status === "pending" && (
        <div className="payment-methods">
          {PROVIDERS.map(({ id, label }) => (
            <button key={id} className="btn btn-block" onClick={() => startPayment(id)}>
              {t(label)}
            </button>
          ))}
        </div>
      )}
    </main>
  );
}

export default Payment;
