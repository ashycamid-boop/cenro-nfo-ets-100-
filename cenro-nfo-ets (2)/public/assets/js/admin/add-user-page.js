document.addEventListener('DOMContentLoaded', function() {
  // Allow normal POST to server-side register handler. Client-side validation only.
  const passwordField = document.getElementById('password');
  const confirmPasswordField = document.getElementById('confirmPassword');

  confirmPasswordField.addEventListener('input', function() {
    if (passwordField.value !== confirmPasswordField.value) {
      confirmPasswordField.setCustomValidity('Passwords do not match');
      confirmPasswordField.classList.add('is-invalid');
    } else {
      confirmPasswordField.setCustomValidity('');
      confirmPasswordField.classList.remove('is-invalid');
    }
  });

  initializeProfileDropdown();
  initializeProfileCropper();
});

function simulatePhotoUpload() {
  showNotification('Photo upload functionality - this is a prototype interface.', 'info');
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

// Preview selected profile image - moved into script block so function is defined
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
