document.addEventListener('DOMContentLoaded', function() {
  const passwordField = document.getElementById('password');
  const confirmPasswordField = document.getElementById('confirmPassword');
  const addUserForm = document.getElementById('addUserForm');
  const emailField = document.getElementById('email');
  const contactNumberField = document.getElementById('contactNumber');
  initializeAutoCapitalizeFields();
  initializeEmailDuplicateCheck(addUserForm, emailField);
  initializeContactNumberDuplicateCheck(addUserForm, contactNumberField);

  // 1. Password Strength Validation (Mix of Letters and Numbers)
  passwordField.addEventListener('input', function() {
    const password = passwordField.value;
    let strengthMessage = '';

    // Criteria: Dapat may letter, dapat may number, at dapat 8+ characters
    const hasLetter = /[a-zA-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const isLongEnough = password.length >= 8;

    if (password.length > 0) {
      if (!hasLetter || !hasNumber) {
        strengthMessage = 'Password is too weak: Use a mix of letters and numbers.';
      } else if (!isLongEnough) {
        strengthMessage = 'Password is too short: Must be at least 8 characters.';
      }
    }

    // Set validity to prevent form submission if criteria are not met
    passwordField.setCustomValidity(strengthMessage);
    
    // UI Feedback using Bootstrap classes
    if (strengthMessage) {
      passwordField.classList.add('is-invalid');
      passwordField.classList.remove('is-valid');
    } else if (password.length > 0) {
      passwordField.classList.remove('is-invalid');
      passwordField.classList.add('is-valid');
    }
  });

  // 2. Confirm Password Match Logic
  confirmPasswordField.addEventListener('input', function() {
    if (passwordField.value !== confirmPasswordField.value) {
      confirmPasswordField.setCustomValidity('Passwords do not match');
      confirmPasswordField.classList.add('is-invalid');
      confirmPasswordField.classList.remove('is-valid');
    } else {
      confirmPasswordField.setCustomValidity('');
      confirmPasswordField.classList.remove('is-invalid');
      confirmPasswordField.classList.add('is-valid');
    }
  });

  initializeProfileDropdown();
  initializeProfileCropper();
});

function initializeAutoCapitalizeFields() {
  const fieldIds = ['firstName', 'middleName', 'lastName', 'suffix', 'position'];

  fieldIds.forEach(function(fieldId) {
    const input = document.getElementById(fieldId);

    if (!input) return;

    input.addEventListener('input', function() {
      const start = input.selectionStart;
      const end = input.selectionEnd;
      const transformedValue = capitalizeWords(input.value);

      if (transformedValue !== input.value) {
        input.value = transformedValue;

        if (typeof start === 'number' && typeof end === 'number') {
          input.setSelectionRange(start, end);
        }
      }
    });
  });
}

function capitalizeWords(value) {
  return value.replace(/(^|[\s\-'])[a-z]/g, function(match) {
    return match.toUpperCase();
  });
}

function initializeEmailDuplicateCheck(form, input, excludeUserId) {
  if (!form || !input) return;

  let debounceTimer = null;
  let requestSequence = 0;

  input.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    clearEmailDuplicateState(input);

    debounceTimer = setTimeout(function() {
      validateEmailUniqueness(input, excludeUserId, ++requestSequence);
    }, 350);
  });

  input.addEventListener('blur', function() {
    clearTimeout(debounceTimer);
    validateEmailUniqueness(input, excludeUserId, ++requestSequence);
  });

  form.addEventListener('submit', async function(event) {
    const isUnique = await validateEmailUniqueness(input, excludeUserId, ++requestSequence);

    if (!isUnique) {
      event.preventDefault();
      input.focus();
    }
  });
}

async function validateEmailUniqueness(input, excludeUserId, requestSequence) {
  const normalized = normalizeEmail(input.value);

  if (normalized === '' || !isValidEmailFormat(normalized)) {
    clearEmailDuplicateState(input);
    return true;
  }

  try {
    const query = new URLSearchParams({
      email: input.value
    });

    if (excludeUserId) {
      query.set('exclude_user_id', String(excludeUserId));
    }

    const response = await fetch(`../../../api/check_email.php?${query.toString()}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!response.ok) {
      throw new Error('Unable to verify email.');
    }

    const result = await response.json();

    if (requestSequence !== undefined) {
      const currentNormalized = normalizeEmail(input.value);

      if (currentNormalized !== normalized) {
        return !result.exists;
      }
    }

    if (result.exists) {
      showEmailDuplicateState(input, 'Email already exists.');
      return false;
    }

    clearEmailDuplicateState(input);
    return true;
  } catch (error) {
    clearEmailDuplicateState(input);
    return true;
  }
}

function normalizeEmail(value) {
  return (value || '').trim().toLowerCase();
}

function isValidEmailFormat(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function showEmailDuplicateState(input, message) {
  const helpText = getFieldHelpText(input);

  input.setCustomValidity(message);
  input.classList.add('is-invalid');
  input.classList.remove('is-valid');

  if (helpText) {
    helpText.innerHTML = `<span class="text-danger"><i class="fa fa-exclamation-triangle me-1"></i>${message}</span>`;
  }
}

function clearEmailDuplicateState(input) {
  const helpText = getFieldHelpText(input);

  input.setCustomValidity('');
  input.classList.remove('is-invalid');

  if (helpText) {
    helpText.innerHTML = '';
  }
}

function initializeContactNumberDuplicateCheck(form, input, excludeUserId) {
  if (!form || !input) return;

  let debounceTimer = null;
  let requestSequence = 0;

  input.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    clearContactNumberDuplicateState(input);

    debounceTimer = setTimeout(function() {
      validateContactNumberUniqueness(input, excludeUserId, ++requestSequence);
    }, 350);
  });

  input.addEventListener('blur', function() {
    clearTimeout(debounceTimer);
    validateContactNumberUniqueness(input, excludeUserId, ++requestSequence);
  });

  form.addEventListener('submit', async function(event) {
    const isUnique = await validateContactNumberUniqueness(input, excludeUserId, ++requestSequence);

    if (!isUnique) {
      event.preventDefault();
      input.focus();
    }
  });
}

async function validateContactNumberUniqueness(input, excludeUserId, requestSequence) {
  const normalized = normalizeContactNumber(input.value);

  if (normalized === '' || normalized.length < 7) {
    clearContactNumberDuplicateState(input);
    return true;
  }

  try {
    const query = new URLSearchParams({
      contact_number: input.value
    });

    if (excludeUserId) {
      query.set('exclude_user_id', String(excludeUserId));
    }

    const response = await fetch(`../../../api/check_contact_number.php?${query.toString()}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!response.ok) {
      throw new Error('Unable to verify contact number.');
    }

    const result = await response.json();

    if (requestSequence !== undefined) {
      const currentNormalized = normalizeContactNumber(input.value);

      if (currentNormalized !== normalized) {
        return !result.exists;
      }
    }

    if (result.exists) {
      showContactNumberDuplicateState(input, 'Contact number already exists.');
      return false;
    }

    clearContactNumberDuplicateState(input);
    return true;
  } catch (error) {
    clearContactNumberDuplicateState(input);
    return true;
  }
}

function normalizeContactNumber(value) {
  return (value || '').replace(/\D+/g, '');
}

function showContactNumberDuplicateState(input, message) {
  const helpText = getFieldHelpText(input);

  input.setCustomValidity(message);
  input.classList.add('is-invalid');
  input.classList.remove('is-valid');

  if (helpText) {
    helpText.innerHTML = `<span class="text-danger"><i class="fa fa-exclamation-triangle me-1"></i>${message}</span>`;
  }
}

function clearContactNumberDuplicateState(input) {
  const helpText = getFieldHelpText(input);

  input.setCustomValidity('');
  input.classList.remove('is-invalid');

  if (helpText) {
    helpText.innerHTML = '';
  }
}

function getFieldHelpText(input) {
  const formGroup = input.closest('.form-group-clean');

  return formGroup ? formGroup.querySelector('.form-help') : null;
}

// --- UI Notification Utility ---
function showNotification(message, type) {
  const notification = document.createElement('div');
  notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} position-fixed`;
  notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);';
  notification.innerHTML = `
    <div class="d-flex align-items-center">
      <i class="fa fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
      <span>${message}</span>
      <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
    </div>
  `;
  document.body.appendChild(notification);
  
  setTimeout(() => {
    if (notification.parentElement) {
      notification.remove();
    }
  }, 5000);
}

// --- Profile Image Logic ---
function previewProfile(e) {
  if (window.addUserProfileCropper) {
    window.addUserProfileCropper.openFromInputEvent(e);
  }
}

function initializeProfileCropper() {
  const fileInput = document.getElementById('profile_picture');
  const hiddenInput = document.getElementById('cropped_profile_picture');
  const preview = document.getElementById('profilePreview');
  if (!fileInput || !hiddenInput || !preview || typeof window.createProfileImageCropper !== 'function') return;

  window.addUserProfileCropper = window.createProfileImageCropper({
    fileInput: fileInput,
    hiddenInput: hiddenInput,
    previewTarget: function(dataUrl) {
      preview.innerHTML = `<img src="${dataUrl}" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`;
    },
    onError: function(message) {
      showNotification(message, 'error');
    }
  });
}

// --- Loading Overlay ---
function showLoading() {
  const loading = document.createElement('div');
  loading.id = 'loadingOverlay';
  loading.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;';
  loading.innerHTML = `
    <div class="text-center text-white">
      <div class="spinner-border mb-3" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
      </div>
      <h5>Creating user account...</h5>
    </div>
  `;
  document.body.appendChild(loading);
}

function hideLoading() {
  const loading = document.getElementById('loadingOverlay');
  if (loading) {
    loading.remove();
  }
}

// --- Dropdown Management ---
function initializeProfileDropdown() {
  const profileCard = document.getElementById('profileCard');
  const profileDropdown = document.getElementById('profileDropdown');
  
  if (!profileCard || !profileDropdown) return;
  
  let dropdownOpen = false;

  function toggleDropdown() {
    dropdownOpen = !dropdownOpen;
    if (dropdownOpen) {
      profileDropdown.classList.add('show');
    } else {
      profileDropdown.classList.remove('show');
    }
  }

  profileCard.addEventListener('click', function(e) {
    toggleDropdown();
    e.stopPropagation();
  });

  document.addEventListener('click', function(e) {
    if (!profileCard.contains(e.target)) {
      dropdownOpen = false;
      profileDropdown.classList.remove('show');
    }
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && dropdownOpen) {
      dropdownOpen = false;
      profileDropdown.classList.remove('show');
    }
  });
}

function simulatePhotoUpload() {
  showNotification('Photo upload functionality - this is a prototype interface.', 'info');
}
