/* New stock in: one receipt with every ingredient on it, and the running total cost. */
import { useState } from 'react';
import Field, { inputClass } from '../lib/Field';
import { money, peso } from '../lib/format';
import IngredientLines, { rowsFrom } from './IngredientLines';

export default function StockInForm({ ingredients, old = null, errors = {}, preselect = '', today }) {
  const [rows, setRows] = useState(() => rowsFrom(old?.lines, preselect));
  const total = money(rows.reduce((sum, r) => sum + (parseFloat(r.quantity) || 0) * (parseFloat(r.unit_cost) || 0), 0));

  return (
    <>
      <div className="card">
        <div className="card-head"><h2 className="card-title">Receipt</h2></div>
        <div className="card-body">
          <div className="grid gap-x-4 md:grid-cols-2">
            <Field id="receipt_no" label="Receipt or invoice number" optional error={errors.receipt_no}>
              <input id="receipt_no" name="receipt_no" defaultValue={old?.receipt_no ?? ''} maxLength={50} className={inputClass('input', errors.receipt_no)} placeholder="e.g. OR-10452" />
            </Field>
            <Field id="supplier_name" label="Supplier" optional error={errors.supplier_name}>
              <input id="supplier_name" name="supplier_name" defaultValue={old?.supplier_name ?? ''} maxLength={100} className={inputClass('input', errors.supplier_name)} placeholder="e.g. Davao Baking Supplies" />
            </Field>
            <Field id="received_date" label="Date received" error={errors.received_date}>
              <input id="received_date" type="date" name="received_date" defaultValue={old?.received_date ?? today} max={today} className={inputClass('input', errors.received_date)} required />
            </Field>
            <Field id="remarks" label="Remarks" optional error={errors.remarks}>
              <input id="remarks" name="remarks" defaultValue={old?.remarks ?? ''} maxLength={500} className={inputClass('input', errors.remarks)} />
            </Field>
          </div>
        </div>
      </div>

      <div className="card">
        <div className="card-head"><h2 className="card-title">Ingredients received</h2><span className="card-sub">Each ingredient once per receipt</span></div>
        <div className="card-body">
          {errors.lines && <div className="alert alert-error"><div className="grow">{errors.lines}</div></div>}
          <IngredientLines kind="stock_in" ingredients={ingredients} rows={rows} onChange={setRows} errors={errors} idp="si_">
            <div className="money-row gap-4"><span className="text-ink-2">Total cost</span><strong data-total>{peso(total)}</strong></div>
          </IngredientLines>
        </div>
      </div>
    </>
  );
}
