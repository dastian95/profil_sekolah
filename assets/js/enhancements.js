/**
 * ENHANCEMENTS: Dark Mode Toggle, Enhanced Form Validation
 */

// ============================================================
// DARK MODE SYSTEM
// ============================================================

class DarkModeManager {
  constructor() {
    this.themeKey = 'theme-preference';
    this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    this.init();
  }

  init() {
    // Load saved preference or use system preference
    const savedTheme = localStorage.getItem(this.themeKey);
    
    if (savedTheme) {
      this.setTheme(savedTheme);
    } else {
      const systemTheme = this.mediaQuery.matches ? 'dark' : 'light';
      this.setTheme(systemTheme);
    }

    // Listen for system theme changes
    this.mediaQuery.addEventListener('change', (e) => {
      const newTheme = e.matches ? 'dark' : 'light';
      this.setTheme(newTheme);
    });

    // Add dark mode toggle button
    this.createToggleButton();
  }

  setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(this.themeKey, theme);
    this.updateToggleButtonIcon(theme);
  }

  getTheme() {
    return document.documentElement.getAttribute('data-theme') || 'light';
  }

  toggleTheme() {
    const currentTheme = this.getTheme();
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    this.setTheme(newTheme);
  }

  createToggleButton() {
    const nav = document.querySelector('nav.navmenu');
    if (!nav) return;

    const toggleButton = document.createElement('button');
    toggleButton.className = 'dark-mode-toggle';
    toggleButton.type = 'button';
    toggleButton.setAttribute('aria-label', 'Toggle dark mode');
    toggleButton.title = 'Toggle Theme';
    
    this.updateToggleButtonIcon(this.getTheme());
    toggleButton.addEventListener('click', () => this.toggleTheme());

    // Add to navbar
    const navbarEnd = nav.querySelector('ul');
    if (navbarEnd) {
      const li = document.createElement('li');
      li.appendChild(toggleButton);
      navbarEnd.appendChild(li);
    }
  }

  updateToggleButtonIcon(theme) {
    const button = document.querySelector('.dark-mode-toggle');
    if (!button) return;

    if (theme === 'dark') {
      button.innerHTML = '<i class="bi bi-sun-fill"></i>';
    } else {
      button.innerHTML = '<i class="bi bi-moon-stars"></i>';
    }
  }
}

// Initialize dark mode on page load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    new DarkModeManager();
  });
} else {
  new DarkModeManager();
}



// ============================================================
// ENHANCED FORM VALIDATION SYSTEM
// ============================================================

class FormValidator {
  constructor(formSelector) {
    this.form = document.querySelector(formSelector);
    if (!this.form) return;
    
    this.validatedFields = new Set();
    this.init();
  }

  init() {
    this.setupInputValidation();
    this.setupPasswordStrength();
    this.setupPasswordMatching();
  }

  setupInputValidation() {
    const inputs = this.form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
      // Validate on blur
      input.addEventListener('blur', () => {
        this.validatedFields.add(input.name);
        this.validateField(input);
      });
      
      // Validate on input (for real-time feedback)
      input.addEventListener('input', () => {
        if (this.validatedFields.has(input.name)) {
          this.debounceValidation(input);
        }
      });
    });
  }

  debounceValidation(input) {
    if (input.debounceTimer) clearTimeout(input.debounceTimer);
    input.debounceTimer = setTimeout(() => {
      this.validateField(input);
    }, 300);
  }

  validateField(input) {
    input.classList.add('validated');
    
    const value = input.value.trim();
    const type = input.type;
    const name = input.name;
    let isValid = true;
    let errorMessage = '';

    // Clear previous validation state
    input.classList.remove('is-valid', 'is-invalid');

    // Check if required and empty
    if (input.hasAttribute('required') && value === '') {
      isValid = false;
      errorMessage = 'Field ini harus diisi';
    } else {
      // Type-specific validation
      switch (type) {
        case 'email':
          const emailResult = this.validateEmail(value);
          isValid = emailResult.valid;
          errorMessage = emailResult.message;
          break;
        case 'password':
          const passwordResult = this.validatePassword(value, input);
          isValid = passwordResult.valid;
          errorMessage = passwordResult.message;
          break;
        case 'text':
          if (name === 'name' && value) {
            const nameResult = this.validateName(value);
            isValid = nameResult.valid;
            errorMessage = nameResult.message;
          }
          break;
        case 'tel':
        case 'number':
          if (value && name === 'nisn') {
            const nisnResult = this.validateNISN(value);
            isValid = nisnResult.valid;
            errorMessage = nisnResult.message;
          }
          break;
        case 'checkbox':
          isValid = input.checked;
          if (!isValid) errorMessage = 'Anda harus setuju dengan syarat dan ketentuan';
          break;
        default:
          isValid = !input.hasAttribute('required') || value.length > 0;
      }
    }

    // Apply validation class
    if (isValid && value !== '') {
      input.classList.add('is-valid');
    } else if (!isValid || value === '') {
      input.classList.add('is-invalid');
    }

    this.showValidationMessage(input, isValid, errorMessage);
    return isValid;
  }

  validateEmail(email) {
    if (!email) return { valid: false, message: 'Email harus diisi' };
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return { valid: false, message: 'Format email tidak valid (contoh: user@domain.com)' };
    }
    
    // Check for common typos
    const commonDomains = ['gmial.com', 'gmai.com', 'yahooo.com', 'hotmial.com'];
    const domain = email.split('@')[1];
    if (commonDomains.includes(domain)) {
      return { valid: false, message: `Apakah Anda maksud @${domain.replace(/o+$/, '')}?` };
    }
    
    return { valid: true, message: '✓ Email valid' };
  }

  validatePassword(password, input = null) {
    if (!password) return { valid: false, message: 'Password harus diisi' };
    
    // Password strength rules
    const rules = {
      length: password.length >= 8,
      lowercase: /[a-z]/.test(password),
      uppercase: /[A-Z]/.test(password),
      number: /[0-9]/.test(password)
    };

    if (input) {
      this.updatePasswordRequirements(input, rules);
    }

    const allValid = Object.values(rules).every(rule => rule === true);
    const message = allValid ? '✓ Password kuat' : 'Password harus memiliki: 8+ karakter, huruf besar, huruf kecil, angka';
    
    return { valid: allValid, message };
  }

  validateName(name) {
    if (!name) return { valid: false, message: 'Nama harus diisi' };
    
    // Only letters and spaces
    if (!/^[a-zA-Z\s]+$/.test(name)) {
      return { valid: false, message: 'Nama hanya boleh berisi huruf dan spasi' };
    }
    
    // Min 3 characters
    if (name.length < 3) {
      return { valid: false, message: 'Nama minimal 3 karakter' };
    }
    
    return { valid: true, message: '✓ Nama valid' };
  }

  validateNISN(nisn) {
    if (!nisn) return { valid: false, message: 'NISN harus diisi' };
    
    // NISN harus 10 digit
    if (!/^\d{10}$/.test(nisn)) {
      return { valid: false, message: 'NISN harus 10 angka' };
    }
    
    return { valid: true, message: '✓ NISN valid' };
  }

  updatePasswordRequirements(input, rules) {
    const container = input.closest('.form-group') || input.closest('.col-md-6');
    if (!container) return;

    const items = container.querySelectorAll('#password-requirements li, .requirement-list li');
    if (items.length >= 3) {
      const setStatus = (index, isValid) => {
        if (items[index]) {
          items[index].className = isValid ? 'text-success' : 'text-danger';
        }
      };

      setStatus(0, rules.length);
      setStatus(1, rules.uppercase);
      setStatus(2, rules.number);
    }
  }

  setupPasswordMatching() {
    const passwordInputs = this.form.querySelectorAll('input[type="password"]');
    if (passwordInputs.length < 2) return;

    const [passInput, verifyInput] = [passwordInputs[0], passwordInputs[1]];
    
    const checkMatch = () => {
      if (!verifyInput.value) {
        verifyInput.classList.remove('is-valid', 'is-invalid');
        return;
      }

      const match = passInput.value === verifyInput.value;
      verifyInput.classList.toggle('is-valid', match);
      verifyInput.classList.toggle('is-invalid', !match);

      const feedback = document.getElementById('password_match_feedback');
      if (feedback) {
        feedback.className = `small mt-1 ${match ? 'text-success' : 'text-danger'}`;
        feedback.textContent = match ? '✓ Password cocok' : '✗ Password tidak cocok';
      }
    };

    passInput.addEventListener('input', checkMatch);
    verifyInput.addEventListener('input', checkMatch);
  }

  showValidationMessage(input, isValid, errorMessage = '') {
    // Find feedback div (using multiple selectors for compatibility)
    let feedback = input.nextElementSibling;
    
    if (!feedback || (!feedback.classList.contains('valid-feedback') && 
                      !feedback.classList.contains('invalid-feedback') &&
                      !feedback.classList.contains('small'))) {
      // Create feedback div if it doesn't exist
      feedback = document.createElement('div');
      feedback.className = 'small mt-1';
      input.parentNode.insertBefore(feedback, input.nextSibling);
    }

    if (isValid && input.value.trim() !== '') {
      feedback.className = 'small mt-1 text-success';
      feedback.textContent = errorMessage || '✓ Valid';
    } else if (!isValid && input.value.trim() !== '') {
      feedback.className = 'small mt-1 text-danger';
      feedback.textContent = errorMessage || '✗ Field tidak valid';
    } else {
      feedback.textContent = '';
    }
  }

  setupPasswordStrength() {
    const passwordInputs = this.form.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
      input.addEventListener('input', () => {
        this.updatePasswordStrength(input);
      });
    });
  }

  updatePasswordStrength(input) {
    const password = input.value;
    let strength = 0;

    // Calculate strength
    if (password.length >= 8) strength += 25;
    if (/[a-z]/.test(password)) strength += 25;
    if (/[A-Z]/.test(password)) strength += 25;
    if (/[0-9]/.test(password)) strength += 25;

    // Find strength bar
    const container = input.closest('.form-group') || input.closest('.col-md-6');
    const strengthBar = container?.querySelector('.progress-bar, #password-strength-bar');

    if (strengthBar) {
      strengthBar.style.width = strength + '%';
      strengthBar.className = 'progress-bar';

      if (strength === 0) {
        strengthBar.style.width = '0%';
      } else if (strength <= 40) {
        strengthBar.classList.add('bg-danger');
      } else if (strength <= 80) {
        strengthBar.classList.add('bg-warning');
      } else {
        strengthBar.classList.add('bg-success');
      }
    }
  }
}

// ============================================================
// MOBILE RESPONSIVE HELPERS
// ============================================================

class MobileHelper {
  static isMobile() {
    return window.innerWidth < 768;
  }

  static setupSidebarToggle() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('#sidebarToggle');
    
    if (!sidebar || !toggleBtn) return;

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('show');
    });

    // Close sidebar when clicking outside
    document.addEventListener('click', (e) => {
      if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('show');
      }
    });
  }

  static preventIOSZoom() {
    // Prevent zoom on input focus (for mobile)
    document.addEventListener('touchstart', function(e) {
      if (e.touches.length > 1) {
        e.preventDefault();
      }
    }, { passive: false });
  }

  static setupResponsiveTable() {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
      if (!table.parentElement.classList.contains('table-responsive')) {
        const wrapper = document.createElement('div');
        wrapper.className = 'table-responsive';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
      }
    });
  }
}

// ============================================================
// INITIALIZE ON PAGE LOAD
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
  // Initialize form validator (only enhance, don't override)
  const form = document.querySelector('form.php-email-form');
  if (form) {
    new FormValidator('form.php-email-form');
  }

  // Initialize mobile helpers
  MobileHelper.setupSidebarToggle();
  MobileHelper.preventIOSZoom();
  MobileHelper.setupResponsiveTable();

  // Add touch feedback for buttons on mobile
  if (MobileHelper.isMobile()) {
    document.querySelectorAll('.btn').forEach(btn => {
      btn.addEventListener('touchstart', () => {
        btn.style.opacity = '0.8';
      });
      btn.addEventListener('touchend', () => {
        btn.style.opacity = '1';
      });
    });
  }

  // Setup CAPTCHA refresh button if exists
  const captchaImg = document.querySelector('img[src*="captcha"]');
  if (captchaImg && !captchaImg.nextElementSibling?.classList.contains('btn-outline-secondary')) {
    const refreshBtn = document.createElement('button');
    refreshBtn.type = 'button';
    refreshBtn.className = 'btn btn-sm btn-outline-secondary ms-2';
    refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh';
    refreshBtn.addEventListener('click', (e) => {
      e.preventDefault();
      captchaImg.src = 'captcha.php?' + new Date().getTime();
    });
    captchaImg.parentNode.insertBefore(refreshBtn, captchaImg.nextSibling);
  }
});

// ============================================================
// WINDOW RESIZE HANDLER
// ============================================================

let resizeTimer;
window.addEventListener('resize', () => {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(() => {
    // Handle responsive adjustments
    const sidebarToggle = document.querySelector('#sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (!MobileHelper.isMobile() && sidebar && sidebar.classList.contains('show')) {
      sidebar.classList.remove('show');
    }
  }, 250);
});

// ============================================================
// UTILITIES
// ============================================================

// Utility: Show toast notification
window.showToast = function(message, type = 'info') {
  const toastContainer = document.getElementById('toastContainer') || (() => {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
    document.body.appendChild(container);
    return container;
  })();

  const toast = document.createElement('div');
  toast.className = `alert alert-${type} alert-dismissible fade show`;
  toast.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  
  toastContainer.appendChild(toast);

  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    toast.remove();
  }, 5000);
};
