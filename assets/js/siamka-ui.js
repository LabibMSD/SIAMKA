/**
 * 🎨 SIAMKA Modern Design System - JavaScript
 * Interaksi, animasi, dan fungsi UI
 */

document.addEventListener('DOMContentLoaded', function() {
  initializeSidebarToggle();
  initializeTooltips();
  initializeForms();
  initializeAnimations();
  initializeNavigation();
});

/**
 * ============================================
 * 1. SIDEBAR TOGGLE - Mobile Navigation
 * ============================================
 */
function initializeSidebarToggle() {
  const toggleBtn = document.querySelector('.sidebar-toggle');
  const sidebar = document.querySelector('.sidebar-main');
  const overlay = document.querySelector('.sidebar-overlay');

  if (!toggleBtn || !sidebar) return;

  // Toggle button click
  toggleBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    sidebar.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active');
  });

  // Close when clicking outside
  document.addEventListener('click', (e) => {
    if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
      sidebar.classList.remove('active');
      if (overlay) overlay.classList.remove('active');
    }
  });

  // Close when nav link clicked
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
      sidebar.classList.remove('active');
      if (overlay) overlay.classList.remove('active');
    });
  });

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      sidebar.classList.remove('active');
      if (overlay) overlay.classList.remove('active');
    }
  });
}

/**
 * ============================================
 * 2. TOOLTIPS & POPOVERS
 * ============================================
 */
function initializeTooltips() {
  // Initialize Bootstrap tooltips if available
  if (typeof bootstrap !== 'undefined') {
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipElements.forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  // Custom tooltip for collapsed sidebar
  const sidebarItems = document.querySelectorAll('.sidebar-main.collapsed .nav-link');
  sidebarItems.forEach(item => {
    const text = item.textContent.trim();
    if (text) {
      item.setAttribute('data-tooltip', text);
    }
  });
}

/**
 * ============================================
 * 3. FORM HANDLING & VALIDATION
 * ============================================
 */
function initializeForms() {
  // Real-time form validation
  const forms = document.querySelectorAll('form[data-validate="true"]');
  forms.forEach(form => {
    form.addEventListener('submit', (e) => {
      if (!validateForm(form)) {
        e.preventDefault();
      }
    });

    // Real-time field validation
    form.querySelectorAll('input, textarea, select').forEach(field => {
      field.addEventListener('blur', () => {
        validateField(field);
      });

      field.addEventListener('change', () => {
        validateField(field);
      });
    });
  });

  // Form field focus effects
  document.querySelectorAll('input, textarea, select').forEach(field => {
    field.addEventListener('focus', (e) => {
      e.target.parentElement.classList.add('focused');
    });

    field.addEventListener('blur', (e) => {
      if (!e.target.value) {
        e.target.parentElement.classList.remove('focused');
      }
    });
  });
}

/**
 * Validate individual field
 */
function validateField(field) {
  const value = field.value.trim();
  let isValid = true;
  let errorMessage = '';

  // Remove existing error
  const errorDiv = field.parentElement.querySelector('.error-message');
  if (errorDiv) errorDiv.remove();

  // Required validation
  if (field.hasAttribute('required') && !value) {
    isValid = false;
    errorMessage = 'Field is required';
  }

  // Email validation
  if (field.type === 'email' && value && !isEmailValid(value)) {
    isValid = false;
    errorMessage = 'Invalid email address';
  }

  // Password validation
  if (field.type === 'password' && field.hasAttribute('data-min-length')) {
    const minLength = parseInt(field.getAttribute('data-min-length'));
    if (value && value.length < minLength) {
      isValid = false;
      errorMessage = `Password must be at least ${minLength} characters`;
    }
  }

  // Show error
  if (!isValid && errorMessage) {
    const error = document.createElement('small');
    error.className = 'error-message text-danger';
    error.textContent = errorMessage;
    field.parentElement.appendChild(error);
    field.classList.add('is-invalid');
  } else {
    field.classList.remove('is-invalid');
    if (value) field.classList.add('is-valid');
  }

  return isValid;
}

/**
 * Validate entire form
 */
function validateForm(form) {
  let isValid = true;
  form.querySelectorAll('input, textarea, select').forEach(field => {
    if (!validateField(field)) {
      isValid = false;
    }
  });
  return isValid;
}

/**
 * Email validation helper
 */
function isEmailValid(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

/**
 * ============================================
 * 4. ANIMATIONS & TRANSITIONS
 * ============================================
 */
function initializeAnimations() {
  // Observe elements for fade-in animation
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate-fade-in');
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);

  // Observe cards and other elements
  document.querySelectorAll('.card, .stat-card, .list-item').forEach(el => {
    observer.observe(el);
  });

  // Add entrance animation to page
  const mainContent = document.querySelector('.main-content');
  if (mainContent) {
    mainContent.style.animation = 'fadeIn 300ms ease-out';
  }
}

/**
 * ============================================
 * 5. NAVIGATION & ACTIVE STATES
 * ============================================
 */
function initializeNavigation() {
  // Mark current page as active
  const currentPage = window.location.pathname;
  document.querySelectorAll('.nav-link').forEach(link => {
    const href = link.getAttribute('href');
    if (href && currentPage.includes(href)) {
      link.classList.add('active');
      // Expand parent section if exists
      const parent = link.closest('.sidebar-nav-section');
      if (parent) parent.classList.add('expanded');
    }
  });

  // Smooth page transitions
  document.querySelectorAll('a:not([target="_blank"])').forEach(link => {
    link.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
        const content = document.querySelector('.main-content');
        if (content) {
          content.style.opacity = '0.7';
          content.style.pointerEvents = 'none';
        }
      }
    });
  });
}

/**
 * ============================================
 * 6. ALERT & NOTIFICATION HELPERS
 * ============================================
 */
class Notification {
  static success(message, duration = 5000) {
    this.show('alert-success', message, duration, 'check-circle');
  }

  static error(message, duration = 5000) {
    this.show('alert-danger', message, duration, 'exclamation-circle');
  }

  static warning(message, duration = 5000) {
    this.show('alert-warning', message, duration, 'triangle-exclamation');
  }

  static info(message, duration = 5000) {
    this.show('alert-info', message, duration, 'info-circle');
  }

  static show(className, message, duration, icon = 'info-circle') {
    const container = document.querySelector('.alerts-container') || 
                      document.querySelector('.main-content');
    
    if (!container) return;

    const alert = document.createElement('div');
    alert.className = `alert ${className} animate-slide-right`;
    alert.innerHTML = `
      <i class="fa-solid fa-${icon}"></i>
      <div>${message}</div>
      <button type="button" class="btn-close" aria-label="Close"></button>
    `;

    container.insertBefore(alert, container.firstChild);

    // Auto remove
    const timeout = setTimeout(() => {
      alert.style.animation = 'slideOutRight 300ms ease-in-out';
      setTimeout(() => alert.remove(), 300);
    }, duration);

    // Manual close
    alert.querySelector('.btn-close').addEventListener('click', () => {
      clearTimeout(timeout);
      alert.style.animation = 'slideOutRight 300ms ease-in-out';
      setTimeout(() => alert.remove(), 300);
    });
  }
}

/**
 * ============================================
 * 7. UTILITY FUNCTIONS
 * ============================================
 */

/**
 * Format currency
 */
function formatCurrency(amount, currency = 'IDR') {
  const formatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: currency,
  });
  return formatter.format(amount);
}

/**
 * Format date
 */
function formatDate(date, format = 'DD/MM/YYYY') {
  const d = new Date(date);
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();

  if (format === 'DD/MM/YYYY') return `${day}/${month}/${year}`;
  if (format === 'YYYY-MM-DD') return `${year}-${month}-${day}`;
  return d.toLocaleDateString('id-ID');
}

/**
 * Debounce function (for search, etc)
 */
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/**
 * Check if element is in viewport
 */
function isInViewport(el) {
  const rect = el.getBoundingClientRect();
  return (
    rect.top >= 0 &&
    rect.left >= 0 &&
    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
  );
}

/**
 * Smooth scroll to element
 */
function smoothScrollTo(element) {
  element.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/**
 * ============================================
 * 8. EXPORT FOR GLOBAL USE
 * ============================================
 */
window.SiamkaUI = {
  Notification,
  formatCurrency,
  formatDate,
  debounce,
  isInViewport,
  smoothScrollTo,
  validateForm,
  validateField,
};

/**
 * ============================================
 * 9. KEYBOARD SHORTCUTS
 * ============================================
 */
document.addEventListener('keydown', (e) => {
  // Alt + S: Toggle Sidebar (mobile)
  if (e.altKey && e.key === 's') {
    e.preventDefault();
    const sidebar = document.querySelector('.sidebar-main');
    if (sidebar) sidebar.classList.toggle('active');
  }

  // Alt + F: Focus Search
  if (e.altKey && e.key === 'f') {
    e.preventDefault();
    const search = document.querySelector('.search-box input');
    if (search) search.focus();
  }
});

/**
 * ============================================
 * 10. PERFORMANCE MONITORING
 * ============================================
 */
if (window.performance) {
  window.addEventListener('load', () => {
    const perfData = window.performance.timing;
    const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;

    // Log only in development (check for localhost or development indicators)
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
      console.log(`Page load time: ${pageLoadTime}ms`);
    }
  });
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
  module.exports = window.SiamkaUI;
}
