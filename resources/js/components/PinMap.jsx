/* A read-only map with one pin (customer and order pages, behind "Show on map"). */
import { useEffect, useRef } from 'react';
import { L, createMap, pinIcon, whenVisible } from '../lib/map';

export default function PinMap({ lat, lng, map: config, label = 'Map pin' }) {
  const box = useRef(null);

  useEffect(() => whenVisible(box.current, () => {
    const latlng = L.latLng(lat, lng);
    const map = createMap(box.current, config, { scrollWheelZoom: false });
    map.setView(latlng, 16);
    L.marker(latlng, { icon: pinIcon(), keyboard: false }).addTo(map);
    return map;
  }), [lat, lng]);

  return <div ref={box} className="map-box mt-2 h-[190px]" role="img" aria-label={label} />;
}
