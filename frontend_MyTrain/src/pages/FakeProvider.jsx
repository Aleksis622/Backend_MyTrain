import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { confirm as confirmPayment, one as getPayment } from "../api/payments";
import { getErrorMessage } from "../api/api";
import { formatPrice } from "../utils/format";
import "../styles/payment.css";

// Step 2 of paying: pretends to be the bank / card page. Replace with a real provider later.
function FakeProvider() {
  const { t } = useTranslation();
  const { paymentId } = useParams();
  const navigate = useNavigate();

  const [payment, setPayment] = useState(null);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    getPayment(paymentId)
      .then((res) => setPayment(res.data))
      .catch((err) => setError(getErrorMessage(err, t("common.error"))));
  }, [paymentId, t]);

  const handleConfirm = async () => {
    setError("");
    setLoading(true);
    try {
      await confirmPayment(paymentId);
      navigate(`/payment/${paymentId}/success`);
    } catch (err) {
      setError(getErrorMessage(err, t("common.error")));
      setLoading(false);
    }
  };

  return (
    <main className="page page-narrow payment-page">
      <h1>{t("payment.provider_title")}</h1>
      <p className="muted">{t("payment.provider_note")}</p>

      {error && <p className="alert alert-error">{error}</p>}

      {payment && (
        <>
          <p className="payment-amount">{formatPrice(payment.amount, payment.currency)}</p>

          <div className="payment-actions">
            <button className="btn btn-success" onClick={handleConfirm} disabled={loading}>
              {t("payment.confirm")}
            </button>
            <button
              className="btn btn-danger"
              onClick={() => navigate(`/payment/${paymentId}/failed`)}
              disabled={loading}
            >
              {t("payment.cancel")}
            </button>
          </div>
        </>
      )}
    </main>
  );
}

export default FakeProvider;
