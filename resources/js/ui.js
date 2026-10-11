/* SweetNest OIMS - small page helpers shared by every screen.
 * The interactive forms are React components (see components/); this file covers the
 * plain behaviours: the phone menu, pop-ups, confirmations, flash messages, drop-down
 * menus, tabs and the photo size check. */

// ---------- Sidebar on phones ----------
document.addEventListener('click', (e) => {
  if (e.target.closest('[data-nav-toggle]')) {
    document.body.classList.toggle('nav-open');
  } else if (e.target.closest('[data-scrim]')) {
    document.body.classList.remove('nav-open');
  }
});

// ---------- Pop-ups (native <dialog>) ----------
// <button data-modal-open="editCustomer"> opens <dialog id="editCustomer">.
// Optional on the button: data-action="/url" (form action), data-fill='{"field":"value"}',
// data-title="Heading" (text for [data-modal-title]).
export function openModal(id, opener) {
  const dialog = document.getElementById(id);
  if (!dialog) return;
  const form = dialog.querySelector('form');

  if (opener) {
    if (opener.dataset.action && form) form.action = opener.dataset.action;
    if (opener.dataset.title) {
      const title = dialog.querySelector('[data-modal-title]');
      if (title) title.textContent = opener.dataset.title;
    }
    if (opener.dataset.fill) {
      const values = JSON.parse(opener.dataset.fill);
      Object.keys(values).forEach((name) => {
        const field = dialog.querySelector(`[name="${name}"]:not([data-react-field])`);
        if (!field) return;
        if (field.type === 'checkbox') field.checked = !!values[name];
        else field.value = values[name] == null ? '' : values[name];
        field.dispatchEvent(new Event('change', { bubbles: true }));
      });
      // React parts inside the pop-up (the address picker, the cake details) take their values from this event.
      dialog.querySelectorAll('[data-react]').forEach((root) => {
        root.dispatchEvent(new CustomEvent('react:fill', { detail: values }));
      });
      // Clear old error messages and chosen files when the pop-up is reused for another row.
      dialog.querySelectorAll('.error-text[data-clearable]').forEach((el) => el.remove());
      dialog.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
      dialog.querySelectorAll('input[type=file]').forEach((el) => { el.value = ''; });
    }
    // data-max='{"field":"2026-10-07"}' caps a date field for this row.
    if (opener.dataset.max && form) {
      const caps = JSON.parse(opener.dataset.max);
      Object.keys(caps).forEach((name) => {
        const el = form.querySelector(`[name="${name}"]`);
        if (el) el.max = caps[name];
      });
    }
  }

  if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', '');
  const first = dialog.querySelector('input:not([type=hidden]):not([readonly]):not([disabled]), select, textarea');
  if (first) first.focus();
}
window.openModal = openModal;

document.addEventListener('click', (e) => {
  const opener = e.target.closest('[data-modal-open]');
  if (opener) {
    e.preventDefault();
    openModal(opener.dataset.modalOpen, opener);
    return;
  }
  const closer = e.target.closest('[data-modal-close]');
  if (closer) {
    e.preventDefault();
    const dialog = closer.closest('dialog');
    if (dialog) dialog.close();
    return;
  }
  // Clicking the dark backdrop closes the pop-up.
  if (e.target.tagName === 'DIALOG' && e.target.classList.contains('modal')) {
    const rect = e.target.getBoundingClientRect();
    const inside = e.clientX >= rect.left && e.clientX <= rect.right && e.clientY >= rect.top && e.clientY <= rect.bottom;
    if (!inside) e.target.close();
  }
});

// Pop-ups that failed to save reopen on load: <dialog data-open-on-load>.
document.querySelectorAll('dialog[data-open-on-load]').forEach((dialog) => openModal(dialog.id));

// ---------- Confirm before risky actions ----------
// <form data-confirm="Cancel this order?" data-confirm-button="Yes, cancel">
const confirmDialog = document.getElementById('confirmDialog');
let pendingForm = null;

document.addEventListener('submit', (e) => {
  const form = e.target;

  if (form.dataset.confirm && !form.dataset.confirmed && confirmDialog) {
    e.preventDefault();
    pendingForm = form;
    confirmDialog.querySelector('[data-confirm-text]').textContent = form.dataset.confirm;
    confirmDialog.querySelector('[data-confirm-ok]').textContent = form.dataset.confirmButton || 'Yes, continue';
    confirmDialog.showModal();
    return;
  }

  // Stop double clicks from saving twice.
  setTimeout(() => {
    form.querySelectorAll('button[type=submit], button:not([type])').forEach((b) => {
      b.disabled = true;
      b.classList.add('is-loading');
    });
  }, 0);
});

if (confirmDialog) {
  confirmDialog.querySelector('[data-confirm-ok]').addEventListener('click', () => {
    confirmDialog.close();
    if (pendingForm) {
      pendingForm.dataset.confirmed = '1';
      if (typeof pendingForm.requestSubmit === 'function') pendingForm.requestSubmit(); else pendingForm.submit();
      pendingForm = null;
    }
  });
}

// ---------- Flash messages ----------
// Success messages fade after 6 seconds; errors stay until closed.
document.querySelectorAll('.alert').forEach((alert) => {
  const close = alert.querySelector('.alert-close');
  if (close) close.addEventListener('click', () => alert.remove());
  if (alert.classList.contains('alert-success')) {
    setTimeout(() => {
      alert.style.transition = 'opacity .4s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 400);
    }, 6000);
  }
});

// ---------- Show a section only for one radio value ----------
// <input type="radio" name="order_type" value="delivery" data-shows="#edit-delivery">
// Several targets can be listed, separated by commas.
document.addEventListener('change', (e) => {
  const radio = e.target;
  if (!radio.matches || !radio.matches('input[type=radio][name]')) return;
  const group = document.querySelectorAll(`input[type=radio][name="${radio.name}"][data-shows]`);
  group.forEach((r) => {
    document.querySelectorAll(r.dataset.shows).forEach((target) => { target.hidden = !r.checked; });
  });
});

// ---------- Regular or rush, from the due date ----------
// <input type="datetime-local" data-rush-watch="#edit-rush" data-rush-label="[data-kind-label]"
//        data-order-date="2026-10-11" data-rush-days="1">
// An order due within that many days of the order date is a rush order: the fee box
// shows and the label says "Rush". The server works it out the same way when saving.
export function rushFor(dueValue, orderDate, withinDays) {
  if (!dueValue) return null;
  const due = new Date(`${dueValue.slice(0, 10)}T00:00:00`);
  const placed = new Date(`${orderDate}T00:00:00`);
  if (Number.isNaN(due.getTime()) || Number.isNaN(placed.getTime())) return null;
  return Math.round((due - placed) / 86400000) <= withinDays;
}
function applyRushWatch(input) {
  const rush = rushFor(input.value, input.dataset.orderDate, parseInt(input.dataset.rushDays, 10) || 0);
  if (rush === null) return;
  document.querySelectorAll(input.dataset.rushWatch).forEach((box) => { box.hidden = !rush; });
  if (input.dataset.rushLabel) {
    document.querySelectorAll(input.dataset.rushLabel).forEach((label) => { label.textContent = rush ? 'Rush' : 'Regular'; });
  }
}
document.addEventListener('input', (e) => {
  if (e.target.matches && e.target.matches('[data-rush-watch]')) applyRushWatch(e.target);
});
document.addEventListener('change', (e) => {
  if (e.target.matches && e.target.matches('[data-rush-watch]')) applyRushWatch(e.target);
});

// ---------- Live search ----------
// A search box inside a GET form filters the list as you type: the page is fetched
// with the new words and its <main> swapped in, so paging, tabs and counts stay right.
let searchTimer = null;
let searchRequest = null;
async function liveSearch(input) {
  const form = input.form;
  if (!form) return;
  const params = new URLSearchParams(new FormData(form));
  params.delete('page');
  const url = `${form.getAttribute('action') || location.pathname}?${params.toString()}`;
  if (searchRequest) searchRequest.abort();
  const controller = new AbortController();
  searchRequest = controller;
  const main = document.querySelector('main');
  main.setAttribute('aria-busy', 'true');
  try {
    const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', signal: controller.signal });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    if (response.redirected) throw new Error('Redirected (signed out?)'); // a normal page load shows the right page
    const html = await response.text();
    const fresh = new DOMParser().parseFromString(html, 'text/html').querySelector('main');
    if (!fresh) throw new Error('No main region in the response');
    const caret = input.selectionStart;
    const value = input.value;
    main.innerHTML = fresh.innerHTML;
    history.replaceState(null, '', url);
    const again = main.querySelector(`input[type=search][name="${input.name}"]`);
    if (again) {
      again.value = value;
      again.focus();
      try { again.setSelectionRange(caret, caret); } catch (err) { /* not every input type allows it */ }
    }
    document.dispatchEvent(new CustomEvent('main:replaced'));
  } catch (err) {
    if (err.name !== 'AbortError') form.submit(); // fall back to a normal page load
  } finally {
    if (searchRequest === controller) {
      searchRequest = null;
      main.removeAttribute('aria-busy'); // only the newest request clears it
    }
  }
}
document.addEventListener('input', (e) => {
  const input = e.target;
  if (!input.matches || !input.matches('form[method=get] input[type=search], form[method=GET] input[type=search]')) return;
  if (!input.form || input.form.dataset.liveSearch === 'off') return;
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => liveSearch(input), 300);
});
// Enter still works, but there is nothing left to submit by then.
document.addEventListener('submit', (e) => {
  const form = e.target;
  if (form.matches && form.matches('form[method=get], form[method=GET]') && form.querySelector('input[type=search]') && searchTimer) {
    clearTimeout(searchTimer);
    const input = form.querySelector('input[type=search]');
    e.preventDefault();
    liveSearch(input);
  }
});

// ---------- Photo size check before uploading ----------
// <input type="file" data-max-kb="4096"> refuses a file that is too big right away,
// instead of after a slow upload.
document.addEventListener('change', (e) => {
  const input = e.target;
  if (!input.matches || !input.matches('input[type=file][data-max-kb]') || !input.files.length) return;
  const maxKb = parseInt(input.dataset.maxKb, 10);
  const field = input.closest('.field');
  const old = field ? field.querySelector('.error-text[data-size-error]') : null;
  if (old) old.remove();
  input.classList.remove('is-invalid');
  if (input.files[0].size > maxKb * 1024) {
    const note = document.createElement('div');
    note.className = 'error-text';
    note.setAttribute('data-size-error', '');
    note.textContent = `This photo is too big. Please choose one up to ${Math.round(maxKb / 102.4) / 10} MB (a screenshot usually works).`;
    input.insertAdjacentElement('afterend', note);
    input.classList.add('is-invalid');
    input.value = '';
  }
});

// ---------- "More" drop-down menus ----------
function closeMenus(except) {
  document.querySelectorAll('[data-menu].open').forEach((menu) => {
    if (menu === except) return;
    menu.classList.remove('open');
    const toggle = menu.querySelector('[data-menu-toggle]');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  });
}

document.addEventListener('click', (e) => {
  const toggle = e.target.closest('[data-menu-toggle]');
  if (toggle) {
    const menu = toggle.closest('[data-menu]');
    closeMenus(menu);
    const open = menu.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    return;
  }
  closeMenus(null); // an item was picked, or a click landed elsewhere
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeMenus(null);
});

// ---------- Tabs inside a page (the order page: Order | Payments) ----------
// [data-tabs] holds buttons with data-tab="name"; each shows [data-tab-panel="name"].
// The address keeps "#name" for any tab but the first, so a reload stays on it.
document.querySelectorAll('[data-tabs]').forEach((list) => {
  const tabs = Array.from(list.querySelectorAll('[data-tab]'));
  if (!tabs.length) return;
  const first = tabs[0].dataset.tab;

  function select(name, remember) {
    tabs.forEach((tab) => {
      const on = tab.dataset.tab === name;
      tab.classList.toggle('active', on);
      tab.setAttribute('aria-selected', on ? 'true' : 'false');
      tab.tabIndex = on ? 0 : -1;
      const panel = document.querySelector(`[data-tab-panel="${tab.dataset.tab}"]`);
      if (panel) panel.hidden = !on;
    });
    if (remember && window.history && history.replaceState) {
      history.replaceState(null, '', name === first ? location.pathname + location.search : `#${name}`);
    }
  }

  list.addEventListener('click', (e) => {
    const tab = e.target.closest('[data-tab]');
    if (tab) select(tab.dataset.tab, true);
  });

  // Left and right arrow keys move between tabs.
  list.addEventListener('keydown', (e) => {
    if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
    const i = tabs.indexOf(document.activeElement);
    if (i < 0) return;
    const next = tabs[(i + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
    next.focus();
    select(next.dataset.tab, true);
  });

  const wanted = location.hash.slice(1);
  if (wanted && tabs.some((tab) => tab.dataset.tab === wanted)) select(wanted, false);
});
