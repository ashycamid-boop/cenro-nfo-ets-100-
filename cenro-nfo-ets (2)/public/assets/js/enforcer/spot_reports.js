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

function parseDateOnly(str) {
  if (!str) return null;
  const m = str.match(/(\d{4}-\d{2}-\d{2})/);
  if (m) return new Date(m[1]);
  const d = new Date(str);
  return isNaN(d.getTime()) ? null : d;
}

function applySpotReportFilters() {
  const searchTerm = (document.getElementById('searchInput').value || '').trim().toLowerCase();
  const dateFromVal = document.getElementById('dateFrom').value;
  const dateToVal = document.getElementById('dateTo').value;
  const statusVal = (document.getElementById('statusFilter').value || '').trim().toLowerCase();

  const dateFrom = dateFromVal ? new Date(dateFromVal) : null;
  const dateTo = dateToVal ? new Date(dateToVal) : null;
  if (dateTo) dateTo.setHours(23, 59, 59, 999);

  const rows = document.querySelectorAll('table tbody tr');
  let anyVisible = false;

  rows.forEach(row => {
    // Skip placeholder row with colspan.
    if (row.querySelector('td') && row.querySelector('td').getAttribute('colspan')) return;

    const cells = row.cells;
    if (!cells) return;

    const ref = (cells[0].textContent || '').toLowerCase();
    const incText = (cells[1].textContent || '').trim();
    const loc = (cells[2].textContent || '').toLowerCase();
    const items = (cells[3].textContent || '').toLowerCase();
    const teamLeader = (cells[4] ? (cells[4].textContent || '') : '').toLowerCase();
    const custodian = (cells[5] ? (cells[5].textContent || '') : '').toLowerCase();
    const submittedBy = (cells[6] ? (cells[6].textContent || '') : '').toLowerCase();
    const statusText = (cells[7] ? (cells[7].textContent || '') : '').toLowerCase();

    let visible = true;

    if (searchTerm) {
      const hay = ref + ' ' + loc + ' ' + items + ' ' + teamLeader + ' ' + custodian + ' ' + submittedBy;
      if (!hay.includes(searchTerm)) visible = false;
    }

    if (visible && (dateFrom || dateTo)) {
      const incDate = parseDateOnly(incText);
      if (!incDate) {
        visible = false;
      } else {
        if (dateFrom && incDate < dateFrom) visible = false;
        if (dateTo && incDate > dateTo) visible = false;
      }
    }

    if (visible && statusVal) {
      const norm = statusVal.replace(/_/g, ' ');
      if (!statusText.includes(norm)) visible = false;
    }

    row.style.display = visible ? '' : 'none';
    if (visible) anyVisible = true;
  });

  // If no rows visible, show the placeholder row.
  const tbody = document.querySelector('table tbody');
  if (tbody) {
    const placeholder = tbody.querySelector('tr[data-placeholder]');
    if (!anyVisible) {
      if (!placeholder) {
        const nr = document.createElement('tr');
        nr.setAttribute('data-placeholder', '1');
        nr.innerHTML = '<td colspan="10" class="text-center">No spot reports found.</td>';
        tbody.appendChild(nr);
      }
    } else if (placeholder) {
      placeholder.remove();
    }
  }
}

// Attach click handlers to status comment buttons (delegated)
function showStatusCommentModal(text) {
  const existing = document.getElementById('statusCommentModal');
  if (existing) existing.remove();

  const modalHtml = `
    <div class="modal fade" id="statusCommentModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Rejection Comment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="statusCommentModalBody"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML('beforeend', modalHtml);
  const bodyEl = document.getElementById('statusCommentModalBody');
  if (bodyEl) bodyEl.textContent = text || '';
  const modal = new bootstrap.Modal(document.getElementById('statusCommentModal'));
  modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
  initializeProfileDropdown();

  const applyFilterBtn = document.getElementById('applyFilter');
  const clearFilterBtn = document.getElementById('clearFilter');
  if (applyFilterBtn) applyFilterBtn.addEventListener('click', applySpotReportFilters);
  if (clearFilterBtn) {
    clearFilterBtn.addEventListener('click', function() {
      document.getElementById('searchInput').value = '';
      document.getElementById('dateFrom').value = '';
      document.getElementById('dateTo').value = '';
      document.getElementById('statusFilter').value = '';
      applySpotReportFilters();
    });
  }

  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        applySpotReportFilters();
      }
    });
  }

  document.body.addEventListener('click', function(e) {
    const btn = e.target.closest('.status-comment-btn');
    if (!btn) return;
    e.stopPropagation();
    const txt = btn.getAttribute('data-comment') || '';
    showStatusCommentModal(txt);
  });
});
