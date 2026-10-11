/* Number formatting shared by the React forms. */

/** ₱1,234.50 */
export function peso(n) {
  return '₱' + (Number(n) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/** 2.5 or 1,000 (no trailing zeros), for quantities. */
export function qty(n) {
  const fixed = (Number(n) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  return fixed.replace(/\.?0+$/, '');
}

/** Rounds money to centavos. */
export function money(n) {
  return Math.round((Number(n) || 0) * 100) / 100;
}
