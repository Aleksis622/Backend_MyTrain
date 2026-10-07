import { el, trainIconSvg } from "./dom";

/**
 * Train icon: a coloured circle with a train symbol.
 * Moving = orange with an arrow pointing where it is heading; at a station = green with a pulsing ring.
 * (The root element is positioned by Mapbox with a CSS transform, so only inner parts are rotated/scaled.)
 */
export function createTrainMarkerElement(train) {
  const root = el(
    "div",
    "train-marker",
    el("div", "train-marker-heading"),
    el("div", "train-marker-body", trainIconSvg(18)),
  );
  updateTrainMarkerElement(root, train);
  return root;
}

export function updateTrainMarkerElement(root, train, selected = false) {
  const moving = train.status === "moving";
  root.classList.toggle("is-moving", moving);
  root.classList.toggle("is-at-station", !moving);
  root.classList.toggle("is-selected", selected);
  root.classList.toggle("is-delayed", train.service_status === "delayed");

  const heading = root.querySelector(".train-marker-heading");
  heading.hidden = train.heading === null || train.heading === undefined;
  heading.style.transform = `rotate(${train.heading ?? 0}deg)`;

  root.title = `${train.origin} → ${train.destination}`;
}
