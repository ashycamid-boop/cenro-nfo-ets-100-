document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('searchInput');
  const searchInputMobile = document.getElementById('searchInputMobile');
  const searchInputModal = document.getElementById('searchInputModal');
  const filterButtons = document.querySelectorAll('.filter-btn');
  const mobileRoleFilter = document.getElementById('mobileRoleFilter');
  const mobileRoleFilterModal = document.getElementById('mobileRoleFilterModal');
  const applyUserFiltersMobile = document.getElementById('applyUserFiltersMobile');
  const clearUserFiltersMobile = document.getElementById('clearUserFiltersMobile');
  const activeFilters = document.getElementById('userManagementActiveFilters');
  const tableBody = document.getElementById('usersTableBody');
  const filtersModal = document.getElementById('userManagementFiltersModal');

  if (!tableBody) {
    return;
  }

  const allRows = Array.from(tableBody.getElementsByTagName('tr'));

  function normalizeRole(value) {
    return String(value || '').trim().toLowerCase();
  }

  function adjustNameFont() {
    const spans = document.querySelectorAll('.full-name-cell span');
    spans.forEach(function (span) {
      span.style.fontSize = '';
      const computed = window.getComputedStyle(span);
      let fontSize = parseFloat(computed.fontSize) || 14;
      const minSize = 10;

      while (span.scrollWidth > span.clientWidth && fontSize > minSize) {
        fontSize -= 0.5;
        span.style.fontSize = fontSize + 'px';
      }
    });
  }

  function getCurrentFilter() {
    if (mobileRoleFilterModal && mobileRoleFilterModal.closest('.modal') && mobileRoleFilterModal.closest('.modal').classList.contains('show')) {
      return mobileRoleFilterModal.value || 'all';
    }
    if (window.matchMedia('(max-width: 575.98px)').matches && mobileRoleFilter) {
      return mobileRoleFilter.value || 'all';
    }
    const activeButton = document.querySelector('.filter-btn.active');
    return activeButton ? activeButton.getAttribute('data-role') : 'all';
  }

  function setActiveFilter(role) {
    const normalizedRole = role || 'all';
    filterButtons.forEach(function (btn) {
      const isActive = btn.getAttribute('data-role') === normalizedRole;
      btn.classList.toggle('active', isActive);
      btn.classList.toggle('btn-primary', isActive);
      btn.classList.toggle('btn-outline-secondary', !isActive);
    });

    if (mobileRoleFilter) {
      mobileRoleFilter.value = normalizedRole;
    }

    if (mobileRoleFilterModal) {
      mobileRoleFilterModal.value = normalizedRole;
    }
  }

  function setAllSearch(value) {
    if (searchInput) searchInput.value = value;
    if (searchInputMobile) searchInputMobile.value = value;
    if (searchInputModal) searchInputModal.value = value;
  }

  function syncFromDesktop() {
    setAllSearch(searchInput ? searchInput.value : '');
    if (mobileRoleFilterModal) {
      mobileRoleFilterModal.value = getCurrentFilter();
    }
  }

  function syncFromModal() {
    setAllSearch(searchInputModal ? searchInputModal.value : '');
    setActiveFilter(mobileRoleFilterModal ? mobileRoleFilterModal.value || 'all' : 'all');
  }

  function applyRealtimeFromModal() {
    syncFromModal();
    filterRows((searchInput ? searchInput.value : '').toLowerCase(), mobileRoleFilterModal ? mobileRoleFilterModal.value : getCurrentFilter());
  }

  function renderActiveFilters() {
    if (!activeFilters) return;
    activeFilters.innerHTML = '';
    activeFilters.style.display = 'none';
  }

  function filterRows(searchTerm, roleFilter) {
    const normalizedFilter = normalizeRole(roleFilter) || 'all';
    allRows.forEach(function (row) {
      const role = normalizeRole(row.getAttribute('data-role'));
      const text = row.textContent.toLowerCase();
      const matchesSearch = searchTerm === '' || text.includes(searchTerm);
      const matchesRole = normalizedFilter === 'all' || role === normalizedFilter;
      row.style.display = matchesSearch && matchesRole ? '' : 'none';
    });

    adjustNameFont();
    renderActiveFilters();
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      setAllSearch(this.value);
      filterRows(this.value.toLowerCase(), getCurrentFilter());
    });
  }

  if (searchInputMobile) {
    searchInputMobile.addEventListener('input', function () {
      setAllSearch(this.value);
      filterRows(this.value.toLowerCase(), getCurrentFilter());
    });
  }

  filterButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      const role = this.getAttribute('data-role');
      setActiveFilter(role);
      const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
      filterRows(searchTerm, role);
    });
  });

  if (mobileRoleFilter) {
    function handleMobileFilterChange() {
      const role = this.value || 'all';
      setActiveFilter(role);
      const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
      filterRows(searchTerm, role);
    }

    mobileRoleFilter.addEventListener('change', handleMobileFilterChange);
    mobileRoleFilter.addEventListener('input', handleMobileFilterChange);
    mobileRoleFilter.addEventListener('touchend', function () {
      setTimeout(function () {
        const role = mobileRoleFilter.value || 'all';
        setActiveFilter(role);
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        filterRows(searchTerm, role);
      }, 0);
    }, { passive: true });
  }

  if (applyUserFiltersMobile) {
    applyUserFiltersMobile.addEventListener('click', function () {
      syncFromModal();
      filterRows((searchInput ? searchInput.value : '').toLowerCase(), mobileRoleFilterModal ? mobileRoleFilterModal.value : getCurrentFilter());
    });
  }

  if (clearUserFiltersMobile) {
    clearUserFiltersMobile.addEventListener('click', function () {
      setAllSearch('');
      setActiveFilter('all');
      filterRows('', 'all');
    });
  }

  if (filtersModal) {
    filtersModal.addEventListener('show.bs.modal', function () {
      syncFromDesktop();
    });
  }

  if (searchInputModal) {
    searchInputModal.addEventListener('input', function () {
      applyRealtimeFromModal();
    });
  }

  if (mobileRoleFilterModal) {
    mobileRoleFilterModal.addEventListener('change', function () {
      applyRealtimeFromModal();
    });

    mobileRoleFilterModal.addEventListener('input', function () {
      applyRealtimeFromModal();
    });
  }

  document.addEventListener('click', function (event) {
    const editButton = event.target.closest('.btn-edit');
    if (!editButton) {
      return;
    }

    const row = editButton.closest('tr');
    if (!row) {
      return;
    }

    const userId = row.getAttribute('data-user-id');
    if (userId) {
      window.location.href = 'edit_user.php?id=' + encodeURIComponent(userId);
    }
  });

  document.addEventListener('click', function (event) {
    const disableButton = event.target.closest('.btn-disable');
    if (!disableButton) {
      return;
    }

    const row = disableButton.closest('tr');
    if (!row) {
      return;
    }

    const userFullName = row.getAttribute('data-user-name');
    const userId = row.getAttribute('data-user-id');
    const statusBadge = row.querySelector('.status-badge');

    if (!statusBadge || !userId) {
      return;
    }

    const isCurrentlyEnabled = statusBadge.classList.contains('status-enable');
    const action = isCurrentlyEnabled ? 'disable' : 'enable';
    const message = isCurrentlyEnabled
      ? 'Are you sure you want to disable ' + userFullName + '?'
      : 'Are you sure you want to enable ' + userFullName + '?';

    if (!confirm(message)) {
      return;
    }

    fetch('../../../../app/admin/update_user_status.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: 'user_id=' + encodeURIComponent(userId) + '&action=' + action
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data.success) {
          alert('Error: ' + (data.message || 'Failed to update user status'));
          return;
        }

        if (action === 'disable') {
          statusBadge.textContent = 'Disabled';
          statusBadge.classList.remove('status-enable');
          statusBadge.classList.add('status-disable');
          disableButton.innerHTML = '<i class="fa fa-check-circle"></i><span class="d-none d-sm-inline"> Enable</span>';
          disableButton.setAttribute('data-current-status', '0');
        } else {
          statusBadge.textContent = 'Enable';
          statusBadge.classList.remove('status-disable');
          statusBadge.classList.add('status-enable');
          disableButton.innerHTML = '<i class="fa fa-ban"></i><span class="d-none d-sm-inline"> Disable</span>';
          disableButton.setAttribute('data-current-status', '1');
        }

        alert(data.message || 'User status updated successfully!');
      })
      .catch(function (error) {
        alert('Error: ' + error.message);
      });
  });

  adjustNameFont();
  syncFromDesktop();
  renderActiveFilters();
  window.addEventListener('resize', function () {
    clearTimeout(window._adjustNameFontTimer);
    window._adjustNameFontTimer = setTimeout(adjustNameFont, 120);
  });

  const observer = new MutationObserver(function () {
    adjustNameFont();
  });
  observer.observe(tableBody, { childList: true, subtree: true });
});
