import { formatTime } from "../../utils/format";

/**
 * The selected train's route: grey dashed for the part already travelled,
 * blue for the part still ahead, and the upcoming stops labelled with their arrival time.
 */
export const ROUTE_SOURCE = "selected-route";
const EMPTY = { type: "FeatureCollection", features: [] };

export function addRouteLayers(map, beforeLayerId) {
  map.addSource(ROUTE_SOURCE, { type: "geojson", data: EMPTY });

  const layer = (definition) => map.addLayer({ source: ROUTE_SOURCE, ...definition }, beforeLayerId);

  layer({
    id: "route-passed",
    type: "line",
    filter: ["==", ["get", "part"], "passed"],
    layout: { "line-cap": "round", "line-join": "round" },
    paint: { "line-color": "#8c959f", "line-width": 3, "line-dasharray": [1.5, 1.5] },
  });
  // white outline under the blue line keeps it readable on any map background
  layer({
    id: "route-ahead-casing",
    type: "line",
    filter: ["==", ["get", "part"], "ahead"],
    layout: { "line-cap": "round", "line-join": "round" },
    paint: { "line-color": "#ffffff", "line-width": 8 },
  });
  layer({
    id: "route-ahead",
    type: "line",
    filter: ["==", ["get", "part"], "ahead"],
    layout: { "line-cap": "round", "line-join": "round" },
    paint: { "line-color": "#1570ef", "line-width": 5 },
  });

  // Labels go on top of everything else (no beforeLayerId).
  map.addLayer({
    id: "route-stop-labels",
    type: "symbol",
    source: ROUTE_SOURCE,
    filter: ["==", ["get", "part"], "stop"],
    minzoom: 8,
    layout: {
      "text-field": ["get", "label"],
      "text-size": 12,
      "text-offset": [0, -1.4],
      "text-anchor": "bottom",
      "text-font": ["Open Sans Semibold", "Arial Unicode MS Bold"],
    },
    paint: { "text-color": "#0b4fb3", "text-halo-color": "#ffffff", "text-halo-width": 2 },
  });
}

const point = (stop) => [stop.longitude, stop.latitude];

/**
 * @param stops  stops of the trip from /map/train-route
 * @param train  the train's current position from /map/trains
 */
export function showRoute(map, stops, train) {
  const position = [Number(train.longitude), Number(train.latitude)];
  const passedStops = stops.filter((stop) => stop.stop_sequence <= train.last_stop_sequence);
  const aheadStops = stops.filter((stop) => stop.stop_sequence > train.last_stop_sequence);

  const line = (part, coordinates) =>
    coordinates.length < 2
      ? null
      : { type: "Feature", properties: { part }, geometry: { type: "LineString", coordinates } };

  const features = [
    line("passed", [...passedStops.map(point), position]),
    line("ahead", [position, ...aheadStops.map(point)]),
    ...aheadStops.map((stop) => ({
      type: "Feature",
      properties: { part: "stop", label: `${stop.stop_name} ${formatTime(stop.arrival_time)}` },
      geometry: { type: "Point", coordinates: point(stop) },
    })),
  ].filter(Boolean);

  map.getSource(ROUTE_SOURCE)?.setData({ type: "FeatureCollection", features });
}

export function clearRoute(map) {
  map.getSource(ROUTE_SOURCE)?.setData(EMPTY);
}

// Zooms so the whole route is visible.
export function fitRoute(map, stops, mapboxgl) {
  if (stops.length < 2) return;

  const bounds = new mapboxgl.LngLatBounds();
  stops.forEach((stop) => bounds.extend(point(stop)));
  map.fitBounds(bounds, { padding: 70, maxZoom: 11, duration: 800 });
}
