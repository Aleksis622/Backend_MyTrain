import { useTranslation } from "react-i18next";
import TrainSearch from "../components/TrainSearch";

function Trains() {
  const { t } = useTranslation();

  return (
    <main className="page">
      <h1>{t("trains.title")}</h1>
      <TrainSearch />
    </main>
  );
}

export default Trains;
