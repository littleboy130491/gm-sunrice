// Dropdown select custom

// Create dropdown custom elemen <select> > select asli hide
function buildDropdown(select) {
  const wrap = document.createElement('div');
  wrap.className = 'cmp-dd';

  Array.from(select.classList)
    .filter((c) => c === 'hidden' || /:hidden$/.test(c))
    .forEach((c) => wrap.classList.add(c));

  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'cmp-dd-button ' + select.className;
  button.setAttribute('aria-haspopup', 'listbox');
  button.setAttribute('aria-expanded', 'false');

  const label = document.createElement('span');
  label.className = 'cmp-dd-label notranslate';
  button.appendChild(label);

  const caret = document.createElement('span');
  caret.className = 'cmp-dd-caret';
  button.appendChild(caret);

  const panel = document.createElement('div');
  panel.className = 'cmp-dd-panel';
  panel.setAttribute('role', 'listbox');
  panel.hidden = true;

  const isPlaceholder = (opt) => opt.value === '' && opt.disabled;

  const optionEls = [];
  Array.from(select.children).forEach((child) => {
    if (child.tagName === 'OPTGROUP') {
      const group = document.createElement('div');
      group.className = 'cmp-dd-group';
      group.textContent = child.label;
      panel.appendChild(group);

      Array.from(child.children).forEach((opt) => {
        if (isPlaceholder(opt)) return;
        panel.appendChild(makeOption(opt));
      });
    } else if (child.tagName === 'OPTION') {
      if (isPlaceholder(child)) return;
      panel.appendChild(makeOption(child));
    }
  });

  function makeOption(opt) {
    const el = document.createElement('div');
    el.className = 'cmp-dd-option notranslate';
    el.textContent = opt.textContent.trim();
    el.dataset.value = opt.value;
    el.setAttribute('role', 'option');
    if (opt.disabled) el.setAttribute('aria-disabled', 'true');
    optionEls.push(el);

    el.addEventListener('click', () => {
      if (opt.disabled) return;
      select.value = opt.value;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      syncFromSelect();
      close();
    });
    return el;
  }

  // Tampilan dropdown
  function syncFromSelect() {
    const current = select.options[select.selectedIndex];
    label.textContent = current ? current.textContent.trim() : '';
    button.classList.toggle('is-placeholder', !!current && isPlaceholder(current));
    optionEls.forEach((el) => {
      el.classList.toggle('is-selected', el.dataset.value === select.value);
    });
  }

  function open() {
    document.dispatchEvent(new CustomEvent('cmp-dd:open', { detail: wrap }));
    panel.hidden = false;
    button.setAttribute('aria-expanded', 'true');
    wrap.classList.add('is-open');
  }

  document.addEventListener('cmp-dd:open', (e) => {
    if (e.detail !== wrap) close();
  });

  function close() {
    panel.hidden = true;
    button.setAttribute('aria-expanded', 'false');
    wrap.classList.remove('is-open');
  }

  function toggle() {
    if (panel.hidden) open();
    else close();
  }

  button.addEventListener('click', (e) => {
    e.stopPropagation();
    toggle();
  });

  document.addEventListener('click', (e) => {
    if (!wrap.contains(e.target)) close();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });

  select.parentNode.insertBefore(wrap, select);
  wrap.appendChild(button);
  wrap.appendChild(panel);
  wrap.appendChild(select);
  select.classList.add('cmp-dd-native');

  select.addEventListener('change', syncFromSelect);

  syncFromSelect();
}

function init() {
  document
    .querySelectorAll('.comparison-select, select.contact-form-input')
    .forEach((select) => {
      if (select.dataset.cmpDdReady) return;
      select.dataset.cmpDdReady = '1';
      buildDropdown(select);
    });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
