import { useEffect, useRef } from "react";
import { useTranslation } from "react-i18next";
import mapboxgl from "mapbox-gl";
import "mapbox-gl/dist/mapbox-gl.css";
import * as mapApi from "../api/map";
import { createEcho } from "../api/echo";
import "../styles/map.css";

mapboxgl.accessToken = import.meta.env.VITE_MAPBOX_TOKEN;

const RIGA = [24.1, 56.95];

// A DB row and a broadcast payload both look like a TrainPosition; make the coordinates numbers.
function normalizePosition(raw) {
  const position = raw?.position ?? raw;
  const lng = Number(position?.longitude);
  const lat = Number(position?.latitude);

  if (!Number.isFinite(lng) || !Number.isFinite(lat)) return null;

  return { ...position, longitude: lng, latitude: lat };
}

// Built with DOM nodes (not an HTML string) so train names can't inject HTML.
function popupContent(position) {
  const el = document.createElement("div");
  const title = document.createElement("strong");
  title.textContent = position.train?.name || `Train ${position.train_id}`;

  el.append(title, document.createElement("br"), `${position.speed ?? 0} km/h`);
  return el;
}

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

  useEffect(() => {
    let disposed = false;
    const isDisposed = () => disposed;
    const markers = {};
    const drawnTrips = new Set();
    let echo = null;

    const map = new mapboxgl.Map({
      container: containerRef.current,
      style: "mapbox://styles/mapbox/streets-v11",
      center: RIGA,
      zoom: 8,
    });
    map.addControl(new mapboxgl.NavigationControl(), "top-right");

    const showTrain = (position) => {
      const id = position.train_id;
      const lngLat = [position.longitude, position.latitude];

      if (markers[id]) {
        markers[id].getPopup()?.setDOMContent(popupContent(position));
        animateMarker(markers[id], lngLat, isDisposed);
        return;
      }

      const el = document.createElement("div");
      el.className = "train-marker";

      markers[id] = new mapboxgl.Marker(el)
        .setLngLat(lngLat)
        .setPopup(new mapboxgl.Popup({ offset: 14 }).setDOMContent(popupContent(position)))
        .addTo(map);
    };

    // Draws the line of a trip's stations once. A failure here never blocks the markers.
    const drawRoute = async (tripId) => {
      if (!tripId || drawnTrips.has(tripId)) return;
      drawnTrips.add(tripId);

      try {
        const { data: coordinates } = await mapApi.route(tripId);
        if (disposed || !Array.isArray(coordinates) || coordinates.length < 2) return;

        const sourceId = `route-${tripId}`;
        map.addSource(sourceId, {
          type: "geojson",
          data: { type: "Feature", geometry: { type: "LineString", coordinates } },
        });
        map.addLayer({
          id: `${sourceId}-line`,
          type: "line",
          source: sourceId,
          layout: { "line-cap": "round", "line-join": "round" },
          paint: { "line-color": "#0b63ce", "line-width": 4 },
        });
      } catch (err) {
        console.warn(`[map] could not draw route for trip ${tripId}`, err);
      }
    };

    const handlePosition = (raw) => {
      const position = normalizePosition(raw);
      if (!position) return;

      showTrain(position);
      drawRoute(position.trip_id);
    };

    map.on("load", async () => {
      // 1. Last known position of every train.
      try {
        const { data } = await mapApi.trains();
        if (!disposed && Array.isArray(data)) data.forEach(handlePosition);
      } catch (err) {
        console.error("[map] failed to load /map/trains", err);
      }

      // 2. Live updates over WebSocket. The leading dot matches broadcastAs() in the backend event.
      if (disposed) return;
      try {
        echo = createEcho();
        echo.channel("map-trains").listen(".TrainPositionUpdated", handlePosition);
      } catch (err) {
        console.error("[map] Reverb subscription failed", err);
      }
    });

    return () => {
      disposed = true;
      if (echo) {
        echo.leave("map-trains");
        echo.disconnect();
      }
      map.remove();
    };
  }, []);

  return (
    <main className="page">
      <h1>{t("map.title")}</h1>
      <p className="muted">{t("map.subtitle")}</p>
      <div ref={containerRef} className="map-container" />
    </main>
  );
}

export default TrainMap;
