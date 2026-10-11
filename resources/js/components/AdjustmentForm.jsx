/* New stock adjustment: the reason, then the ingredients. Spoilage, expired and damaged
 * can only remove stock, so those reasons lock every line to "Remove from stock". */
import { useState } from 'react';
import Field, { inputClass } from '../lib/Field';
import IngredientLines, { rowsFrom } from './IngredientLines';

const LOSS_REASONS = ['spoilage', 'expired', 'damaged'];

export default function AdjustmentForm({ ingredients, reasons, old = null, errors = {}, preselect = '', today }) {
  const [reason, setReason] = useState(old?.reason ?? '');
  const [rows, setRows] = useState(() => rowsFrom(old?.lines, preselect));
  const lossOnly = LOSS_REASONS.includes(reason);

  return (
    <>
      <div className="card">
        <div className="card-head"><h2 className="card-title">Reason</h2></div>
        <div className="card-body">
          <div className="grid gap-x-4 md:grid-cols-2">
            <Field id="reason" label="Reason" error={errors.reason}>
              <select id="reason" name="reason" className={inputClass('select', errors.reason)} value={reason} onChange={(e) => setReason(e.target.value)} required>
                <option value="">Choose...</option>
                {Object.entries(reasons).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
              </select>
            </Field>
            <Field id="adjusted_date" label="Date" error={errors.adjusted_date}>
              <input id="adjusted_date" type="date" name="adjusted_date" defaultValue={old?.adjusted_date ?? today} max={today} className={inputClass('input', errors.adjusted_date)} required />
            </Field>
            <Field id="remarks" label="What happened" error={errors.remarks} className="md:col-span-2">
              <textarea id="remarks" name="remarks" rows={2} maxLength={500} defaultValue={old?.remarks ?? ''} className={inputClass('textarea', errors.remarks)} placeholder="e.g. 1 L of milk left out overnight and spoiled" required />
            </Field>
          </div>
        </div>
      </div>

      <div className="card">
        <div className="card-head">
          <h2 className="card-title">Ingredients</h2>
          {lossOnly && <span className="card-sub">Spoilage, expired and damaged can only remove stock.</span>}
        </div>
        <div className="card-body">
          {errors.lines && <div className="alert alert-error"><div className="grow">{errors.lines}</div></div>}
          <IngredientLines kind="adjustment" ingredients={ingredients} rows={rows} onChange={setRows} errors={errors} idp="adj_" lossOnly={lossOnly} />
        </div>
      </div>
    </>
  );
}
