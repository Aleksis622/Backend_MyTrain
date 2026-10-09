import { useTranslation } from "react-i18next";

// 95 -> "1 h 35 min"
function useDurationText() {
  const { t } = useTranslation();

  return (minutes) => {
    const hours = Math.floor(minutes / 60);
    const rest = Math.round(minutes % 60);
    const h = `${hours} ${t("profile.hours_short")}`;
    const min = `${rest} ${t("profile.minutes_short")}`;
    if (hours === 0) return min;
    return rest === 0 ? h : `${h} ${min}`;
  };
}

export default useDurationText;
