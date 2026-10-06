import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import "../styles/payment.css";

function PaymentSuccess() {
  const { t } = useTranslation();

  return (
    <main className="page page-narrow payment-page">
      <h1>{t("payment.success_title")}</h1>
      <p>{t("payment.success_text")}</p>

      <Link className="btn" to="/profile/tickets">
        {t("payment.to_tickets")}
      </Link>
    </main>
  );
}

export default PaymentSuccess;
