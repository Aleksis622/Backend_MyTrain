import { useTranslation } from "react-i18next";
import { TRAIN_ICON_PATH } from "./dom";

const TrainIcon = ({ size = 12 }) => (
  <svg viewBox="0 0 24 24" width={size} height={size} aria-hidden="true">
    <path d={TRAIN_ICON_PATH} fill="currentColor" />
  </svg>
);

/**
 * Key for the map symbols, shown in the corner of the map.
 */
function MapLegend() {
  const { t } = useTranslation();

  return (
    <div className="map-legend">
      <div className="map-legend-title">{t("map.legend_title")}</div>
      <ul>
        <li>
          <span className="legend-train is-moving">
            <TrainIcon />
          </span>
          {t("map.legend_moving")}
        </li>
        <li>
          <span className="legend-train is-at-station">
            <TrainIcon />
          </span>
          {t("map.legend_at_station")}
        </li>
        <li>
          <span className="legend-train is-moving is-delayed">
            <TrainIcon />
          </span>
          {t("map.legend_delayed")}
        </li>
        <li>
          <span className="legend-station">
            <TrainIcon size={10} />
          </span>
          {t("map.legend_station")}
        </li>
        <li>
          <span className="legend-line is-ahead" />
          {t("map.legend_route_ahead")}
        </li>
        <li>
          <span className="legend-line is-passed" />
          {t("map.legend_route_passed")}
        </li>
      </ul>
    </div>
  );
}

export default MapLegend;
