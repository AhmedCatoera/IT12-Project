/* Leaflet helpers shared by the address picker and the pin maps.
 * Maps open on Davao City; a pin can go anywhere in the Philippines. */
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

export const PARTS = ['street', 'address_line2', 'barangay', 'city', 'province', 'postal_code'];

export function pinIcon(color = '#be123c') {
  return L.divIcon({
    className: 'map-pin',
    html: `<svg viewBox="0 0 24 32" width="28" height="37" aria-hidden="true">
      <path d="M12 0C5.4 0 0 5.3 0 11.9 0 20.8 12 32 12 32s12-11.2 12-20.1C24 5.3 18.6 0 12 0z" fill="${color}"/>
      <circle cx="12" cy="12" r="4.6" fill="#fff"/></svg>`,
    iconSize: [28, 37],
    iconAnchor: [14, 36],
    popupAnchor: [0, -32],
  });
}

export function bounds(box) {
  return L.latLngBounds([box.south, box.west], [box.north, box.east]);
}

/** A map locked to the Philippines, with OpenStreetMap tiles. */
export function createMap(el, config, options = {}) {
  const map = L.map(el, {
    maxBounds: bounds(config.bounds).pad(0.3),
    maxBoundsViscosity: 0.6,
    minZoom: 5,
    ...options,
  });
  L.tileLayer(config.tiles, { maxZoom: 19, attribution: config.attribution }).addTo(map);
  return map;
}

/** Leaflet needs a visible box to draw in. Maps inside a closed pop-up or a folded
 * section are created the first time they show, and resized whenever they change size. */
export function whenVisible(el, create) {
  let map = null;
  const observer = new ResizeObserver(() => {
    if (el.offsetWidth === 0 || el.offsetHeight === 0) return;
    if (!map) map = create(); else map.invalidateSize();
  });
  observer.observe(el);
  return () => {
    observer.disconnect();
    if (map) map.remove();
  };
}

export { L };

/** GET a JSON URL through the app (the server talks to OpenStreetMap, not the browser). */
export function getJson(url) {
  return fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
    .then((response) => response.json().catch(() => ({})).then((body) => {
      if (!response.ok) {
        const first = body.errors ? Object.values(body.errors).map((e) => e[0])[0] : null;
        throw new Error(first || body.message || 'Lookup failed');
      }
      return body;
    }));
}
