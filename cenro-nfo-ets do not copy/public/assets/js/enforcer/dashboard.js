// Initialize profile dropdown
function initializeProfileDropdown() {
  const profileCard = document.getElementById('profileCard');
  const profileDropdown = document.getElementById('profileDropdown');

  if (!profileCard || !profileDropdown) return;

  let dropdownOpen = false;

  function toggleDropdown() {
    dropdownOpen = !dropdownOpen;
    profileDropdown.style.display = dropdownOpen ? 'flex' : 'none';
  }

  profileCard.addEventListener('click', function(e) {
    toggleDropdown();
    e.stopPropagation();
  });

  document.addEventListener('click', function(e) {
    if (!profileCard.contains(e.target)) {
      dropdownOpen = false;
      profileDropdown.style.display = 'none';
    }
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && dropdownOpen) {
      dropdownOpen = false;
      profileDropdown.style.display = 'none';
    }
  });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  initializeProfileDropdown();
});
