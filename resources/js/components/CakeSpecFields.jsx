/* The details of a cake on an order: type of cake, flavor (or icing flavor), size,
 * design, message and a reference photo. Used by the New order form for each cake
 * item and by the "Edit item" pop-up on the order page. The lists of choices come
 * from config/sweetnest.php through the `options` prop. */
import { useRef, useState } from 'react';
import Field, { inputClass } from '../lib/Field';

export const SPEC_KEYS = ['cake_type', 'flavor', 'size', 'tiers', 'design_theme', 'inscription'];

export function emptySpecs() {
  return { cake_type: '', flavor: '', size: '', tiers: '', design_theme: '', inscription: '' };
}

/** Chiffon cakes are flavored themselves; sponge and moist cakes get their flavor from the icing. */
export function flavorsFor(options, cakeType) {
  return cakeType === 'chiffon' ? options.flavors : options.icingFlavors;
}

export default function CakeSpecFields({ idp, nameFor, values, onChange, err, options, photoMaxKb, photoMaxLabel, hasImage = false, imageUrl = null, removeImage = false, onRemoveImage = null }) {
  const fileRef = useRef(null);
  const [fileName, setFileName] = useState('');
  const id = (field) => `${idp}${field}`;
  const chiffon = values.cake_type === 'chiffon';
  const flavors = flavorsFor(options, values.cake_type);

  function chooseType(type) {
    // The flavor list changes with the type, so a flavor from the other list is cleared.
    const keep = values.flavor && flavorsFor(options, type).includes(values.flavor);
    onChange({ cake_type: type, flavor: keep ? values.flavor : '' });
  }

  // The page-wide size check (ui.js) runs after this handler and empties a file that is too big,
  // so the name is read a moment later to match what is really in the box.
  function chooseFile(input) {
    setTimeout(() => setFileName(input.files?.[0]?.name || ''), 0);
  }

  function clearFile() {
    if (fileRef.current) fileRef.current.value = '';
    setFileName('');
  }

  return (
    <div className="grid gap-x-4 md:grid-cols-2">
      <Field id={id('cake_type')} label="Type of cake" optional error={err('cake_type')}>
        <select id={id('cake_type')} name={nameFor('cake_type')} className={inputClass('select', err('cake_type'))} value={values.cake_type || ''} onChange={(e) => chooseType(e.target.value)} data-react-field>
          <option value="">Choose...</option>
          {Object.entries(options.types).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
        </select>
      </Field>
      <Field id={id('flavor')} label={chiffon ? 'Flavor' : 'Icing flavor'} optional error={err('flavor')}
        hint={!values.cake_type ? 'Choose the type of cake first.' : (chiffon ? null : 'Sponge and moist cakes are flavored by the icing.')}>
        <select id={id('flavor')} name={nameFor('flavor')} className={inputClass('select', err('flavor'))} value={values.flavor || ''} onChange={(e) => onChange({ flavor: e.target.value })} disabled={!values.cake_type} data-react-field>
          <option value="">Choose...</option>
          {flavors.map((f) => <option key={f} value={f}>{f}</option>)}
        </select>
      </Field>
      <Field id={id('size')} label="Size" optional error={err('size')}>
        <select id={id('size')} name={nameFor('size')} className={inputClass('select', err('size'))} value={values.size || ''} onChange={(e) => onChange({ size: e.target.value })} data-react-field>
          <option value="">Choose...</option>
          {options.sizes.map((s) => <option key={s} value={s}>{s}</option>)}
        </select>
      </Field>
      <Field id={id('tiers')} label="Tiers" optional error={err('tiers')}>
        <select id={id('tiers')} name={nameFor('tiers')} className={inputClass('select', err('tiers'))} value={values.tiers || ''} onChange={(e) => onChange({ tiers: e.target.value })} data-react-field>
          <option value="">Choose...</option>
          {(options.tiers || []).map((n) => <option key={n} value={n}>{n === 1 ? '1 tier' : `${n} tiers`}</option>)}
        </select>
      </Field>
      <Field id={id('design_theme')} label="Design or theme" optional error={err('design_theme')} className="md:col-span-2">
        <input id={id('design_theme')} name={nameFor('design_theme')} value={values.design_theme || ''} maxLength={255} className={inputClass('input', err('design_theme'))} placeholder="e.g. Pink and gold, floral" onChange={(e) => onChange({ design_theme: e.target.value })} data-react-field />
      </Field>
      <Field id={id('inscription')} label="Message on the cake" optional error={err('inscription')} className="md:col-span-2">
        <input id={id('inscription')} name={nameFor('inscription')} value={values.inscription || ''} maxLength={255} className={inputClass('input', err('inscription'))} placeholder="e.g. Happy 7th Birthday, Mia!" onChange={(e) => onChange({ inscription: e.target.value })} data-react-field />
      </Field>
      <Field id={id('reference_image')} label={hasImage ? 'New reference photo' : 'Reference photo'} optional error={err('reference_image')}
        hint={`JPG, PNG or WEBP up to ${photoMaxLabel}.${hasImage ? ' Leave empty to keep the current photo.' : ''}`} className="md:col-span-2">
        <div className="flex items-start gap-2">
          <div className="min-w-0 flex-1">
            <input ref={fileRef} id={id('reference_image')} type="file" name={nameFor('reference_image')} accept="image/jpeg,image/png,image/webp"
              className={inputClass('input', err('reference_image'))} data-max-kb={photoMaxKb} onChange={(e) => chooseFile(e.target)} data-react-field />
          </div>
          {fileName && (
            <button type="button" className="btn btn-sm mt-[5px] whitespace-nowrap" onClick={clearFile} title="Remove the chosen photo">
              <svg className="icon size-3.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg> Remove
            </button>
          )}
        </div>
      </Field>
      {hasImage && (
        <div className="mb-[15px] flex items-center gap-3 md:col-span-2">
          {imageUrl && <a href={imageUrl} target="_blank" rel="noopener" title="Open the current photo"><img src={imageUrl} alt="Current reference photo" className="block size-[46px] rounded-[8px] border border-line object-cover" /></a>}
          <label className="checkbox"><input type="checkbox" name="remove_image" value="1" checked={removeImage} onChange={(e) => onRemoveImage && onRemoveImage(e.target.checked)} data-react-field /> Remove the current photo</label>
        </div>
      )}
    </div>
  );
}
