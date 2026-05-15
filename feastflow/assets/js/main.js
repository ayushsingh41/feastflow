// ============================================================
// FeastFlow — Main JavaScript
// ============================================================

const APP_URL = document.querySelector('meta[name="app-url"]')?.content || '';

// ── Theme Toggle ──────────────────────────────────────────
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = document.getElementById('themeIcon');
const html        = document.documentElement;

const savedTheme = localStorage.getItem('ff_theme') || 'dark';
html.setAttribute('data-theme', savedTheme);
updateThemeIcon(savedTheme);

themeToggle?.addEventListener('click', () => {
  const current = html.getAttribute('data-theme');
  const next    = current === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  localStorage.setItem('ff_theme', next);
  updateThemeIcon(next);
});

function updateThemeIcon(theme) {
  if (!themeIcon) return;
  themeIcon.className = theme === 'dark' ? 'ri-sun-line' : 'ri-moon-line';
}

// ── Sidebar Toggle ────────────────────────────────────────
const sidebar        = document.getElementById('sidebar');
const sidebarToggle  = document.getElementById('sidebarToggle');
const sidebarOverlay = document.getElementById('sidebarOverlay');

sidebarToggle?.addEventListener('click', toggleSidebar);
sidebarOverlay?.addEventListener('click', closeSidebar);

function toggleSidebar() {
  sidebar?.classList.toggle('open');
  sidebarOverlay?.classList.toggle('open');
}
function closeSidebar() {
  sidebar?.classList.remove('open');
  sidebarOverlay?.classList.remove('open');
}

// ── User Dropdown ─────────────────────────────────────────
const userMenu    = document.getElementById('userMenu');
const userTrigger = userMenu?.querySelector('.user-trigger');

userTrigger?.addEventListener('click', (e) => {
  e.stopPropagation();
  userMenu.classList.toggle('open');
});

document.addEventListener('click', () => {
  userMenu?.classList.remove('open');
});

// ── Toast Auto-dismiss ────────────────────────────────────
document.querySelectorAll('.toast').forEach(toast => {
  setTimeout(() => toast.remove(), 4000);
});

function showToast(msg, type = 'info') {
  const icons = { success: 'check-circle', error: 'error-warning', info: 'information', warning: 'alert' };
  const container = document.getElementById('toastContainer') || (() => {
    const c = document.createElement('div');
    c.id = 'toastContainer';
    c.className = 'toast-container';
    document.body.appendChild(c);
    return c;
  })();

  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.innerHTML = `<i class="ri-${icons[type] || 'information'}-line"></i><span>${msg}</span><button onclick="this.parentElement.remove()"><i class="ri-close-line"></i></button>`;
  container.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}

// ── Cart AJAX ─────────────────────────────────────────────
async function addToCart(productId, btn) {
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="loading"></span>'; }

  try {
    const res  = await fetch(`${window.APP_URL}/customer/cart.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=add&product_id=${productId}&_ff_csrf=${window.CSRF_TOKEN}`
    });
    const data = await res.json();

    if (data.success) {
      showToast(data.message || 'Added to cart!', 'success');
      // Update cart badge
      document.querySelectorAll('.badge-dot, .nav-badge[data-cart]').forEach(el => {
        el.textContent = data.cart_count;
        el.style.display = data.cart_count > 0 ? '' : 'none';
      });
    } else {
      showToast(data.message || 'Failed to add item.', 'error');
    }
  } catch (e) {
    showToast('Something went wrong.', 'error');
  }

  if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ri-shopping-cart-2-line"></i> Add to Cart'; }
}

// ── Delete Confirm ────────────────────────────────────────
function confirmDelete(url, msg = 'Are you sure you want to delete this?') {
  if (confirm(msg)) window.location.href = url;
}

// ── Animated Counters ─────────────────────────────────────
function animateCounter(el) {
  const target = parseFloat(el.dataset.target) || 0;
  const isPrice = el.dataset.type === 'price';
  const duration = 1200;
  const start = performance.now();

  function update(now) {
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    const current = Math.floor(target * eased);
    el.textContent = isPrice ? '₹' + current.toLocaleString() : current.toLocaleString();
    if (progress < 1) requestAnimationFrame(update);
    else el.textContent = isPrice ? '₹' + target.toLocaleString() : target.toLocaleString();
  }
  requestAnimationFrame(update);
}

const counterObserver = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) { animateCounter(e.target); counterObserver.unobserve(e.target); } });
}, { threshold: 0.5 });

document.querySelectorAll('[data-target]').forEach(el => counterObserver.observe(el));

// ── Category Filter ───────────────────────────────────────
document.querySelectorAll('.cat-pill').forEach(pill => {
  pill.addEventListener('click', () => {
    document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    const catId = pill.dataset.cat;
    document.querySelectorAll('.product-card').forEach(card => {
      card.style.display = (!catId || card.dataset.cat === catId) ? '' : 'none';
    });
  });
});

// ── Cart Quantity Controls ────────────────────────────────
function updateQty(productId, delta, currentQty) {
  const newQty = currentQty + delta;
  fetch(`${window.APP_URL}/customer/cart.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=update&product_id=${productId}&quantity=${newQty}&_ff_csrf=${window.CSRF_TOKEN}`
  }).then(r => r.json()).then(d => {
    if (d.success) location.reload();
    else showToast(d.message, 'error');
  });
}

// ── Apply Coupon ──────────────────────────────────────────
document.getElementById('applyCoupon')?.addEventListener('click', async () => {
  const code = document.getElementById('couponCode')?.value;
  if (!code) return showToast('Enter a coupon code.', 'warning');

  const res  = await fetch(`${window.APP_URL}/customer/cart.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=coupon&code=${encodeURIComponent(code)}&_ff_csrf=${window.CSRF_TOKEN}`
  });
  const data = await res.json();
  showToast(data.message, data.success ? 'success' : 'error');
  if (data.success) location.reload();
});

// ── Confirm Modals ────────────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', (e) => {
    if (!confirm(el.dataset.confirm)) e.preventDefault();
  });
});

// ── Search Live Filter ────────────────────────────────────
const searchInput = document.getElementById('filterSearch');
searchInput?.addEventListener('input', () => {
  const q = searchInput.value.toLowerCase();
  document.querySelectorAll('[data-searchable]').forEach(row => {
    row.style.display = row.dataset.searchable.toLowerCase().includes(q) ? '' : 'none';
  });
});

// ── Image Preview ─────────────────────────────────────────
document.querySelectorAll('input[type=file][data-preview]').forEach(input => {
  input.addEventListener('change', () => {
    const preview = document.getElementById(input.dataset.preview);
    if (preview && input.files[0]) {
      preview.src = URL.createObjectURL(input.files[0]);
      preview.style.display = 'block';
    }
  });
});

// ── Status Update (Admin) ─────────────────────────────────
document.querySelectorAll('.status-select').forEach(sel => {
  sel.addEventListener('change', async function() {
    const orderId = this.dataset.orderId;
    const status  = this.value;
    const res  = await fetch(`${window.APP_URL}/admin/orders.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=update_status&order_id=${orderId}&status=${status}&_ff_csrf=${window.CSRF_TOKEN}`
    });
    const data = await res.json();
    showToast(data.message, data.success ? 'success' : 'error');
  });
});

// ── Expose globals for inline use ────────────────────────
window.showToast = showToast;
window.addToCart = addToCart;
window.updateQty = updateQty;
window.confirmDelete = confirmDelete;
