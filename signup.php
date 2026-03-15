<?php
// ══════════════════════════════════════════
//  BlueRoute — Sign Up Page
//  signup.php
// ══════════════════════════════════════════

require_once __DIR__ . '/includes/security.php';

// Already logged in? redirect
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up | BlueRoute Security &amp; Shipping</title>
  <meta name="robots" content="noindex" />

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/signup.css" />
</head>
<body>

<div class="su-wrap">

  <!-- ══════════ LEFT: FORM ══════════ -->
  <div class="su-form-panel">

    <div class="su-header">
      <h1>Sign up now!</h1>
      <p>Let's set up your account in just a couple of steps.</p>
    </div>

    <!-- Alert box (shown on error/success) -->
    <div class="su-alert" id="su-alert" role="alert">
      <i class="fa-solid fa-circle-info"></i>
      <span id="su-alert-msg"></span>
    </div>

    <!-- ── FORM ── -->
    <form class="su-form" id="signupForm" novalidate autocomplete="off">

      <!-- Hidden CSRF -->
      <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($token) ?>" />

      <!-- Name row -->
      <div class="form-row-2">
        <div class="form-group">
          <label for="first_name">Name <span class="req">*</span></label>
          <div class="input-wrap">
            <i class="input-icon fa-solid fa-user"></i>
            <input type="text" id="first_name" name="first_name" placeholder="Full Name"
                   autocomplete="given-name" maxlength="80" />
          </div>
          <span class="field-error" id="err-first_name"><i class="fa-solid fa-circle-exclamation"></i> <span></span></span>
        </div>
        <div class="form-group">
          <label for="last_name">Last Name <span class="req">*</span></label>
          <div class="input-wrap">
            <i class="input-icon fa-solid fa-user"></i>
            <input type="text" id="last_name" name="last_name" placeholder="Full Last Name"
                   autocomplete="family-name" maxlength="80" />
          </div>
          <span class="field-error" id="err-last_name"><i class="fa-solid fa-circle-exclamation"></i> <span></span></span>
        </div>
      </div>

      <!-- Email + Phone -->
      <div class="form-row-2">
        <div class="form-group">
          <label for="email">Email <span class="req">*</span></label>
          <div class="input-wrap">
            <i class="input-icon fa-solid fa-envelope"></i>
            <input type="email" id="email" name="email" placeholder="Email"
                   autocomplete="email" maxlength="180" />
          </div>
          <span class="field-error" id="err-email"><i class="fa-solid fa-circle-exclamation"></i> <span></span></span>
        </div>
        <div class="form-group">
          <label for="phone">Phone <span class="req">*</span></label>
          <div class="phone-row">
            <select id="phone_code" title="Country code">
              <option value="+44">🇬🇧 +44</option>
              <option value="+1">🇺🇸 +1</option>
              <option value="+971">🇦🇪 +971</option>
              <option value="+49">🇩🇪 +49</option>
              <option value="+65">🇸🇬 +65</option>
              <option value="+234">🇳🇬 +234</option>
              <option value="+27">🇿🇦 +27</option>
              <option value="+91">🇮🇳 +91</option>
              <option value="+86">🇨🇳 +86</option>
              <option value="+33">🇫🇷 +33</option>
            </select>
            <input type="tel" id="phone" name="phone" placeholder="Phone number"
                   autocomplete="tel" maxlength="20" />
          </div>
          <span class="field-error" id="err-phone"><i class="fa-solid fa-circle-exclamation"></i> <span></span></span>
        </div>
      </div>

      <!-- Country -->
      <div class="form-row-1 form-group">
        <label for="country">Country <span class="req">*</span></label>
        <div class="input-wrap">
          <i class="input-icon fa-solid fa-globe"></i>
          <select id="country" name="country">
            <option value="">Search Country</option>
            <option value="United Kingdom">🇬🇧 United Kingdom</option>
            <option value="United States">🇺🇸 United States</option>
            <option value="United Arab Emirates">🇦🇪 United Arab Emirates</option>
            <option value="Germany">🇩🇪 Germany</option>
            <option value="Singapore">🇸🇬 Singapore</option>
            <option value="Nigeria">🇳🇬 Nigeria</option>
            <option value="South Africa">🇿🇦 South Africa</option>
            <option value="Ghana">🇬🇭 Ghana</option>
            <option value="India">🇮🇳 India</option>
            <option value="China">🇨🇳 China</option>
            <option value="France">🇫🇷 France</option>
            <option value="Canada">🇨🇦 Canada</option>
            <option value="Australia">🇦🇺 Australia</option>
            <option value="Brazil">🇧🇷 Brazil</option>
            <option value="Japan">🇯🇵 Japan</option>
            <option value="Other">🌍 Other</option>
          </select>
        </div>
        <span class="field-error" id="err-country"><i class="fa-solid fa-circle-exclamation"></i> <span></span></span>
      </div>

      <!-- State / City / Zip -->
      <div class="location-row">
        <div class="form-group">
          <label for="state">State <span class="req">*</span></label>
          <div class="input-wrap">
            <i class="input-icon fa-solid fa-map"></i>
            <input type="text" id="state" name="state" placeholder="Search State" maxlength="80" />
          </div>
        </div>
        <div class="form-group">
          <label for="city">City <span class="req">*</span></label>
          <div class="input-wrap">
            <i class="input-icon fa-solid fa-city"></i>
            <input type="text" id="city" name="city" placeholder="Search City" maxlength="80" />
          </div>
        </div>
        <div class="form-group">
          <label for="zip_code">Zip Code <span class="req">*</span></label>
          <div class="input-wrap">
            <i class="input-icon fa-regular fa-square"></i>
            <input type="text" id="zip_code" name="zip_code" placeholder="Zip Code" maxlength="20" />
          </div>
        </div>
      </div>

      <!-- Address -->
      <div class="form-row-1 form-group">
        <label for="address">Address <span class="req">*</span></label>
        <div class="input-wrap">
          <i class="input-icon fa-solid fa-location-dot"></i>
          <input type="text" id="address" name="address" placeholder="Street address" maxlength="255" />
        </div>
      </div>

      <!-- Username -->
      <div class="form-row-1 form-group">
        <label for="username">Username <span class="req">*</span></label>
        <div class="input-wrap">
          <i class="input-icon fa-solid fa-at"></i>
          <input type="text" id="username" name="username" placeholder="Username"
                 autocomplete="username" maxlength="30" />
        </div>
        <span class="field-error" id="err-username"><i class="fa-solid fa-circle-exclamation"></i> <span></span></span>
      </div>

      <!-- Password + Confirm -->
      <div class="form-row-2">
        <div class="form-group">
          <label for="password">Password <span class="req">*</span></label>
          <div class="input-wrap">
            <i class="input-icon fa-solid fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="Password"
                   autocomplete="new-password" />
            <button type="button" class="pw-toggle" id="pw-toggle-1" aria-label="Show password">
              <i class="fa-regular fa-eye"></i>
            </button>
          </div>
          <!-- Strength bar -->
          <div class="pw-strength" id="pw-strength" style="display:none">
            <div class="pw-strength-bar"><div class="pw-strength-fill" id="pw-fill"></div></div>
            <div class="pw-strength-label" id="pw-label"></div>
          </div>
          <span class="field-error" id="err-password"><i class="fa-solid fa-circle-exclamation"></i> <span></span></span>
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm Password <span class="req">*</span></label>
          <div class="input-wrap">
            <i class="input-icon fa-solid fa-lock"></i>
            <input type="password" id="confirm_password" name="confirm_password"
                   placeholder="Password" autocomplete="new-password" />
            <button type="button" class="pw-toggle" id="pw-toggle-2" aria-label="Show password">
              <i class="fa-regular fa-eye"></i>
            </button>
          </div>
          <span class="field-error" id="err-confirm_password"><i class="fa-solid fa-circle-exclamation"></i> <span></span></span>
        </div>
      </div>

      <!-- Terms -->
      <div class="terms-row">
        <input type="checkbox" id="terms" name="terms" />
        <label for="terms">
          You agree to our <a href="#" target="_blank">terms and conditions</a>
          and <a href="#" target="_blank">privacy policy</a>.
        </label>
      </div>
      <span class="field-error" id="err-terms" style="margin-top:-10px;margin-bottom:12px">
        <i class="fa-solid fa-circle-exclamation"></i> <span>You must accept the terms.</span>
      </span>

      <!-- Submit -->
      <button type="submit" class="su-submit" id="su-submit">
        SIGN UP FREE
      </button>

    </form>

    <!-- Success screen (shown after registration) -->
    <div class="su-success-screen" id="su-success">
      <div class="su-success-icon"><i class="fa-solid fa-circle-check"></i></div>
      <h2>Account Created!</h2>
      <p>Welcome to BlueRoute. Your account has been created and is ready to use.</p>
      <a href="login.php" class="su-success-btn">Go to Login</a>
    </div>

    <div class="su-login-link">
      Already have an account? <a href="login.html">Login</a>
    </div>

  </div><!-- /su-form-panel -->

  <!-- ══════════ RIGHT: IMAGE ══════════ -->
  <div class="su-image-panel">

    <!-- Demo banner -->
    <div class="su-demo-banner" id="demo-banner">
      <span><i class="fa-solid fa-circle-info"></i> Hello! In this demo, choose the country that corresponds to the configured price list, so you can fill out the shipping and pick-up form.</span>
      <button class="su-demo-close" onclick="document.getElementById('demo-banner').style.display='none'">&times;</button>
    </div>

    <div class="su-image-content">
      <div class="su-logo-icon">BLUE<br>ROUTE</div>
      <h2>Secure Global<br>Logistics</h2>
      <p>Join thousands of clients who trust BlueRoute to move and store their most valuable assets worldwide.</p>
      <div class="su-features">
        <div class="su-feature">
          <div class="su-feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
          <span>256-bit encrypted shipment tracking</span>
        </div>
        <div class="su-feature">
          <div class="su-feature-icon"><i class="fa-solid fa-vault"></i></div>
          <span>Ultra-secure vault storage across 4 countries</span>
        </div>
        <div class="su-feature">
          <div class="su-feature-icon"><i class="fa-solid fa-truck-fast"></i></div>
          <span>Real-time tracking for every shipment</span>
        </div>
        <div class="su-feature">
          <div class="su-feature-icon"><i class="fa-solid fa-headset"></i></div>
          <span>24/7 dedicated client support</span>
        </div>
      </div>
    </div>

  </div><!-- /su-image-panel -->

</div><!-- /su-wrap -->

<script>
/* ══════════════════════════════════════════
   BlueRoute Sign Up — Client-side JS
══════════════════════════════════════════ */

const form    = document.getElementById('signupForm');
const alertEl = document.getElementById('su-alert');
const alertMsg= document.getElementById('su-alert-msg');
const submitBtn = document.getElementById('su-submit');

/* ── Password toggle ── */
function setupPwToggle(btnId, inputId) {
  document.getElementById(btnId).addEventListener('click', () => {
    const inp = document.getElementById(inputId);
    const icon = document.querySelector(`#${btnId} i`);
    if (inp.type === 'password') {
      inp.type = 'text';
      icon.className = 'fa-regular fa-eye-slash';
    } else {
      inp.type = 'password';
      icon.className = 'fa-regular fa-eye';
    }
  });
}
setupPwToggle('pw-toggle-1', 'password');
setupPwToggle('pw-toggle-2', 'confirm_password');

/* ── Password strength ── */
document.getElementById('password').addEventListener('input', function() {
  const val = this.value;
  const wrap = document.getElementById('pw-strength');
  const fill = document.getElementById('pw-fill');
  const label = document.getElementById('pw-label');

  if (!val) { wrap.style.display = 'none'; return; }
  wrap.style.display = 'block';

  let score = 0;
  if (val.length >= 8)              score++;
  if (/[A-Z]/.test(val))            score++;
  if (/[a-z]/.test(val))            score++;
  if (/[0-9]/.test(val))            score++;
  if (/[\W_]/.test(val))            score++;

  const levels = [
    { pct:'20%', color:'#ef4444', text:'Very Weak' },
    { pct:'40%', color:'#f97316', text:'Weak' },
    { pct:'60%', color:'#f59e0b', text:'Fair' },
    { pct:'80%', color:'#84cc16', text:'Good' },
    { pct:'100%',color:'#10b981', text:'Strong' },
  ];
  const l = levels[score - 1] || levels[0];
  fill.style.width      = l.pct;
  fill.style.background = l.color;
  label.textContent     = l.text;
  label.style.color     = l.color;
});

/* ── Real-time confirm password match ── */
document.getElementById('confirm_password').addEventListener('input', function() {
  const pw = document.getElementById('password').value;
  if (this.value && this.value !== pw) {
    showFieldError('confirm_password', 'Passwords do not match.');
  } else {
    clearFieldError('confirm_password');
    if (this.value) this.closest('.input-wrap').querySelector('input').classList.add('valid');
  }
});

/* ── Real-time email check ── */
document.getElementById('email').addEventListener('blur', function() {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (this.value && !re.test(this.value)) {
    showFieldError('email', 'Please enter a valid email address.');
  } else {
    clearFieldError('email');
  }
});

/* ── Username: only allow valid chars ── */
document.getElementById('username').addEventListener('input', function() {
  this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '').toLowerCase();
});

/* ── Field error helpers ── */
function showFieldError(field, msg) {
  const el = document.getElementById(`err-${field}`);
  if (!el) return;
  const span = el.querySelector('span');
  if (span) span.textContent = msg;
  el.classList.add('visible');
  const input = document.getElementById(field);
  if (input) { input.classList.add('error'); input.classList.remove('valid'); }
}

function clearFieldError(field) {
  const el = document.getElementById(`err-${field}`);
  if (el) el.classList.remove('visible');
  const input = document.getElementById(field);
  if (input) input.classList.remove('error');
}

function showAlert(msg, type = 'error') {
  alertEl.className = `su-alert visible ${type}`;
  alertMsg.innerHTML = msg;
  alertEl.scrollIntoView({ behavior:'smooth', block:'nearest' });
}

function clearAlert() {
  alertEl.className = 'su-alert';
}

/* ── Form submit ── */
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  clearAlert();

  // Clear all errors
  document.querySelectorAll('.field-error').forEach(el => el.classList.remove('visible'));
  document.querySelectorAll('input, select').forEach(el => el.classList.remove('error'));

  // Build payload
  const phoneCode = document.getElementById('phone_code').value;
  const phoneNum  = document.getElementById('phone').value.trim();

  const payload = {
    csrf_token:       document.getElementById('csrf_token').value,
    first_name:       document.getElementById('first_name').value.trim(),
    last_name:        document.getElementById('last_name').value.trim(),
    email:            document.getElementById('email').value.trim(),
    phone:            phoneCode + ' ' + phoneNum,
    country:          document.getElementById('country').value,
    state:            document.getElementById('state').value.trim(),
    city:             document.getElementById('city').value.trim(),
    zip_code:         document.getElementById('zip_code').value.trim(),
    address:          document.getElementById('address').value.trim(),
    username:         document.getElementById('username').value.trim(),
    password:         document.getElementById('password').value,
    confirm_password: document.getElementById('confirm_password').value,
    terms:            document.getElementById('terms').checked,
  };

  // Basic client-side checks
  if (!payload.first_name || !payload.last_name) { showAlert('Please enter your full name.'); return; }
  if (!payload.email)    { showAlert('Email is required.'); return; }
  if (!payload.country)  { showAlert('Please select your country.'); return; }
  if (!payload.username) { showAlert('Username is required.'); return; }
  if (!payload.password) { showAlert('Password is required.'); return; }
  if (!payload.terms)    { showFieldError('terms',''); showAlert('You must accept the terms and conditions.'); return; }

  // Disable button & show loader
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating Account…';

  try {
    const res = await fetch('api/signup.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload),
    });

    const data = await res.json();

    if (data.success) {
      // Hide form, show success screen
      form.style.display = 'none';
      alertEl.style.display = 'none';
      document.getElementById('su-success').classList.add('visible');
    } else {
      // Show field-level errors if any
      if (data.errors) {
        Object.entries(data.errors).forEach(([field, msg]) => showFieldError(field, msg));
      }
      showAlert(data.error || 'Something went wrong. Please try again.');
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'SIGN UP FREE';
    }

  } catch (err) {
    showAlert('Connection error. Please check your internet connection and try again.');
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'SIGN UP FREE';
  }
});
</script>
</body>
</html>