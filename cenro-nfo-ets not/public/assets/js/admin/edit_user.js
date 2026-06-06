document.addEventListener('DOMContentLoaded', function() {
  // 1. Initialize existing UI components
  initializeProfileDropdown();
  initializeProfileCropper();

  // 2. Password Validation and Matching Logic
  const passwordField = document.getElementById('password');
  const confirmPasswordField = document.getElementById('confirmPassword');
  const editUserForm = document.getElementById('editUserForm');
  const emailField = document.getElementById('email');
  const contactNumberField = document.getElementById('contactNumber');
  const userIdField = document.querySelector('input[name="user_id"]');

  initializeEmailDuplicateCheck(
    editUserForm,
    emailField,
    userIdField ? userIdField.value : ''
  );

  initializeContactNumberDuplicateCheck(
    editUserForm,
    contactNumberField,
    userIdField ? userIdField.value : ''
  );

  if (passwordField && confirmPasswordField) {
    // Alphanumeric and Length Check
    passwordField.addEventListener('input', function() {
      const password = this.value;
      let strengthMessage = '';

      if (password.length > 0) {
        const hasLetter = /[a-zA-Z]/.test(password);
        const hasNumber = /\d/.test(password);

        if (!hasLetter || !hasNumber) {
          strengthMessage = 'Password must be a mix of letters and numbers.';
        } else if (password.length < 8) {
          strengthMessage = 'Password must be at least 8 characters long.';
        }
      }

      this.setCustomValidity(strengthMessage);
      // Nagdadagdag ng visual red border kung may error (Bootstrap class)
      this.classList.toggle('is-invalid', strengthMessage !== '');
    });

    // Confirm Password Match Check
    confirmPasswordField.addEventListener('input', function() {
      const isMatch = this.value === passwordField.value;

      if (this.value.length > 0 && !isMatch) {
        this.setCustomValidity('Passwords do not match');
        this.classList.add('is-invalid');
      } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
      }
    });
  }
});

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

function previewProfile(e) {
  if (window.editUserProfileCropper) {
    window.editUserProfileCropper.openFromInputEvent(e);
  }
}

function initializeProfileCropper() {
  const fileInput = document.getElementById('profile_picture');
  const hiddenInput = document.getElementById('cropped_profile_picture');
  const preview = document.getElementById('profilePreview');
  if (!fileInput || !hiddenInput || !preview || typeof window.createProfileImageCropper !== 'function') return;

  window.editUserProfileCropper = window.createProfileImageCropper({
    fileInput: fileInput,
    hiddenInput: hiddenInput,
    previewTarget: function(dataUrl) {
      preview.innerHTML = `<img src="${dataUrl}" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:50%; display:block;">`;
    },
    onError: function(message) {
      showNotification(message, 'error');
    }
  });
}

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
