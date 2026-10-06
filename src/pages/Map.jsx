import { useEffect, useRef, useState } from "react";
import { useTranslation } from "react-i18next";
import mapboxgl from "mapbox-gl";
import "mapbox-gl/dist/mapbox-gl.css";
import * as mapApi from "../api/map";
import { formatTime } from "../utils/format";
import "../styles/map.css";

mapboxgl.accessToken = import.meta.env.VITE_MAPBOX_TOKEN;

const RIGA = [24.1, 56.95];
const REFRESH_MS = 15000;
const ROUTE_LAYER = "selected-route";

// Slides a marker to its new position over ~0.5 s instead of jumping.
function animateMarker(marker, [endLng, endLat], isDisposed) {
  const start = marker.getLngLat();
  const frames = 30;
  let frame = 0;

  const step = () => {
    if (isDisposed()) return;
    frame++;
    marker.setLngLat([
      start.lng + ((endLng - start.lng) * frame) / frames,
      start.lat + ((endLat - start.lat) * frame) / frames,
    ]);
    if (frame < frames) requestAnimationFrame(step);
  };
  step();
}

function TrainMap() {
  const { t } = useTranslation();
  const containerRef = useRef(null);
  const [trainCount, setTrainCount] = useState(null);

  
  const tRef = useRef(t);
  useEffect(() => {
    tRef.current = t;
  }, [t]);

  useEffect(() => {
    let disposed = false;
    const isDisposed = () => disposed;
    const markers = {}; 
    let timer = null;

    const map = new mapboxgl.Map({
      container: containerRef.current,
      style: "mapbox://styles/mapbox/streets-v11",
      center: RIGA,
      zoom: 8,
    });
    map.addControl(new mapboxgl.NavigationControl(), "top-right");

   
    const popupContent = (train) => {
      const tr = tRef.current;
      const el = document.createElement("div");
      const title = document.createElement("strong");
      title.textContent = train.route_name || train.headsign || train.trip_id;

      const status =
        train.status === "at_station"
          ? tr("map.at_station", { station: train.current_stop })
          : tr("map.between", { from: train.previous_stop, to: train.next_stop });

      el.append(title, document.createElement("br"), status);

      if (train.next_stop) {
        const next = tr("map.next_stop", {
          station: train.next_stop,
          time: formatTime(train.next_arrival),
        });
        el.append(document.createElement("br"), next);
      }
      return el;
    };

    
    const showRoute = async (tripId) => {
      try {
        const { data: coordinates } = await mapApi.route(tripId);
        if (disposed || !Array.isArray(coordinates) || coordinates.length < 2) return;

        if (map.getLayer(ROUTE_LAYER)) map.removeLayer(ROUTE_LAYER);
        if (map.getSource(ROUTE_LAYER)) map.removeSource(ROUTE_LAYER);

        map.addSource(ROUTE_LAYER, {
          type: "geojson",
          data: { type: "Feature", geometry: { type: "LineString", coordinates } },
        });
        map.addLayer({
          id: ROUTE_LAYER,
          type: "line",
          source: ROUTE_LAYER,
          layout: { "line-cap": "round", "line-join": "round" },
          paint: { "line-color": "#0b63ce", "line-width": 4 },
        });
      } catch (err) {
        console.warn(`[map] could not draw route for trip ${tripId}`, err);
      }
    };

    const showTrain = (train) => {
      const lngLat = [Number(train.longitude), Number(train.latitude)];
      const existing = markers[train.trip_id];

      if (existing) {
        existing.getElement().classList.toggle("at-station", train.status === "at_station");
        existing.getPopup().setDOMContent(popupContent(train));
        animateMarker(existing, lngLat, isDisposed);
        return;
      }

      const el = document.createElement("div");
      el.className = "train-marker";
      el.classList.toggle("at-station", train.status === "at_station");
      el.addEventListener("click", () => showRoute(train.trip_id));

      markers[train.trip_id] = new mapboxgl.Marker(el)
        .setLngLat(lngLat)
        .setPopup(new mapboxgl.Popup({ offset: 14 }).setDOMContent(popupContent(train)))
        .addTo(map);
    };

    const refresh = async () => {
      try {
        const { data: trains } = await mapApi.trains();
        if (disposed || !Array.isArray(trains)) return;

        trains.forEach(showTrain);

       
        const running = new Set(trains.map((train) => train.trip_id));
        Object.keys(markers).forEach((tripId) => {
          if (!running.has(tripId)) {
            markers[tripId].remove();
            delete markers[tripId];
          }
        });

        setTrainCount(trains.length);
      } catch (err) {
        console.error("[map] failed to load /map/trains", err);
      }
    };

    map.on("load", () => {
      if (disposed) return;
      refresh();
      timer = setInterval(refresh, REFRESH_MS);
    });

    return () => {
      disposed = true;
      clearInterval(timer);
      map.remove();
    };
  }, []);

  return (
    <main className="page">
      <h1>{t("map.title")}</h1>
      <p className="muted">
        {trainCount !== null && <strong>{t("map.running", { count: trainCount })}. </strong>}
        {t("map.subtitle")}
      </p>
      <div ref={containerRef} className="map-container" />
    </main>
  );
}

export default TrainMap;
