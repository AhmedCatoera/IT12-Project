/* Repeatable ingredient lines, shared by Stock in (ingredient, quantity, cost, expiry),
 * Adjustments (ingredient, add or remove, quantity) and Log ingredients used
 * (ingredient, amount used). The rows post as lines[key][field]. */
import Field, { inputClass } from '../lib/Field';
import { qty } from '../lib/format';

const LAYOUT = {
  stock_in: 'md:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,.9fr)_minmax(0,1fr)_auto]',
  adjustment: 'md:grid-cols-[minmax(0,1.6fr)_minmax(0,.9fr)_minmax(0,1fr)_auto]',
  usage: 'md:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_auto]',
};

/** Turns old('lines') (an object keyed by row number) into rows with stable keys. */
export function rowsFrom(old, preselect = '') {
  const rows = old && Object.keys(old).length ? old : { 0: { ingredient_id: preselect || '' } };
  return Object.keys(rows).map((key) => ({ key: Number(key), ...rows[key] }));
}

export function nextKeyFor(rows) {
  return rows.length ? Math.max(...rows.map((r) => r.key)) + 1 : 1;
}

export default function IngredientLines({ kind, ingredients, rows, onChange, errors = {}, idp, lossOnly = false, addLabel = 'Add ingredient', addSmall = false, children }) {
  const unitOf = (id) => ingredients.find((i) => String(i.id) === String(id))?.unit || 'unit';

  function update(key, changes) {
    onChange(rows.map((row) => (row.key === key ? { ...row, ...changes } : row)));
  }

  function add() {
    onChange([...rows, { key: nextKeyFor(rows) }]);
  }

  function remove(key) {
    onChange(rows.length > 1 ? rows.filter((row) => row.key !== key) : [{ key: rows[0].key }]);
  }

  const optionLabel = (i) => {
    if (kind === 'adjustment') return `${i.name} (${qty(i.stock)} ${i.unit} on hand)`;
    if (kind === 'usage') return `${i.name} (${qty(i.stock)} ${i.unit} left)`;
    return i.name;
  };

  return (
    <>
      <div className="grid gap-2.5">
        {rows.map((row) => {
          const n = `lines[${row.key}]`;
          const e = (field) => errors[`lines.${row.key}.${field}`];
          const id = (field) => `${idp}${row.key}_${field}`;
          const direction = lossOnly ? 'remove' : (row.direction || 'remove');
          return (
            <div key={row.key} className={`line ${LAYOUT[kind]}`} data-line>
              <Field id={id('ing')} label="Ingredient" error={e('ingredient_id')}>
                <select id={id('ing')} name={`${n}[ingredient_id]`} className={inputClass('select', e('ingredient_id'))} value={row.ingredient_id ?? ''} onChange={(ev) => update(row.key, { ingredient_id: ev.target.value })} required>
                  <option value="">Choose...</option>
                  {ingredients.map((i) => (
                    <option key={i.id} value={i.id} disabled={kind === 'usage' && Number(i.stock) <= 0 && String(row.ingredient_id) !== String(i.id)}>
                      {optionLabel(i)}
                    </option>
                  ))}
                </select>
              </Field>
              {kind === 'adjustment' && (
                <Field id={id('dir')} label="Change" error={e('direction')}>
                  <select id={id('dir')} name={`${n}[direction]`} className={inputClass('select', e('direction'))} value={direction} onChange={(ev) => update(row.key, { direction: ev.target.value })}>
                    <option value="remove">Remove from stock</option>
                    <option value="add" disabled={lossOnly}>Add to stock</option>
                  </select>
                </Field>
              )}
              <Field id={id('qty')} label={kind === 'usage' ? 'Amount used' : 'Quantity'} error={e('quantity')}>
                <div className="qty-input">
                  <input id={id('qty')} type="number" name={`${n}[quantity]`} value={row.quantity ?? ''} min={0.01} step={0.01} onChange={(ev) => update(row.key, { quantity: ev.target.value })} className={inputClass('input', e('quantity'))} required />
                  <span className="unit">{unitOf(row.ingredient_id)}</span>
                </div>
              </Field>
              {kind === 'stock_in' && (
                <>
                  <Field id={id('cost')} label="Cost per unit" optional error={e('unit_cost')}>
                    <input id={id('cost')} type="number" name={`${n}[unit_cost]`} value={row.unit_cost ?? ''} min={0} step={0.01} placeholder="₱" onChange={(ev) => update(row.key, { unit_cost: ev.target.value })} className={inputClass('input', e('unit_cost'))} />
                  </Field>
                  <Field id={id('exp')} label="Expiry date" optional error={e('expiration_date')}>
                    <input id={id('exp')} type="date" name={`${n}[expiration_date]`} value={row.expiration_date ?? ''} onChange={(ev) => update(row.key, { expiration_date: ev.target.value })} className={inputClass('input', e('expiration_date'))} />
                  </Field>
                </>
              )}
              <button type="button" className="btn btn-ghost btn-sm self-end" onClick={() => remove(row.key)} aria-label="Remove line">Remove</button>
            </div>
          );
        })}
      </div>
      <div className="mt-3 flex flex-wrap items-center justify-between gap-2.5">
        <button type="button" className={`btn ${addSmall ? 'btn-sm' : ''}`} onClick={add}>
          <svg className="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg> {addLabel}
        </button>
        {children}
      </div>
    </>
  );
}
