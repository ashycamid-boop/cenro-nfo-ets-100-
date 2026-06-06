document.addEventListener('DOMContentLoaded', function() {
  initializeProfileDropdown();
  initializeProfileCropper();
});

function previewProfileAndSubmit(e) {
  if (window.profileModuleCropper) {
    window.profileModuleCropper.openFromInputEvent(e);
  }
}

function triggerProfilePicInput() {
  const input = document.getElementById('profile_picture_input');
  if (input) {
    input.click();
  }
}

function initializeProfileCropper() {
  const fileInput = document.getElementById('profile_picture_input');
  const hiddenInput = document.getElementById('cropped_profile_picture');
  const form = document.getElementById('profilePicForm');
  const image = document.getElementById('mainProfileImage') || document.querySelector('.profile-image');

  if (!fileInput || !hiddenInput || !form || !image || typeof window.createProfileImageCropper !== 'function') {
    return;
  }

  window.profileModuleCropper = window.createProfileImageCropper({
    fileInput: fileInput,
    hiddenInput: hiddenInput,
    previewTarget: image,
    autoSubmitForm: form,
    onError: function(message) {
      showNotification(message, 'error');
    }
  });
}

function changeProfilePicture() {
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

  setTimeout(function() {
    if (notification.parentElement) {
      notification.remove();
    }
  }, 5000);
}

function initializeProfileDropdown() {
  const profileCard = document.getElementById('profileCard');
  const profileDropdown = document.getElementById('profileDropdown');

  if (!profileCard || !profileDropdown) {
    return;
  }

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
