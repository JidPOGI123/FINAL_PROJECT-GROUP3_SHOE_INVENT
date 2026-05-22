/* ============================================================
   ShStorage — request.js
   Branch request cart logic — with colorway support.
   ============================================================ */

/* ---- Cart state ---- */
const CART_KEY = 'shstorage_cart';

function loadCart() {
  try {
    const saved = localStorage.getItem(CART_KEY);
    return saved ? JSON.parse(saved) : [];
  } catch(e) { return []; }
}

function saveCart() {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
}

let cart = loadCart(); // [{ shoe_id, shoe_name, brand, size, quantity, max_stock, colorway_id, colorway }]

/* ---- Add item to cart ---- */
function addToCart(shoeId, shoeName, brand, size, quantity, maxStock, colorwayId, colorway) {
  quantity   = parseInt(quantity)  || 1;
  maxStock   = parseInt(maxStock)  || 999;
  colorwayId = colorwayId ?? null;
  colorway   = colorway   || '';

  // Same shoe + same colorway + same size → merge
  const existing = cart.find(function(i) {
    return i.shoe_id === shoeId && i.size === size && i.colorway_id === colorwayId;
  });

  if (existing) {
    existing.quantity = Math.min(existing.quantity + quantity, maxStock);
  } else {
    cart.push({ shoe_id: shoeId, shoe_name: shoeName, brand, size, quantity, max_stock: maxStock, colorway_id: colorwayId, colorway });
  }

  saveCart();
  renderCart();
  updateCartBadge();
  const label = colorway ? shoeName + ' — ' + colorway + ' (US ' + size + ')' : shoeName + ' (US ' + size + ')';
  showCartToast(label + ' added to request.');
}

/* ---- Remove item ---- */
function removeFromCart(index) {
  cart.splice(index, 1);
  saveCart();
  renderCart();
  updateCartBadge();
}

/* ---- Change quantity ---- */
function changeQty(index, delta) {
  const item = cart[index];
  if (!item) return;
  item.quantity = Math.max(1, Math.min(item.quantity + delta, item.max_stock));
  saveCart();
  renderCart();
}

/* ---- Render cart list ---- */
function renderCart() {
  const container = document.getElementById('cartItems');
  const emptyMsg  = document.getElementById('cartEmpty');
  const submitBtn = document.getElementById('submitBtn');
  const totalEl   = document.getElementById('cartTotal');

  if (!container) return;

  if (cart.length === 0) {
    container.innerHTML = '';
    if (emptyMsg)  emptyMsg.style.display  = '';
    if (submitBtn) submitBtn.disabled = true;
    if (totalEl)   totalEl.textContent = '0 items';
    buildHiddenFields();
    return;
  }

  if (emptyMsg)  emptyMsg.style.display = 'none';
  if (submitBtn) submitBtn.disabled = false;

  let totalQty = 0;
  container.innerHTML = cart.map(function(item, i) {
    totalQty += item.quantity;
    const meta = [item.brand, item.colorway].filter(Boolean).join(' · ');
    return `
      <div class="cart-item">
        <div class="cart-item-img">
          <span style="font-size:1.4rem">👟</span>
        </div>
        <div class="cart-item-info">
          <span class="cart-item-name">${escHtml(item.shoe_name)}</span>
          <span class="cart-item-meta">${escHtml(meta)}</span>
          <span class="cart-item-size">Size US ${escHtml(item.size)}</span>
        </div>
        <div class="cart-item-qty">
          <button type="button" class="qty-btn" onclick="changeQty(${i}, -1)">−</button>
          <span class="qty-display">${item.quantity}</span>
          <button type="button" class="qty-btn" onclick="changeQty(${i}, 1)">+</button>
        </div>
        <button type="button" class="cart-item-remove" onclick="removeFromCart(${i})" title="Remove">✕</button>
      </div>
    `;
  }).join('');

  if (totalEl) totalEl.textContent = totalQty + ' pair' + (totalQty !== 1 ? 's' : '');
  buildHiddenFields();
}

/* ---- Build hidden form inputs for submission ---- */
function buildHiddenFields() {
  const form = document.getElementById('requestForm');
  if (!form) return;

  form.querySelectorAll('.cart-hidden').forEach(function(el) { el.remove(); });

  cart.forEach(function(item, i) {
    const fields = {
      [`items[${i}][shoe_id]`]:     item.shoe_id,
      [`items[${i}][colorway_id]`]: item.colorway_id ?? '',
      [`items[${i}][colorway]`]:    item.colorway,
      [`items[${i}][size]`]:        item.size,
      [`items[${i}][quantity]`]:    item.quantity,
    };
    Object.entries(fields).forEach(function([name, value]) {
      const input = document.createElement('input');
      input.type  = 'hidden';
      input.name  = name;
      input.value = value;
      input.className = 'cart-hidden';
      form.appendChild(input);
    });
  });
}

/* ---- Cart badge ---- */
function updateCartBadge() {
  const badge = document.getElementById('cartCount');
  if (badge) {
    badge.textContent  = cart.length;
    badge.style.display = cart.length > 0 ? '' : 'none';
  }
}

/* ---- Legacy openSizePicker (no colorways) — kept for compatibility ---- */
function openSizePicker(shoeId, shoeName, brand, sizesJson) {
  const sizes  = JSON.parse(sizesJson);
  const modal  = document.getElementById('sizePickerModal');
  const title  = document.getElementById('sizePickerTitle');
  const picker = document.getElementById('sizePicker');
  const qtyIn  = document.getElementById('sizePickerQty');
  if (!modal || !picker) return;

  if (title) title.textContent = shoeName;
  picker.innerHTML = '';
  let selectedSize = null;

  sizes.forEach(function(s) {
    const btn    = document.createElement('button');
    btn.type     = 'button';
    btn.className = 'size-pick-btn' + (s.quantity === 0 ? ' out' : '');
    btn.textContent = 'US ' + s.size;
    btn.disabled    = s.quantity === 0;
    btn.addEventListener('click', function() {
      picker.querySelectorAll('.size-pick-btn').forEach(function(b) { b.classList.remove('active'); });
      btn.classList.add('active');
      selectedSize = s;
    });
    picker.appendChild(btn);
  });

  const confirmBtn = document.getElementById('sizePickerConfirm');
  if (confirmBtn) {
    confirmBtn.onclick = function() {
      if (!selectedSize) { alert('Please select a size.'); return; }
      const qty = parseInt(qtyIn ? qtyIn.value : 1) || 1;
      addToCart(shoeId, shoeName, brand, selectedSize.size, qty, selectedSize.quantity, null, '');
      modal.classList.remove('active');
    };
  }
  modal.classList.add('active');
}

function closeSizePicker() {
  const modal = document.getElementById('sizePickerModal');
  if (modal) modal.classList.remove('active');
}

/* ---- Toast notification ---- */
function showCartToast(message) {
  let toast = document.getElementById('cartToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'cartToast';
    toast.style.cssText = `
      position: fixed; bottom: 24px; right: 24px; z-index: 999;
      background: #1a1e2b; border: 1px solid #4caf50; color: #a5d6a7;
      padding: 12px 18px; border-radius: 8px; font-size: .85rem;
      box-shadow: 0 4px 16px rgba(0,0,0,.4);
      transition: opacity .3s ease; pointer-events: none;
    `;
    document.body.appendChild(toast);
  }
  toast.textContent = '✅ ' + message;
  toast.style.opacity = '1';
  clearTimeout(toast._timer);
  toast._timer = setTimeout(function() { toast.style.opacity = '0'; }, 2800);
}

/* ---- Escape HTML ---- */
function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/* ---- Init ---- */
document.addEventListener('DOMContentLoaded', function() {
  renderCart();
  updateCartBadge();
});