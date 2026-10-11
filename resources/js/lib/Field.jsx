/* One labelled form field, the same shape as the Blade <x-field> component:
 * label, the input, then the error from the last save or a hint. */
export default function Field({ id, label, optional = false, error = null, hint = null, className = '', children }) {
  return (
    <div className={`field ${className}`.trim()}>
      {label && (
        <label className="label" htmlFor={id}>
          {label} {optional && <span className="opt">(optional)</span>}
        </label>
      )}
      {children}
      {error ? <div className="error-text">{error}</div> : hint ? <div className="hint">{hint}</div> : null}
    </div>
  );
}

/** "input is-invalid" when the field has an error. */
export function inputClass(base, error) {
  return error ? `${base} is-invalid` : base;
}
