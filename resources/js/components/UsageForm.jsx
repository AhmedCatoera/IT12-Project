/* "Log ingredients used" on a job order (the stock out). Lives inside the pop-up's form. */
import { useState } from 'react';
import Field, { inputClass } from '../lib/Field';
import IngredientLines, { rowsFrom } from './IngredientLines';

export default function UsageForm({ ingredients, old = null, errors = {}, today }) {
  const [rows, setRows] = useState(() => rowsFrom(old?.lines));

  return (
    <>
      <p className="mb-3.5 text-ink-2">These amounts are deducted from stock. You can't use more than what is in stock.</p>
      {errors.lines && <div className="alert alert-error"><div className="grow">{errors.lines}</div></div>}
      <IngredientLines kind="usage" ingredients={ingredients} rows={rows} onChange={setRows} errors={errors} idp="use_" addSmall />
      <hr className="my-4 border-0 border-t border-line" />
      <div className="grid gap-x-4 md:grid-cols-2">
        <Field id="use_date" label="Date used" error={errors.out_date}>
          <input id="use_date" type="date" name="out_date" defaultValue={old?.out_date ?? today} max={today} className={inputClass('input', errors.out_date)} required />
        </Field>
        <Field id="use_remarks" label="Remarks" optional error={errors.remarks}>
          <input id="use_remarks" name="remarks" defaultValue={old?.remarks ?? ''} maxLength={500} className="input" placeholder="e.g. Extra batch of icing" />
        </Field>
      </div>
    </>
  );
}
