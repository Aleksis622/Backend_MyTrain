import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import "../styles/payment.css";

function PaymentFailed() {
  const { t } = useTranslation();

  return (
    <main className="page page-narrow payment-page">
      <h1>{t("payment.failed_title")}</h1>
      <p>{t("payment.failed_text")}</p>

      <Link className="btn" to="/tickets">
        {t("payment.to_tickets")}
      </Link>
    </main>
  );
}

export default PaymentFailed;
