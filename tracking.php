<?php
// ══════════════════════════════════════════
//  BlueRoute — Track & Trace Page
//  tracking.php
// ══════════════════════════════════════════

require_once __DIR__ . '/includes/helpers.php';

// Handle direct URL ?tracking_number=XXX (from email links etc.)
$prefill = clean($_GET['tracking_number'] ?? '');
$prefillType = clean($_GET['type'] ?? 'shipments');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Track and Trace | BlueRoute Security &amp; Shipping</title>

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/tracking-page.css" />
</head>
<body>

<!-- ══════════════════ TOP BAR ══════════════════ -->
<header class="tp-header">
  <a href="index.html" class="tp-logo">
    <div class="tp-logo-icon">BLUE<br>ROUTE</div>
  </a>
  <a href="index.html" class="tp-home-btn" title="Back to home">
    <i class="fa-solid fa-house"></i>
  </a>
</header>

<!-- ══════════════════ HERO + SEARCH ══════════════════ -->
<main class="tp-hero">

  <!-- Left illustration -->
  <div class="tp-illustration">
    <div class="tp-hero-text">
      <h1>Track and Trace<br>status of your<br>shipments</h1>
    </div>
    <!-- SVG delivery illustration -->
    <div class="tp-svg-wrap">
      <svg viewBox="0 0 420 440" xmlns="http://www.w3.org/2000/svg" class="tp-svg">
        <!-- Phone outline -->
        <rect x="60" y="30" width="210" height="370" rx="30" ry="30" fill="#2a3a5c" opacity=".85"/>
        <rect x="75" y="50" width="180" height="330" rx="18" ry="18" fill="#1a2847"/>
        <!-- Screen content: map dots & route -->
        <circle cx="165" cy="110" r="10" fill="#e63946" opacity=".9"/>
        <circle cx="165" cy="110" r="5"  fill="#fff"/>
        <polyline points="165,120 155,175 180,220 160,270" stroke="#e63946" stroke-width="2.5" fill="none" stroke-dasharray="6,4" opacity=".8"/>
        <circle cx="160" cy="270" r="7" fill="#b8942a"/>
        <!-- Distance badge -->
        <rect x="95" y="320" width="110" height="38" rx="10" fill="#fff" opacity=".12"/>
        <text x="108" y="334" font-size="9" fill="#aaa" font-family="Outfit,sans-serif">total distance</text>
        <text x="108" y="350" font-size="14" fill="#fff" font-weight="700" font-family="Outfit,sans-serif">1,100 m</text>
        <!-- Chat bubble icon -->
        <rect x="186" y="316" width="36" height="36" rx="8" fill="#e63946" opacity=".85"/>
        <text x="197" y="338" font-size="14" fill="#fff" font-family="Outfit,sans-serif">💬</text>

        <!-- Scooter (simplified) -->
        <g transform="translate(140,230) scale(0.85)">
          <!-- Body -->
          <ellipse cx="80" cy="60" rx="55" ry="28" fill="#c0392b"/>
          <rect x="55" y="35" width="50" height="25" rx="10" fill="#e74c3c"/>
          <!-- Windscreen -->
          <ellipse cx="95" cy="32" rx="14" ry="10" fill="#3498db" opacity=".7"/>
          <!-- Wheels -->
          <circle cx="30"  cy="80" r="22" fill="#2c3e50" stroke="#555" stroke-width="4"/>
          <circle cx="30"  cy="80" r="10" fill="#7f8c8d"/>
          <circle cx="135" cy="80" r="22" fill="#2c3e50" stroke="#555" stroke-width="4"/>
          <circle cx="135" cy="80" r="10" fill="#7f8c8d"/>
          <!-- Rider body -->
          <rect x="65" y="10" width="30" height="35" rx="8" fill="#c0392b"/>
          <!-- Helmet -->
          <ellipse cx="80" cy="8"  rx="18" ry="14" fill="#e74c3c"/>
          <ellipse cx="80" cy="10" rx="14" ry="8"  fill="#f39c12" opacity=".6"/>
          <!-- Arm -->
          <line x1="95" y1="30" x2="120" y2="50" stroke="#c0392b" stroke-width="6" stroke-linecap="round"/>
          <!-- Package on back -->
          <rect x="42" y="20" width="24" height="22" rx="4" fill="#f39c12"/>
          <line x1="42" y1="31" x2="66" y2="31" stroke="#e67e22" stroke-width="1.5"/>
          <line x1="54" y1="20" x2="54" y2="42" stroke="#e67e22" stroke-width="1.5"/>
        </g>

        <!-- Leaf decorations -->
        <ellipse cx="68"  cy="390" rx="28" ry="14" fill="#c0392b" opacity=".5" transform="rotate(-30 68 390)"/>
        <ellipse cx="290" cy="370" rx="20" ry="10" fill="#c0392b" opacity=".4" transform="rotate(20 290 370)"/>
      </svg>
    </div>
  </div>

  <!-- Right search panel -->
  <div class="tp-search-panel">

    <!-- Type selector -->
    <div class="tp-type-row">
      <label class="tp-radio <?= $prefillType === 'shipments' ? 'checked' : '' ?>">
        <input type="radio" name="track_type" value="shipments" <?= $prefillType === 'shipments' ? 'checked' : '' ?> />
        <span class="tp-radio-dot"></span>
        shipments
      </label>
      <label class="tp-radio <?= $prefillType === 'vault' ? 'checked' : '' ?>">
        <input type="radio" name="track_type" value="vault" <?= $prefillType === 'vault' ? 'checked' : '' ?> />
        <span class="tp-radio-dot"></span>
        Locker packages
      </label>
    </div>

    <!-- Textarea input -->
    <div class="tp-input-wrap">
      <div class="tp-input-icon"><i class="fa-solid fa-gear"></i></div>
      <textarea
        id="trackingInput"
        placeholder="Enter your Shipping/Tracking/Waybill number&#10;Eg:(BLR-100000001)"
        rows="4"><?= htmlspecialchars($prefill) ?></textarea>
    </div>

    <!-- Search button -->
    <button class="tp-search-btn" id="searchBtn" onclick="doSearch()">
      <i class="fa-solid fa-magnifying-glass"></i> Search now!
    </button>

    <!-- Error message -->
    <div class="tp-error" id="tp-error" style="display:none"></div>

  </div>

</main>

<!-- ══════════════════ RESULTS SECTION ══════════════════ -->
<section class="tp-results" id="tp-results" style="display:none">
  <div class="tp-results-inner" id="tp-results-inner"></div>
</section>

<!-- ══════════════════ FOOTER NOTE ══════════════════ -->
<div class="tp-footer-note">
  <i class="fa-solid fa-shield-halved"></i>
  All tracking data is encrypted and only accessible by the account holder.
  &nbsp;|&nbsp;
  <a href="contact.html">Contact Support</a>
  &nbsp;|&nbsp;
  <a href="index.html">BlueRoute Home</a>
</div>

<script>
/* ══════════════════════════════════════════
   Track & Trace — Frontend JS
══════════════════════════════════════════ */

// Radio label styling
document.querySelectorAll('.tp-radio input').forEach(radio => {
  radio.addEventListener('change', () => {
    document.querySelectorAll('.tp-radio').forEach(l => l.classList.remove('checked'));
    radio.closest('.tp-radio').classList.add('checked');
  });
});

// Auto-search if prefilled from URL
<?php if (!empty($prefill)): ?>
window.addEventListener('DOMContentLoaded', () => doSearch());
<?php endif; ?>

// Allow Enter key in textarea (Ctrl+Enter or just Enter when no shift)
document.getElementById('trackingInput').addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); doSearch(); }
});

async function doSearch() {
  const rawInput = document.getElementById('trackingInput').value.trim();
  const type     = document.querySelector('input[name="track_type"]:checked')?.value || 'shipments';
  const errEl    = document.getElementById('tp-error');
  const resEl    = document.getElementById('tp-results');

  // Support multiple tracking numbers (one per line)
  const refs = rawInput.split(/[\n,]+/).map(r => r.trim()).filter(Boolean);

  if (!refs.length) {
    showError('Please enter a tracking number.');
    return;
  }

  errEl.style.display = 'none';
  resEl.style.display = 'none';

  const btn = document.getElementById('searchBtn');
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Searching…';
  btn.disabled = true;

  try {
    // Fetch all refs in parallel
    const results = await Promise.all(refs.map(ref =>
      fetch(`api/track.php?ref=${encodeURIComponent(ref)}&type=${encodeURIComponent(type)}`)
        .then(r => r.json())
    ));

    const cards = results.map(res => {
      if (!res.success) return renderError(res.error);
      return res.data.type === 'vault' ? renderVaultCard(res.data) : renderShipmentCard(res.data);
    }).join('');

    document.getElementById('tp-results-inner').innerHTML = cards;
    resEl.style.display = 'block';
    resEl.scrollIntoView({ behavior: 'smooth', block: 'start' });

  } catch(err) {
    showError('Connection error. Please try again.');
  } finally {
    btn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Search now!';
    btn.disabled = false;
  }
}

function showError(msg) {
  const el = document.getElementById('tp-error');
  el.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> ${msg}`;
  el.style.display = 'flex';
}

function renderError(msg) {
  return `<div class="tp-card tp-card-error">
    <div class="tpc-error-icon"><i class="fa-solid fa-circle-xmark"></i></div>
    <p>${msg}</p>
    <p style="font-size:.82rem;color:#9ca3af;margin-top:4px">Double-check the reference number or <a href="contact.html" style="color:#b8942a">contact support</a>.</p>
  </div>`;
}

/* ── Shipment result card ── */
function renderShipmentCard(s) {
  const statusClasses = {
    transit:    ['status-transit',    'fa-truck',         'In Transit'],
    delivered:  ['status-delivered',  'fa-circle-check',  'Delivered'],
    processing: ['status-processing', 'fa-gear',          'Processing'],
    pending:    ['status-pending',    'fa-clock',         'Pending'],
    cancelled:  ['status-cancelled',  'fa-xmark',         'Cancelled'],
  };
  const [cls, ico, label] = statusClasses[s.status] || ['status-pending','fa-circle',s.status];

  // Progress bar: count done events / total
  const total   = s.events?.length || 0;
  const done    = s.events?.filter(e => e.is_done == 1).length || 0;
  const pct     = total ? Math.round((done / total) * 100) : 0;

  // Timeline HTML
  const tlHtml = (s.events || []).map(ev => {
    let cls2 = '';
    if (ev.is_done == 1)   cls2 = 'tl-done';
    if (ev.is_active == 1) cls2 = 'tl-active';
    const timeStr = ev.event_time && !ev.event_time.startsWith('0000')
      ? new Date(ev.event_time).toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})
      : 'Pending';
    return `<div class="tl-item ${cls2}">
      <div class="tl-dot"></div>
      <div class="tl-body">
        <div class="tl-event">${ev.event_title}</div>
        <div class="tl-meta"><i class="fa-solid fa-clock"></i> ${timeStr}</div>
        ${ev.location ? `<div class="tl-meta"><i class="fa-solid fa-location-dot"></i> ${ev.location}</div>` : ''}
      </div>
    </div>`;
  }).join('');

  const etaStr = s.eta
    ? new Date(s.eta).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})
    : 'TBC';

  const daysLeft = parseInt(s.days_remaining);
  const etaNote  = s.status === 'delivered' ? '' :
    daysLeft > 0 ? `<span class="eta-note"><i class="fa-regular fa-calendar"></i> ${daysLeft} day${daysLeft!==1?'s':''} remaining</span>` :
    daysLeft === 0 ? `<span class="eta-note eta-today"><i class="fa-solid fa-star"></i> Expected today!</span>` :
    `<span class="eta-note eta-late"><i class="fa-solid fa-triangle-exclamation"></i> Overdue by ${Math.abs(daysLeft)} day${Math.abs(daysLeft)!==1?'s':''}</span>`;

  return `
  <div class="tp-card">
    <div class="tpc-header">
      <div class="tpc-header-left">
        <div class="tpc-ref"><i class="fa-solid fa-barcode"></i> ${s.reference}</div>
        <div class="tpc-client"><i class="fa-solid fa-user"></i> ${s.client_name}</div>
      </div>
      <span class="tpc-status ${cls}"><i class="fa-solid ${ico}"></i> ${label}</span>
    </div>

    <div class="tpc-meta-grid">
      <div class="tpc-meta-item">
        <div class="tpc-meta-label">Origin</div>
        <div class="tpc-meta-val"><i class="fa-solid fa-location-dot"></i> ${s.origin}</div>
      </div>
      <div class="tpc-meta-item">
        <div class="tpc-meta-label">Destination</div>
        <div class="tpc-meta-val"><i class="fa-solid fa-location-dot" style="color:#b8942a"></i> ${s.destination}</div>
      </div>
      <div class="tpc-meta-item">
        <div class="tpc-meta-label">Service</div>
        <div class="tpc-meta-val"><i class="fa-solid fa-truck-fast"></i> ${s.service_type}</div>
      </div>
      <div class="tpc-meta-item">
        <div class="tpc-meta-label">Est. Delivery</div>
        <div class="tpc-meta-val">${etaStr} ${etaNote}</div>
      </div>
      <div class="tpc-meta-item">
        <div class="tpc-meta-label">Current Location</div>
        <div class="tpc-meta-val"><i class="fa-solid fa-map-pin"></i> ${s.current_location || '—'}</div>
      </div>
      ${s.description ? `<div class="tpc-meta-item">
        <div class="tpc-meta-label">Description</div>
        <div class="tpc-meta-val"><i class="fa-solid fa-box"></i> ${s.description}</div>
      </div>` : ''}
    </div>

    <!-- Progress bar -->
    <div class="tpc-progress-wrap">
      <div class="tpc-progress-label">
        <span>Shipment Progress</span>
        <span>${pct}%</span>
      </div>
      <div class="tpc-progress-bar">
        <div class="tpc-progress-fill" style="width:${pct}%"></div>
      </div>
    </div>

    <!-- Timeline -->
    <div class="tpc-timeline-title"><i class="fa-solid fa-route"></i> Tracking History</div>
    <div class="tpc-timeline">
      ${tlHtml || '<div class="tl-empty">No tracking events yet.</div>'}
    </div>
  </div>`;
}

/* ── Vault result card ── */
function renderVaultCard(v) {
  const statusMap = {
    verified: ['status-delivered',  'fa-shield-halved', 'Verified'],
    review:   ['status-processing', 'fa-magnifying-glass','Under Review'],
    pending:  ['status-pending',    'fa-clock',         'Pending Audit'],
  };
  const [cls, ico, label] = statusMap[v.status] || ['status-pending','fa-circle',v.status];

  const fmtD = d => d ? new Date(d).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) : '—';

  return `
  <div class="tp-card">
    <div class="tpc-header">
      <div class="tpc-header-left">
        <div class="tpc-ref"><i class="fa-solid fa-vault"></i> ${v.reference}</div>
        <div class="tpc-client"><i class="fa-solid fa-user"></i> ${v.client_name}</div>
      </div>
      <span class="tpc-status ${cls}"><i class="fa-solid ${ico}"></i> ${label}</span>
    </div>
    <div class="tpc-meta-grid">
      <div class="tpc-meta-item">
        <div class="tpc-meta-label">Facility</div>
        <div class="tpc-meta-val"><i class="fa-solid fa-building"></i> ${v.facility}</div>
      </div>
      <div class="tpc-meta-item">
        <div class="tpc-meta-label">Contents</div>
        <div class="tpc-meta-val"><i class="fa-solid fa-box"></i> ${v.contents || '—'}</div>
      </div>
      <div class="tpc-meta-item">
        <div class="tpc-meta-label">Last Audit</div>
        <div class="tpc-meta-val"><i class="fa-solid fa-calendar-check"></i> ${fmtD(v.last_audit)}</div>
      </div>
      <div class="tpc-meta-item">
        <div class="tpc-meta-label">Next Audit</div>
        <div class="tpc-meta-val"><i class="fa-regular fa-calendar"></i> ${fmtD(v.next_audit)}</div>
      </div>
    </div>
    ${v.notes ? `<div class="tpc-notes"><i class="fa-solid fa-note-sticky"></i> ${v.notes}</div>` : ''}
  </div>`;
}
</script>
</body>
</html>