/* The "New order" form: customer and schedule (regular or rush comes from the due date),
 * the pickup spot or delivery address with a map, the delivery fee, the items (from the
 * price list or typed in as custom items) with their prices, and a live summary (items,
 * rush fee, delivery fee, total and the payment that confirms the order).
 * It renders inside the Blade <form>, so saving is a normal POST to OrderController@store
 * and a failed save comes back with `old` values and `errors` as props. */
import { useMemo, useState } from 'react';
import Field, { inputClass } from '../lib/Field';
import { money, peso } from '../lib/format';
import AddressPicker, { emptyAddress, isBlankAddress } from './AddressPicker';
import CakeSpecFields, { emptySpecs } from './CakeSpecFields';
import { rushFor } from '../ui';

const ADDRESS_KEYS = Object.keys(emptyAddress());

function addressFrom(values, prefix) {
  const next = emptyAddress();
  ADDRESS_KEYS.forEach((key) => { next[key] = values && values[prefix + key] != null ? String(values[prefix + key]) : ''; });
  return next;
}

function sameAddress(a, b) {
  return ['street', 'address_line2', 'barangay', 'postal_code', 'latitude'].every((k) => String(a[k] ?? '') === String(b[k] ?? ''));
}

/** A ready-made address (the shop's, a customer's) as form values: every part a string. */
function asFields(fields) {
  const next = emptyAddress();
  if (fields) ADDRESS_KEYS.forEach((key) => { next[key] = fields[key] == null ? '' : String(fields[key]); });
  return next;
}

function cityKey(city) {
  return String(city || '').trim().toLowerCase().replace(/\s+/g, ' ');
}

/** "Blk 7 Lot 3, Narra St., Phase 1, Brgy. Santo Niño, Davao City, Davao del Sur 8022" */
function formatAddress(a) {
  const province = [a.province, a.postal_code].filter(Boolean).join(' ');
  return [a.street, a.address_line2, a.barangay ? `Brgy. ${a.barangay}` : '', a.city, province].filter(Boolean).join(', ');
}

function trimPercent(n) {
  const v = Number(n);
  return Number.isFinite(v) ? String(Math.round(v * 100) / 100) : '';
}

export default function OrderForm({ customers, products, categories, old = null, errors = {}, selectedCustomerId = '', photoMaxKb, photoMaxLabel, minDate, map, defaults = {}, cake, rush }) {
  const [customerId, setCustomerId] = useState(String(selectedCustomerId ?? ''));
  const [finder, setFinder] = useState('');
  const [scheduledAt, setScheduledAt] = useState(old?.scheduled_at ?? '');
  const [orderType, setOrderType] = useState(old?.order_type ?? 'pickup');
  const [rushFee, setRushFee] = useState(old?.rush_fee_percent != null && old.rush_fee_percent !== '' ? String(old.rush_fee_percent) : String(rush.defaultPercent));
  const [deliveryFee, setDeliveryFee] = useState(old?.delivery_fee != null ? String(old.delivery_fee) : '');
  const [notes, setNotes] = useState(old?.notes ?? '');
  // The place the order goes to (pickup spot or delivery address), posted as delivery_* fields.
  const [delivery, setDelivery] = useState(() => (old ? addressFrom(old, 'delivery_') : asFields(rush.shopAddress)));
  // What we copied in (the shop or the customer's saved address), so a customer or type change can
  // replace it; a place typed by hand is never replaced. After a failed save the restored fields
  // count as copied in when they still equal one of those defaults.
  const [autoFilled, setAutoFilled] = useState(() => {
    if (!old) return asFields(rush.shopAddress);
    const restored = addressFrom(old, 'delivery_');
    const oldCustomer = customers.find((c) => String(c.id) === String(old.customer_id ?? selectedCustomerId ?? ''));
    const candidates = [rush.shopAddress, oldCustomer?.address].filter(Boolean).map(asFields);
    return candidates.find((c) => sameAddress(c, restored)) || null;
  });
  const [editingAddress, setEditingAddress] = useState(() => Boolean(old)); // after a failed save, show the fields again
  const [items, setItems] = useState(() => {
    const rows = old?.items && Object.keys(old.items).length ? old.items : { 0: {} };
    // A custom row posts custom_name and custom_category (even empty); a product row never does.
    return Object.keys(rows).map((key) => ({ key: Number(key), quantity: 1, ...rows[key], custom: 'custom_category' in rows[key] || Boolean(rows[key].custom_name) }));
  });
  const [nextKey, setNextKey] = useState(() => Math.max(...items.map((i) => i.key)) + 1);

  const customer = customers.find((c) => String(c.id) === customerId) || null;
  const saved = customer?.address || null;
  const shop = rush.shopAddress || null;
  // Regular or rush comes from the due date: due within a day of today is rush.
  const isRush = rushFor(scheduledAt, rush.orderDate, rush.withinDays) === true;

  const visibleCustomers = useMemo(() => {
    const words = finder.toLowerCase().split(/\s+/).filter(Boolean);
    if (!words.length) return customers;
    return customers.filter((c) => {
      const text = `${c.name} · ${c.contact}`.toLowerCase();
      const digits = text.replace(/\D/g, '');
      return words.every((w) => text.includes(w) || (/^\d+$/.test(w) && digits.includes(w)));
    });
  }, [customers, finder]);

  // The place starts as the shop (pickup) or the customer's saved address (delivery) and can
  // be changed for this order. A place the user typed is never overwritten.
  function applySaved(nextCustomer, nextType, current) {
    const target = nextType === 'delivery' ? (nextCustomer?.address || null) : shop;
    const untouched = isBlankAddress(current) || (autoFilled && sameAddress(current, autoFilled));
    if (!untouched) return;
    if (target) {
      const filled = asFields(target);
      setDelivery(filled);
      setAutoFilled(filled);
      setEditingAddress(false);
    } else if (autoFilled) {
      // A delivery for a customer with no saved address: the previous address must not follow them.
      setDelivery({ ...emptyAddress(), city: defaults.city || '', province: defaults.province || '' });
      setAutoFilled(null);
    }
  }

  function chooseCustomer(id) {
    setCustomerId(id);
    applySaved(customers.find((c) => String(c.id) === id) || null, orderType, delivery);
  }

  function chooseType(type) {
    setOrderType(type);
    applySaved(customer, type, delivery);
  }

  function onFinder(text) {
    setFinder(text);
    const words = text.toLowerCase().split(/\s+/).filter(Boolean);
    if (!words.length) return;
    const matches = customers.filter((c) => {
      const t = `${c.name} · ${c.contact}`.toLowerCase();
      return words.every((w) => t.includes(w) || (/^\d+$/.test(w) && t.replace(/\D/g, '').includes(w)));
    });
    const stillMatches = matches.some((c) => String(c.id) === customerId);
    if (matches.length && !stillMatches) chooseCustomer(String(matches[0].id));
  }

  function updateItem(key, changes) {
    setItems((rows) => rows.map((row) => (row.key === key ? { ...row, ...changes } : row)));
  }

  function chooseProduct(key, productId) {
    const product = products.find((p) => String(p.id) === productId);
    const changes = { product_id: productId, unit_price: product ? Number(product.price).toFixed(2) : '' };
    // Only cakes have details; switching to a pastry, bread or souvenir drops them.
    if (!product || product.category !== 'cake') Object.assign(changes, emptySpecs());
    updateItem(key, changes);
  }

  /** Custom item: typed in instead of picked from the price list. */
  function setCustom(key, custom) {
    const changes = custom
      ? { custom: true, product_id: '', unit_price: '', custom_name: '', custom_category: 'cake' }
      : { custom: false, custom_name: '', custom_category: '', product_id: '', unit_price: '', ...emptySpecs() };
    updateItem(key, changes);
  }

  function chooseCustomCategory(key, category) {
    const changes = { custom_category: category };
    if (category !== 'cake') Object.assign(changes, emptySpecs());
    updateItem(key, changes);
  }

  const productOf = (row) => (row.custom ? null : products.find((p) => String(p.id) === String(row.product_id ?? '')) || null);
  const categoryOf = (row) => (row.custom ? row.custom_category : productOf(row)?.category);

  function addItem() {
    setItems((rows) => [...rows, { key: nextKey, quantity: 1, custom: false }]);
    setNextKey(nextKey + 1);
  }

  function removeItem(key) {
    setItems((rows) => (rows.length > 1 ? rows.filter((row) => row.key !== key) : [{ key: rows[0].key, quantity: 1, custom: false }]));
  }

  const subtotal = (row) => money((parseFloat(row.quantity) || 0) * (parseFloat(row.unit_price) || 0));
  const itemsTotal = money(items.reduce((sum, row) => sum + subtotal(row), 0));
  const feePercent = isRush ? (parseFloat(rushFee) || 0) : 0;
  const rushFeeAmount = money(itemsTotal * feePercent / 100);
  const deliveryFeeAmount = orderType === 'delivery' ? money(parseFloat(deliveryFee) || 0) : 0;
  const total = money(itemsTotal + rushFeeAmount + deliveryFeeAmount);
  const confirmAmount = isRush ? total : money(total / 2);
  const count = items.reduce((sum, row) => sum + ((row.product_id || row.custom_name) ? (parseFloat(row.quantity) || 0) : 0), 0);
  const groups = useMemo(() => {
    const byCategory = new Map();
    products.forEach((p) => { if (!byCategory.has(p.categoryLabel)) byCategory.set(p.categoryLabel, []); byCategory.get(p.categoryLabel).push(p); });
    return Array.from(byCategory.entries());
  }, [products]);

  const target = orderType === 'delivery' ? saved : shop;
  const showAddressEditor = editingAddress || !target || isBlankAddress(delivery);
  const addressErrors = ADDRESS_KEYS.some((k) => errors[`delivery_${k}`]);
  const atAutoFilled = autoFilled && sameAddress(delivery, autoFilled);
  const outsideCity = orderType === 'delivery' && rush.deliveryCity && delivery.city && cityKey(delivery.city) !== cityKey(rush.deliveryCity);
  const savedChoices = [shop ? { label: 'Use the shop address', fields: shop } : null, saved ? { label: `Use ${customer.firstName}'s saved address`, fields: saved } : null].filter(Boolean);

  return (
    <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
      <div>
        <div className="card">
          <div className="card-head"><h2 className="card-title">Customer and schedule</h2></div>
          <div className="card-body">
            <Field id="customer_id" label="Customer" error={errors.customer_id}>
              {customers.length > 8 && (
                <div className="relative mb-2">
                  <svg className="icon pointer-events-none absolute top-1/2 left-[11px] size-4 -translate-y-1/2 text-ink-3" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5" /><path d="M20 20l-4-4" /></svg>
                  <input type="search" className="input pl-[34px]" placeholder="Type a name or number to find the customer" aria-label="Find customer" value={finder} onChange={(e) => onFinder(e.target.value)} />
                </div>
              )}
              <div className="flex gap-2">
                <select id="customer_id" name="customer_id" className={`${inputClass('select', errors.customer_id)} min-w-0 flex-1`} value={customerId} onChange={(e) => chooseCustomer(e.target.value)} required>
                  <option value="">Choose a customer...</option>
                  {customers.map((c) => (
                    <option key={c.id} value={c.id} hidden={!visibleCustomers.includes(c) && String(c.id) !== customerId}>{c.name} · {c.contact}</option>
                  ))}
                </select>
                <button type="button" className="btn" data-modal-open="createCustomer">
                  <svg className="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg> New
                </button>
              </div>
            </Field>
            <div className="grid gap-x-4 md:grid-cols-2">
              <Field id="scheduled_at" label="Due date and time" error={errors.scheduled_at}>
                <input id="scheduled_at" type="datetime-local" name="scheduled_at" value={scheduledAt} min={minDate} onChange={(e) => setScheduledAt(e.target.value)} className={inputClass('input', errors.scheduled_at)} required />
              </Field>
              <Field id="type_pickup" label="Pickup or delivery" error={errors.order_type}>
                <div className="flex flex-wrap gap-2.5">
                  <label className="choice"><input id="type_pickup" type="radio" name="order_type" value="pickup" checked={orderType === 'pickup'} onChange={() => chooseType('pickup')} /> Pickup</label>
                  <label className="choice"><input type="radio" name="order_type" value="delivery" checked={orderType === 'delivery'} onChange={() => chooseType('delivery')} /> Delivery</label>
                </div>
              </Field>
              <div className="field" data-kind={isRush ? 'rush' : 'regular'}>
                <span className="label">Type of order</span>
                {!scheduledAt ? (
                  <div className="hint mt-0">Set the due date: due tomorrow or today is a rush order, later is regular.</div>
                ) : isRush ? (
                  <div><span className="badge b-orange" data-kind-badge>Rush order</span><div className="hint">Due within {rush.withinDays} day of today, so it adds a fee and is paid in full before the kitchen starts.</div></div>
                ) : (
                  <div><span className="badge" data-kind-badge>Regular order</span><div className="hint">Due 2 or more days from today. 50% down payment to confirm, or pay in full.</div></div>
                )}
              </div>
              {isRush && (
                <Field id="rush_fee_percent" label="Rush fee (% of the items)" error={errors.rush_fee_percent} hint="Added on top of the items.">
                  <div className="flex items-center gap-2">
                    <input id="rush_fee_percent" type="number" name="rush_fee_percent" value={rushFee} min={0.5} max={100} step={0.5} onChange={(e) => setRushFee(e.target.value)} className={`${inputClass('input', errors.rush_fee_percent)} max-w-[120px]`} required />
                    {rush.choices.map((choice) => (
                      <button key={choice} type="button" className={`chip ${trimPercent(rushFee) === trimPercent(choice) ? 'chip-active' : ''}`} onClick={() => setRushFee(String(choice))}>{choice}%</button>
                    ))}
                  </div>
                </Field>
              )}
            </div>
            <div className="mt-[18px] mb-3.5">
              <div className="mb-2 flex flex-col gap-0.5">
                <span className="label mb-0">{orderType === 'delivery' ? 'Delivery address' : 'Pickup spot'}</span>
                {orderType === 'delivery' && !customer && <span className="hint mt-0">Choose the customer first. Deliveries go around {rush.deliveryCity} only.</span>}
                {orderType === 'delivery' && customer && !saved && <span className="hint mt-0">This customer has no saved address yet, so please enter where to deliver (around {rush.deliveryCity} only).</span>}
                {orderType === 'pickup' && <span className="hint mt-0">Where the customer collects the order: the shop, or a meet-up spot they ask for.</span>}
              </div>
              {showAddressEditor ? (
                <AddressPicker prefix="delivery_" idp="del_" value={delivery} onChange={setDelivery} errors={errors} map={map} saved={savedChoices} />
              ) : (
                <div className="flex flex-wrap items-start justify-between gap-3 rounded-sm border border-line bg-surface-2 px-3.5 py-3" data-address-summary>
                  <div>
                    <div className="font-medium">{formatAddress(delivery)}</div>
                    <div className="hint mt-0.5">
                      {orderType === 'delivery'
                        ? (atAutoFilled ? "The customer's saved address. Press Edit if this delivery goes somewhere else." : 'Changed for this order only.')
                        : (atAutoFilled ? 'The shop. Press Edit if the customer wants to meet somewhere else.' : 'Meet-up spot for this order.')}
                    </div>
                    {addressErrors && <div className="error-text">Please check the address.</div>}
                  </div>
                  <button type="button" className="btn btn-sm" onClick={() => setEditingAddress(true)} data-address-edit>
                    <svg className="icon size-3.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4l10-10-4-4L4 16v4z" /><path d="M12.5 7.5l4 4" /></svg> Edit
                  </button>
                  {ADDRESS_KEYS.map((k) => <input key={k} type="hidden" name={`delivery_${k}`} value={delivery[k] || ''} />)}
                </div>
              )}
              {outsideCity && <div className="error-text mt-2" data-outside-city>Deliveries only go around {rush.deliveryCity}. This address is in {delivery.city}; choose Pickup instead, or a {rush.deliveryCity} address.</div>}
            </div>
            {orderType === 'delivery' && (
              <Field id="delivery_fee" label="Delivery fee" optional error={errors.delivery_fee} hint="What the delivery app (Maxim) quotes for this address. Added to the total.">
                <input id="delivery_fee" type="number" name="delivery_fee" value={deliveryFee} min={0} max={99999} step={0.01} placeholder="0.00" onChange={(e) => setDeliveryFee(e.target.value)} className={`${inputClass('input', errors.delivery_fee)} max-w-[160px]`} />
              </Field>
            )}
            <Field id="notes" label="Notes" optional error={errors.notes}>
              <textarea id="notes" name="notes" rows={2} maxLength={1000} className="textarea" placeholder="Anything the kitchen or the rider should know" value={notes} onChange={(e) => setNotes(e.target.value)} />
            </Field>
          </div>
        </div>

        <div className="card">
          <div className="card-head">
            <h2 className="card-title">Items</h2>
            <span className="card-sub">The price comes from the price list and can be changed for this order.</span>
          </div>
          <div className="card-body">
            <div className="grid gap-3">
              {items.map((row) => {
                const n = `items[${row.key}]`;
                const e = (field) => errors[`items.${row.key}.${field}`];
                const id = (field) => `item_${row.key}_${field}`;
                const category = categoryOf(row);
                return (
                  <div key={row.key} className="rounded-lg border border-line bg-surface-2 p-4" data-item data-custom={row.custom ? '1' : undefined}>
                    <div className="mb-2.5 flex items-center justify-between font-[650]">
                      <span>{row.custom ? 'Custom item' : 'Item'}</span>
                      <button type="button" className="btn btn-ghost btn-sm" onClick={() => removeItem(row.key)}>Remove</button>
                    </div>
                    <div className="grid gap-x-4 md:grid-cols-2">
                      {row.custom ? (
                        <>
                          <Field id={id('custom_name')} label="Item name" error={e('custom_name')}>
                            <div className="flex gap-2">
                              <input id={id('custom_name')} name={`${n}[custom_name]`} value={row.custom_name ?? ''} maxLength={120} className={`${inputClass('input', e('custom_name'))} min-w-0 flex-1`} placeholder="e.g. 3-layer number cake" onChange={(ev) => updateItem(row.key, { custom_name: ev.target.value })} required />
                              <button type="button" className="btn whitespace-nowrap" onClick={() => setCustom(row.key, false)} title="Pick from the price list instead">Price list</button>
                            </div>
                          </Field>
                          <Field id={id('custom_category')} label="Kind of item" error={e('custom_category')}>
                            <select id={id('custom_category')} name={`${n}[custom_category]`} className={inputClass('select', e('custom_category'))} value={row.custom_category ?? 'cake'} onChange={(ev) => chooseCustomCategory(row.key, ev.target.value)} required>
                              {Object.entries(categories).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                            </select>
                          </Field>
                        </>
                      ) : (
                        <Field id={id('product')} label="Product" error={e('product_id')} className="md:col-span-2">
                          <div className="flex gap-2">
                            <select id={id('product')} name={`${n}[product_id]`} className={`${inputClass('select', e('product_id'))} min-w-0 flex-1`} value={row.product_id ?? ''} onChange={(ev) => chooseProduct(row.key, ev.target.value)} required>
                              <option value="">Choose a product...</option>
                              {groups.map(([label, group]) => (
                                <optgroup key={label} label={label}>
                                  {group.map((p) => <option key={p.id} value={p.id}>{p.name} · {peso(p.price)}</option>)}
                                </optgroup>
                              ))}
                            </select>
                            <button type="button" className="btn whitespace-nowrap" onClick={() => setCustom(row.key, true)} title="Type an item that is not on the price list" data-custom-item>Custom</button>
                          </div>
                        </Field>
                      )}
                      <div className="grid grid-cols-2 gap-x-4 md:col-span-2">
                        <Field id={id('qty')} label="Quantity" error={e('quantity')}>
                          <input id={id('qty')} type="number" name={`${n}[quantity]`} value={row.quantity ?? 1} min={1} max={1000} step={1} onChange={(ev) => updateItem(row.key, { quantity: ev.target.value })} className={inputClass('input', e('quantity'))} required />
                        </Field>
                        <Field id={id('price')} label="Price each (₱)" error={e('unit_price')}>
                          <input id={id('price')} type="number" name={`${n}[unit_price]`} value={row.unit_price ?? ''} min={0} step={0.01} onChange={(ev) => updateItem(row.key, { unit_price: ev.target.value })} className={inputClass('input', e('unit_price'))} placeholder={row.custom ? 'Agreed price' : ''} required />
                        </Field>
                      </div>
                      {category === 'cake' ? (
                        <div className="md:col-span-2">
                          <CakeSpecFields idp={`item_${row.key}_`} nameFor={(field) => `${n}[${field}]`} values={row} onChange={(changes) => updateItem(row.key, changes)}
                            err={e} options={cake} photoMaxKb={photoMaxKb} photoMaxLabel={photoMaxLabel} />
                        </div>
                      ) : (
                        (row.product_id || row.custom) ? <p className="hint mb-3.5 md:col-span-2">No extra details for this kind of item. Anything special can go in the order notes.</p> : null
                      )}
                    </div>
                    <div className="money-row mt-3.5"><span className="text-ink-2">Subtotal</span><strong data-subtotal>{peso(subtotal(row))}</strong></div>
                  </div>
                );
              })}
            </div>
            <div className="mt-3 flex flex-wrap items-center justify-between gap-2.5">
              <button type="button" className="btn" onClick={addItem}>
                <svg className="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg> Add another item
              </button>
            </div>
          </div>
        </div>
      </div>

      <div>
        <div className="card xl:sticky xl:top-5">
          <div className="card-head"><h2 className="card-title">Summary</h2></div>
          <div className="card-body money">
            <div className="money-row"><span className="text-ink-2">Items</span><span data-sum-count>{count}</span></div>
            {(isRush || deliveryFeeAmount > 0) && (
              <div className="money-row"><span className="text-ink-2">Items total</span><span data-sum-items>{peso(itemsTotal)}</span></div>
            )}
            {isRush && (
              <div className="money-row"><span className="text-ink-2">Rush fee ({trimPercent(feePercent) || 0}%)</span><span data-sum-fee>{peso(rushFeeAmount)}</span></div>
            )}
            {deliveryFeeAmount > 0 && (
              <div className="money-row"><span className="text-ink-2">Delivery fee</span><span data-sum-delivery-fee>{peso(deliveryFeeAmount)}</span></div>
            )}
            <div className="money-row total"><span>Total</span><span data-sum-total>{peso(total)}</span></div>
            <div className="money-row"><span className="text-ink-2">{isRush ? 'Full payment needed (rush)' : 'Down payment needed (50%)'}</span><span data-sum-dp>{peso(confirmAmount)}</span></div>
            <p className="hint">{isRush ? 'Record the full payment on the next page; a rush order is confirmed once it is paid in full.' : 'Record the down payment on the next page. The order is confirmed once it is paid.'}</p>
          </div>
          <div className="card-foot">
            <button type="submit" className="btn btn-primary btn-block">Save order</button>
          </div>
        </div>
      </div>
    </div>
  );
}
