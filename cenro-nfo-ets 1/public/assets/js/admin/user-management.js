document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('searchInput');
  const filterButtons = document.querySelectorAll('.filter-btn');
  const tableBody = document.getElementById('usersTableBody');

  if (!tableBody) {
    return;
  }

  const allRows = Array.from(tableBody.getElementsByTagName('tr'));

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
    const activeButton = document.querySelector('.filter-btn.active');
    return activeButton ? activeButton.getAttribute('data-role') : 'all';
  }

  function filterRows(searchTerm, roleFilter) {
    allRows.forEach(function (row) {
      const role = row.getAttribute('data-role');
      const text = row.textContent.toLowerCase();
      const matchesSearch = searchTerm === '' || text.includes(searchTerm);
      const matchesRole = roleFilter === 'all' || role === roleFilter;
      row.style.display = matchesSearch && matchesRole ? '' : 'none';
    });

    adjustNameFont();
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      filterRows(this.value.toLowerCase(), getCurrentFilter());
    });
  }

  filterButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      filterButtons.forEach(function (btn) {
        btn.classList.remove('active', 'btn-primary');
        btn.classList.add('btn-outline-secondary');
      });

      this.classList.add('active', 'btn-primary');
      this.classList.remove('btn-outline-secondary');

      const role = this.getAttribute('data-role');
      const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
      filterRows(searchTerm, role);
    });
  });

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
  window.addEventListener('resize', function () {
    clearTimeout(window._adjustNameFontTimer);
    window._adjustNameFontTimer = setTimeout(adjustNameFont, 120);
  });

  const observer = new MutationObserver(function () {
    adjustNameFont();
  });
  observer.observe(tableBody, { childList: true, subtree: true });
});
