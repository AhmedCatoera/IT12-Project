/* The address picker as a stand-alone part of a Blade form (customer pop-ups, the
 * edit-order pop-up). It keeps its own state and can be filled from outside:
 * the page's "Edit" buttons fire a react:fill event with the row's values. */
import { useEffect, useState } from 'react';
import AddressPicker, { emptyAddress, isBlankAddress } from './AddressPicker';

const KEYS = Object.keys(emptyAddress());

/** Picks this address's fields out of a flat set of form values (with the prefix). */
function fromValues(values, prefix, defaults) {
  const next = emptyAddress();
  KEYS.forEach((key) => {
    const v = values ? values[prefix + key] : undefined;
    next[key] = v == null ? '' : String(v);
  });
  // A blank address starts on the shop's city and province.
  if (isBlankAddress(next)) {
    if (!next.city) next.city = defaults.city || '';
    if (!next.province) next.province = defaults.province || '';
  }
  return next;
}

function asFields(fields) {
  const next = emptyAddress();
  if (fields) KEYS.forEach((key) => { next[key] = fields[key] == null ? '' : String(fields[key]); });
  return next;
}

function sameAddress(a, b) {
  return ['street', 'address_line2', 'barangay', 'postal_code', 'latitude'].every((k) => String(a[k] ?? '') === String(b[k] ?? ''));
}

/**
 * `defaultsByType` + `typeField`: the place an order goes to starts as the shop (pickup) or the
 * customer's saved address (delivery). When the Pickup/Delivery radio in the same form changes,
 * the fields switch to the other default, unless a spot was typed by hand (then it is kept).
 */
export default function AddressFields({ prefix = '', idp = '', values = {}, errors = {}, map, defaults = {}, saved = null, defaultsByType = null, typeField = null, root }) {
  const [value, setValue] = useState(() => fromValues(values, prefix, defaults));

  useEffect(() => {
    const onFill = (e) => setValue(fromValues(e.detail || {}, prefix, defaults));
    root.addEventListener('react:fill', onFill);
    return () => root.removeEventListener('react:fill', onFill);
  }, [root, prefix]);

  useEffect(() => {
    if (!defaultsByType || !typeField) return undefined;
    const form = root.closest('form');
    if (!form) return undefined;
    const onType = (e) => {
      if (!e.target.matches || !e.target.matches(`input[type=radio][name="${typeField}"]`) || !e.target.checked) return;
      const next = defaultsByType[e.target.value] ? asFields(defaultsByType[e.target.value]) : null;
      setValue((current) => {
        const untouched = isBlankAddress(current) || Object.values(defaultsByType).some((d) => d && sameAddress(current, asFields(d)));
        if (!untouched) return current;
        return next || { ...emptyAddress(), city: defaults.city || '', province: defaults.province || '' };
      });
    };
    form.addEventListener('change', onType);
    return () => form.removeEventListener('change', onType);
  }, [root, typeField, defaultsByType]);

  return <AddressPicker prefix={prefix} idp={idp} value={value} onChange={setValue} errors={errors} map={map} saved={saved} />;
}
