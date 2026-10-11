/* Address with a map. Click the map or drag the pin, or search a place,
 * and the address fields fill in by themselves from OpenStreetMap (through the app's
 * /geo/reverse and /geo/search routes). The fields are ordinary inputs, so the form
 * that holds this component posts them like any other field.
 *
 * Controlled: the parent owns `value` ({street, address_line2, barangay, city, province,
 * postal_code, latitude, longitude}) and gets the whole object back in onChange. */
import { useEffect, useRef, useState } from 'react';
import { L, PARTS, bounds, createMap, getJson, pinIcon, whenVisible } from '../lib/map';
import Field, { inputClass } from '../lib/Field';

const CITIES = ['Davao City', 'Digos City', 'Panabo City', 'Tagum City', 'Island Garden City of Samal', 'Santa Cruz', 'Carmen'];
const PROVINCES = ['Davao del Sur', 'Davao del Norte', 'Davao de Oro', 'Davao Oriental', 'Davao Occidental'];
const DEFAULT_STATUS = 'Click the map or drag the pin to the exact spot. The address fills in by itself.';

export function emptyAddress() {
  return { street: '', address_line2: '', barangay: '', city: '', province: '', postal_code: '', latitude: '', longitude: '' };
}

/** Everything but the city and province, which have defaults. */
export function isBlankAddress(value) {
  return ['street', 'address_line2', 'barangay', 'postal_code', 'latitude'].every((part) => !value[part]);
}

export default function AddressPicker({ prefix = '', idp = '', value, onChange, errors = {}, map: config, saved = null, children }) {
  const box = useRef(null);
  const mapRef = useRef(null);
  const markerRef = useRef(null);
  const valueRef = useRef(value);
  valueRef.current = value;
  const lookupId = useRef(0);
  const searchTimer = useRef(null);

  const [status, setStatus] = useState(DEFAULT_STATUS);
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [flash, setFlash] = useState({});
  const [ready, setReady] = useState(false); // the map exists once its box is visible

  const area = bounds(config.bounds);
  const home = bounds(config.home);
  const err = (part) => errors[prefix + part] || null;
  const pin = value.latitude !== '' && value.longitude !== '' && value.latitude != null
    ? L.latLng(parseFloat(value.latitude), parseFloat(value.longitude))
    : null;

  function update(changes) {
    onChange({ ...valueRef.current, ...changes });
  }

  // The map itself, created once the box is visible (it may sit inside a closed pop-up).
  useEffect(() => whenVisible(box.current, () => {
    const map = createMap(box.current, config);
    mapRef.current = map;
    const current = valueRef.current;
    const start = current.latitude ? L.latLng(parseFloat(current.latitude), parseFloat(current.longitude)) : null;
    if (start) map.setView(start, 16); else map.fitBounds(home);
    map.on('click', (e) => placePin(e.latlng, true));
    setReady(true);
    return map;
  }), []);

  // Keep the marker in step with the value (typed in, picked, or filled from outside).
  useEffect(() => {
    const map = mapRef.current;
    if (!map) return;
    if (!pin) {
      if (markerRef.current) { map.removeLayer(markerRef.current); markerRef.current = null; }
      return;
    }
    if (!markerRef.current) {
      markerRef.current = L.marker(pin, { draggable: true, icon: pinIcon(), keyboard: true, title: 'Drag to adjust' }).addTo(map);
      markerRef.current.on('dragend', () => placePin(markerRef.current.getLatLng(), true));
      map.setView(pin, Math.max(map.getZoom(), 16));
    } else if (!markerRef.current.getLatLng().equals(pin)) {
      markerRef.current.setLatLng(pin);
      map.setView(pin, Math.max(map.getZoom(), 16));
    }
  }, [value.latitude, value.longitude, ready]);

  function fillFromMap(fields) {
    const changes = {};
    PARTS.forEach((part) => {
      // Keep what is already typed when the map doesn't know that part.
      if (fields[part] !== null && fields[part] !== undefined && fields[part] !== '') changes[part] = fields[part];
    });
    update(changes);
    const lit = {};
    Object.keys(changes).forEach((part) => { lit[part] = Date.now(); });
    setFlash(lit);
    setTimeout(() => setFlash({}), 1600);
  }

  function placePin(latlng, lookup) {
    if (!area.contains(latlng)) {
      setStatus('That spot is outside the Philippines. Pick a place inside the map.');
      if (markerRef.current && pin) markerRef.current.setLatLng(pin);
      return;
    }
    update({ latitude: latlng.lat.toFixed(7), longitude: latlng.lng.toFixed(7) });
    if (!lookup) return;

    const id = ++lookupId.current;
    setStatus('Looking up the address...');
    getJson(`${config.reverseUrl}?lat=${latlng.lat.toFixed(7)}&lng=${latlng.lng.toFixed(7)}`)
      .then((data) => {
        if (id !== lookupId.current) return;
        const fields = data.fields || {};
        fillFromMap(fields);
        setStatus(fields.street
          ? 'Filled in from the map. Please check the house number and street.'
          : 'Filled in what the map knows. Please type the house number and street.');
      })
      .catch((error) => {
        if (id !== lookupId.current) return;
        setStatus(error.message && error.message !== 'Failed to fetch'
          ? error.message
          : "Couldn't look up this spot (no internet?). The pin is kept, so just type the address.");
      });
  }

  function runSearch(text) {
    const q = text.trim();
    if (q.length < 3) { setResults([]); return; }
    setStatus('Searching...');
    getJson(`${config.searchUrl}?q=${encodeURIComponent(q)}`)
      .then((data) => {
        const found = data.results || [];
        setResults(found);
        setStatus(found.length ? DEFAULT_STATUS : 'No places found. Try another name, or click the map.');
      })
      .catch((error) => { setResults([]); setStatus(error.message || "Search isn't available right now."); });
  }

  function pickResult(result) {
    setResults([]);
    setQuery('');
    const latlng = L.latLng(result.lat, result.lng);
    if (mapRef.current) mapRef.current.setView(latlng, 17);
    placePin(latlng, true);
  }

  function clearPin() {
    update({ latitude: '', longitude: '' });
    if (mapRef.current) mapRef.current.fitBounds(home);
    setStatus('Pin removed. The typed address is kept.');
  }

  // `saved` = one or more ready-made addresses to copy in: [{ label, fields }], or the older
  // { firstName, fields } shape for a customer's saved address.
  const savedList = (Array.isArray(saved) ? saved : saved ? [saved] : [])
    .filter((s) => s && s.fields)
    .map((s) => ({ label: s.label || `Use ${s.firstName}'s saved address`, fields: s.fields }));

  function useSaved(fields) {
    onChange({ ...emptyAddress(), ...fields });
    setStatus(DEFAULT_STATUS);
  }

  const field = (part, label, props = {}, extra = {}) => (
    <Field id={idp + part} label={label} error={err(part)} optional={extra.optional} className={extra.className || ''}>
      <input
        id={idp + part}
        name={prefix + part}
        value={value[part] ?? ''}
        onChange={(e) => update({ [part]: e.target.value })}
        className={`${inputClass('input', err(part))} ${flash[part] ? 'just-filled' : ''}`}
        data-react-field
        {...props}
      />
      {extra.list}
    </Field>
  );

  const pinError = err('latitude') || err('longitude');

  return (
    <div className="rounded-lg border border-line bg-surface-2 p-3.5" data-address-block>
      <div className="mb-2 flex items-center gap-2">
        <div className="relative min-w-0 flex-1">
          <svg className="icon pointer-events-none absolute top-1/2 left-[11px] size-4 -translate-y-1/2 text-ink-3" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5" /><path d="M20 20l-4-4" /></svg>
          <input
            type="search"
            className="input w-full pl-[34px]"
            placeholder="Search a place"
            title="For example: SM Lanang, Toril public market"
            aria-label="Search a place"
            autoComplete="off"
            value={query}
            onChange={(e) => {
              setQuery(e.target.value);
              clearTimeout(searchTimer.current);
              searchTimer.current = setTimeout(() => runSearch(e.target.value), 600);
            }}
            onKeyDown={(e) => {
              if (e.key === 'Enter') { e.preventDefault(); clearTimeout(searchTimer.current); runSearch(query); }
              if (e.key === 'Escape') setResults([]);
            }}
            data-map-search
          />
          {results.length > 0 && (
            <ul className="absolute top-[calc(100%+4px)] right-0 left-0 z-[1100] m-0 max-h-60 list-none overflow-y-auto rounded-sm border border-line bg-surface p-1 shadow-pop" data-map-results>
              {results.map((result, i) => (
                <li key={i}>
                  <button type="button" className="block w-full rounded-[6px] px-2.5 py-2 text-left text-sm text-ink hover:bg-surface-2 focus:bg-surface-2 focus:outline-none" onClick={() => pickResult(result)}>
                    {result.label}
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>

      <div ref={box} className="map-box h-[250px] cursor-crosshair max-md:h-[210px] [&_.leaflet-marker-icon]:cursor-grab" aria-label="Map. Click to pin the exact spot." data-address-map />

      <div className="mt-[7px] mb-3 flex items-start justify-between gap-2.5 text-xs text-ink-2">
        <span data-map-status>{status}</span>
        {pin && <button type="button" className="link-btn" onClick={clearPin} data-map-clear>Remove pin</button>}
      </div>
      {pinError && <div className="error-text mb-2">{pinError}</div>}

      <input type="hidden" name={`${prefix}latitude`} value={value.latitude ?? ''} data-lat data-react-field readOnly />
      <input type="hidden" name={`${prefix}longitude`} value={value.longitude ?? ''} data-lng data-react-field readOnly />

      {savedList.length > 0 && (
        <div className="mb-3.5 flex flex-wrap gap-2">
          {savedList.map((s) => (
            <button key={s.label} type="button" className="btn btn-sm" onClick={() => useSaved(s.fields)} data-use-saved>
              <svg className="icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" /></svg> {s.label}
            </button>
          ))}
        </div>
      )}
      {children}

      <div className="mt-1 grid gap-x-4 md:grid-cols-2">
        {field('street', 'House no. and street', { maxLength: 150, placeholder: 'e.g. Blk 7 Lot 3, Narra St.', autoComplete: 'address-line1' }, { className: 'md:col-span-2' })}
        {field('address_line2', 'Subdivision, building or landmark', { maxLength: 150, placeholder: 'e.g. Phase 1, near the chapel', autoComplete: 'address-line2' }, { className: 'md:col-span-2', optional: true })}
        {field('barangay', 'Barangay', { maxLength: 100, placeholder: 'e.g. Santo Niño' })}
        {field('city', 'City or municipality', { maxLength: 100, list: `${idp}cities`, autoComplete: 'address-level2' }, {
          list: <datalist id={`${idp}cities`}>{CITIES.map((c) => <option key={c} value={c} />)}</datalist>,
        })}
        {field('province', 'Province', { maxLength: 100, list: `${idp}provinces`, autoComplete: 'address-level1' }, {
          list: <datalist id={`${idp}provinces`}>{PROVINCES.map((p) => <option key={p} value={p} />)}</datalist>,
        })}
        {field('postal_code', 'ZIP code', { maxLength: 4, inputMode: 'numeric', pattern: '\\d{4}', placeholder: '8000', autoComplete: 'postal-code' }, { optional: true })}
      </div>
    </div>
  );
}
