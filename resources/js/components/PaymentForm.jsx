/* "Record payment" pop-up: the amount (with Down payment / Full balance shortcuts), the
 * method, when it was received, notes. Cash needs no proof. GCash needs the reference
 * number or a screenshot of the payment (one of the two is enough). */
import { useRef, useState } from 'react';
import Field, { inputClass } from '../lib/Field';
import { peso } from '../lib/format';

export default function PaymentForm({ balance, dpRemaining, isPending, isRush = false, methods, old = null, errors = {}, now, defaultAmount, photoMaxKb, photoMaxLabel }) {
  const [amount, setAmount] = useState(old ? (old.amount ?? '') : Number(defaultAmount).toFixed(2));
  const [method, setMethod] = useState(old?.payment_method ?? 'cash');
  const [fileName, setFileName] = useState('');
  const fileRef = useRef(null);
  const showDpChip = isPending && dpRemaining > 0 && dpRemaining < balance;

  // ui.js empties a file that is too big right after this handler, so read the name a moment later.
  function chooseFile(input) {
    setTimeout(() => setFileName(input.files?.[0]?.name || ''), 0);
  }

  function clearFile() {
    if (fileRef.current) fileRef.current.value = '';
    setFileName('');
  }

  return (
    <>
      <div className="money mb-4">
        <div className="money-row"><span className="text-ink-2">Balance</span><strong>{peso(balance)}</strong></div>
        {isPending && <div className="money-row"><span className="text-ink-2">{isRush ? 'Still needed to confirm (rush: full payment)' : 'Still needed to confirm (50%)'}</span><span>{peso(dpRemaining)}</span></div>}
      </div>
      <Field id="pay_amount" label="Amount received (₱)" error={errors.amount}>
        <input id="pay_amount" type="number" name="amount" step={0.01} min={0.01} max={balance} value={amount} onChange={(e) => setAmount(e.target.value)} className={inputClass('input', errors.amount)} required />
        <div className="mt-2 flex flex-wrap gap-1.5">
          {showDpChip && <button type="button" className="chip" onClick={() => setAmount(Number(dpRemaining).toFixed(2))}>Down payment {peso(dpRemaining)}</button>}
          <button type="button" className="chip" onClick={() => setAmount(Number(balance).toFixed(2))}>Full balance {peso(balance)}</button>
        </div>
      </Field>
      <div className="grid gap-x-4 md:grid-cols-2">
        <Field id="pay_method" label="Method" error={errors.payment_method}>
          <select id="pay_method" name="payment_method" className={inputClass('select', errors.payment_method)} value={method} onChange={(e) => setMethod(e.target.value)} required>
            {Object.entries(methods).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
          </select>
        </Field>
        <Field id="pay_at" label="Date and time received" error={errors.paid_at}>
          <input id="pay_at" type="datetime-local" name="paid_at" defaultValue={old?.paid_at ?? now} max={now} className={inputClass('input', errors.paid_at)} required />
        </Field>
      </div>
      {method === 'gcash' && (
        <div className="mb-[15px] rounded-sm border border-line bg-surface-2 p-3.5" data-gcash-proof>
          <p className="mb-2.5 text-sm text-ink-2">Proof of payment: enter the GCash reference number or attach a screenshot. One of the two is enough.</p>
          <Field id="pay_ref" label="GCash reference number" error={errors.reference_no}>
            <input id="pay_ref" name="reference_no" maxLength={100} defaultValue={old?.reference_no ?? ''} className={inputClass('input', errors.reference_no)} placeholder="e.g. 1234567890123" />
          </Field>
          <Field id="pay_proof" label="GCash screenshot" error={errors.proof_image} hint={`JPG, PNG or WEBP up to ${photoMaxLabel}.`} className="mb-0">
            <div className="flex items-start gap-2">
              <div className="min-w-0 flex-1">
                <input ref={fileRef} id="pay_proof" type="file" name="proof_image" accept="image/jpeg,image/png,image/webp" className={inputClass('input', errors.proof_image)} data-max-kb={photoMaxKb} onChange={(e) => chooseFile(e.target)} />
              </div>
              {fileName && (
                <button type="button" className="btn btn-sm mt-[5px] whitespace-nowrap" onClick={clearFile} title="Remove the chosen screenshot">
                  <svg className="icon size-3.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg> Remove
                </button>
              )}
            </div>
          </Field>
        </div>
      )}
      <Field id="pay_notes" label="Notes" optional error={errors.notes}>
        <input id="pay_notes" name="notes" maxLength={500} defaultValue={old?.notes ?? ''} className="input" />
      </Field>
    </>
  );
}
