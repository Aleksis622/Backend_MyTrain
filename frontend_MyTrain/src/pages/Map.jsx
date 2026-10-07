import { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import mapboxgl from "mapbox-gl";
import "mapbox-gl/dist/mapbox-gl.css";
import * as mapApi from "../api/map";
import { TRAIN_ICON_PATH } from "../components/map/dom";
import { createTrainMarkerElement, updateTrainMarkerElement } from "../components/map/trainMarker";
import { stationPopup, trainPopup } from "../components/map/popups";
import { addRouteLayers, clearRoute, fitRoute, showRoute } from "../components/map/routeLayer";
import MapLegend from "../components/map/MapLegend";
import "../styles/map.css";

mapboxgl.accessToken = import.meta.env.VITE_MAPBOX_TOKEN;

const RIGA = [24.1, 56.95];
const REFRESH_MS = 15000;
const STATION_SOURCE = "stations";
const STATION_LAYER = "station-icons";
const STATION_LABELS = "station-labels";
const STATION_ICON = "station-icon";

// Blue station sign with a white train, drawn at 2x for sharp screens.
const STATION_ICON_SVG = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">
  <rect x="2" y="2" width="36" height="36" rx="9" fill="#0b63ce" stroke="#ffffff" stroke-width="3"/>
  <path transform="translate(8 8)" d="${TRAIN_ICON_PATH}" fill="#ffffff"/>
</svg>`;

function loadStationIcon(map) {
  return new Promise((resolve, reject) => {
    const image = new Image(40, 40);
    image.onload = () => {
      if (!map.hasImage(STATION_ICON)) map.addImage(STATION_ICON, image, { pixelRatio: 2 });
      resolve();
    };
    image.onerror = reject;
    image.src = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(STATION_ICON_SVG)}`;
  });
}

// Stations as GeoJSON points for a Mapbox source.
const stationsToGeoJson = (stations) => ({
  type: "FeatureCollection",
  features: stations.map((station) => ({
    type: "Feature",
    geometry: { type: "Point", coordinates: [station.stop_lon, station.stop_lat] },
    properties: { stop_id: station.stop_id, stop_name: station.stop_name },
  })),
});

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
  const navigate = useNavigate();
  const containerRef = useRef(null);
  const [trainCount, setTrainCount] = useState(null);

  // The map is created once, so its handlers read the latest t() / navigate() through refs.
  const tRef = useRef(t);
  const navigateRef = useRef(navigate);
  useEffect(() => {
    tRef.current = t;
    navigateRef.current = navigate;
  }, [t, navigate]);

  useEffect(() => {
    let disposed = false;
    const isDisposed = () => disposed;
    const markers = {}; // trip_id -> mapboxgl.Marker
    const trains = {}; // trip_id -> latest position
    let timer = null;

    // The train whose route is drawn: { tripId, stops } (stops is null while loading).
    let selected = null;
    // Only one popup at a time; closing it (× or a click on the map) clears the selection.
    let popup = null;
    let popupTripId = null;

    const map = new mapboxgl.Map({
      container: containerRef.current,
      style: "mapbox://styles/mapbox/streets-v11",
      center: RIGA,
      zoom: 8,
    });
    map.addControl(new mapboxgl.NavigationControl(), "top-right");

    const popupHelpers = () => ({ t: tRef.current, navigate: (path) => navigateRef.current(path) });

    const openPopup = (lngLat, content, { offset, tripId = null }) => {
      const previous = popup;
      const next = new mapboxgl.Popup({ offset, maxWidth: "320px", className: "map-popup" })
        .setLngLat(lngLat)
        .setDOMContent(content);

      popup = next;
      popupTripId = tripId;
      next.on("close", () => {
        if (popup !== next) return; // replaced by another popup, not closed by the user
        popup = null;
        popupTripId = null;
        clearSelection();
      });

      previous?.remove();
      next.addTo(map);
    };

    const setSelectedMarker = (tripId) => {
      Object.entries(markers).forEach(([id, marker]) =>
        updateTrainMarkerElement(marker.getElement(), trains[id], id === tripId),
      );
    };

    const clearSelection = () => {
      selected = null;
      clearRoute(map);
      setSelectedMarker(null);
    };

    const selectTrain = async (tripId) => {
      const train = trains[tripId];
      if (!train) return;

      openPopup([train.longitude, train.latitude], trainPopup(train, popupHelpers()), { offset: 24, tripId });

      if (selected?.tripId === tripId) return;
      selected = { tripId, stops: null };
      setSelectedMarker(tripId);

      try {
        const { data } = await mapApi.route(tripId);
        if (disposed || selected?.tripId !== tripId) return;

        selected.stops = data.stops;
        showRoute(map, data.stops, trains[tripId]);
        fitRoute(map, data.stops, mapboxgl);
      } catch (err) {
        console.warn(`[map] could not load route for trip ${tripId}`, err);
      }
    };

    const showTrain = (train) => {
      const lngLat = [Number(train.longitude), Number(train.latitude)];
      const isSelected = selected?.tripId === train.trip_id;
      const existing = markers[train.trip_id];

      if (existing) {
        updateTrainMarkerElement(existing.getElement(), train, isSelected);
        animateMarker(existing, lngLat, isDisposed);
        return;
      }

      const element = createTrainMarkerElement(train);
      element.addEventListener("click", (e) => {
        e.stopPropagation();
        selectTrain(train.trip_id);
      });
      markers[train.trip_id] = new mapboxgl.Marker({ element }).setLngLat(lngLat).addTo(map);
    };

    const showStations = async () => {
      try {
        const [{ data: stations }] = await Promise.all([mapApi.stations(), loadStationIcon(map)]);
        if (disposed || !Array.isArray(stations)) return;

        map.addSource(STATION_SOURCE, { type: "geojson", data: stationsToGeoJson(stations) });
        map.addLayer({
          id: STATION_LAYER,
          type: "symbol",
          source: STATION_SOURCE,
          layout: {
            "icon-image": STATION_ICON,
            "icon-size": ["interpolate", ["linear"], ["zoom"], 6, 0.55, 10, 0.9, 13, 1.1],
            "icon-allow-overlap": true,
          },
        });
        // Names only when zoomed in, otherwise they cover the whole country.
        map.addLayer({
          id: STATION_LABELS,
          type: "symbol",
          source: STATION_SOURCE,
          minzoom: 10,
          layout: {
            "text-field": ["get", "stop_name"],
            "text-size": 12,
            "text-offset": [0, 1.3],
            "text-anchor": "top",
          },
          paint: { "text-color": "#1f2328", "text-halo-color": "#ffffff", "text-halo-width": 1.5 },
        });
        // The route line goes under the station icons.
        addRouteLayers(map, STATION_LAYER);

        map.on("click", STATION_LAYER, (e) => {
          const feature = e.features?.[0];
          if (!feature) return;
          openPopup(feature.geometry.coordinates, stationPopup(feature.properties, popupHelpers()), {
            offset: 14,
          });
        });
        map.on("mouseenter", STATION_LAYER, () => {
          map.getCanvas().style.cursor = "pointer";
        });
        map.on("mouseleave", STATION_LAYER, () => {
          map.getCanvas().style.cursor = "";
        });
      } catch (err) {
        console.error("[map] failed to load stations", err);
      }
    };

    const refresh = async () => {
      try {
        const { data } = await mapApi.trains();
        if (disposed || !Array.isArray(data)) return;

        data.forEach((train) => {
          trains[train.trip_id] = train;
          showTrain(train);
        });

        // Remove trains that finished their trip.
        const running = new Set(data.map((train) => train.trip_id));
        Object.keys(markers).forEach((tripId) => {
          if (!running.has(tripId)) {
            markers[tripId].remove();
            delete markers[tripId];
            delete trains[tripId];
          }
        });

        // Keep the selected train's route and card in step with its new position.
        if (selected && !running.has(selected.tripId)) {
          popup?.remove();
        } else if (selected) {
          const train = trains[selected.tripId];
          if (selected.stops) showRoute(map, selected.stops, train);
          if (popupTripId === selected.tripId) {
            popup.setLngLat([train.longitude, train.latitude]).setDOMContent(trainPopup(train, popupHelpers()));
          }
        }

        setTrainCount(data.length);
      } catch (err) {
        console.error("[map] failed to load /map/trains", err);
      }
    };

    map.on("load", () => {
      if (disposed) return;
      showStations();
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
      <div className="map-wrapper">
        <div ref={containerRef} className="map-container" />
        <MapLegend />
      </div>
    </main>
  );
}

export default TrainMap;
