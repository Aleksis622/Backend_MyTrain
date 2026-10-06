import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";

function NotFound() {
  const { t } = useTranslation();

  return (
    <main className="page">
      <h1>{t("common.not_found")}</h1>
      <Link className="btn" to="/">
        {t("common.back_home")}
      </Link>
    </main>
  );
}

export default NotFound;
