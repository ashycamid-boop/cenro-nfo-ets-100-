// Delegated handler for status comment icon - show Bootstrap modal like enforcement officer
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

document.body.addEventListener('click', function(e) {
  const btn = e.target.closest && e.target.closest('.status-comment-btn');
  if (!btn) return;
  e.stopPropagation();
  const txt = btn.getAttribute('data-comment') || '';
  showStatusCommentModal(txt);
});

function editSpotReportStatus(reportId) {
  // Create modal for editing status
  const modalHtml = `
    <div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editStatusModalLabel">Edit Status - ${reportId}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="editStatusForm">
              <div class="mb-3">
                <label for="statusSelect" class="form-label">Select Status:</label>
                <select class="form-select" id="statusSelect" name="status">
                  <option value="pending" data-class="bg-warning">Pending</option>
                  <option value="approved" data-class="bg-success">Approved</option>
                  <option value="rejected" data-class="bg-danger">Rejected</option>
                  <option value="under_review" data-class="bg-info">Under Review</option>
                </select>
              </div>
              <!-- comments removed per request -->
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="updateSpotReportStatus('${reportId}')">
              <i class="fa fa-save me-2"></i>Update Status
            </button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Remove existing modal if any
  const existingModal = document.getElementById('editStatusModal');
  if (existingModal) {
    existingModal.remove();
  }

  // Add modal to page
  document.body.insertAdjacentHTML('beforeend', modalHtml);

  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('editStatusModal'));
  modal.show();

  // Set current status as selected
  const row = document.querySelector(`[onclick*="${reportId}"]`).closest('tr');
  const currentStatusText = row.querySelector('td:nth-child(8) .badge').textContent.trim().toLowerCase();
  const statusSelect = document.getElementById('statusSelect');
  statusSelect.value = currentStatusText;
}

function updateSpotReportStatus(reportId) {
  const statusSelect = document.getElementById('statusSelect');
  const selectedOption = statusSelect.options[statusSelect.selectedIndex];
  const newStatus = statusSelect.value;
  const badgeClass = selectedOption.getAttribute('data-class');
  const statusText = selectedOption.text;

  const confirmMessage = `Are you sure you want to change the status to "${statusText}"?`;

  if (!confirm(confirmMessage)) return;

  // Close modal
  const modal = bootstrap.Modal.getInstance(document.getElementById('editStatusModal'));
  modal.hide();

  // Find the edit button and show loading state
  const editButton = document.querySelector(`[onclick*="${reportId}"]`);
  const originalContent = editButton ? editButton.innerHTML : null;
  if (editButton) {
    editButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    editButton.disabled = true;
  }

  // Map frontend values to DB-stored status labels
  const statusMap = {
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected',
    under_review: 'Under Review'
  };
  const payloadStatus = statusMap[newStatus] || statusText || newStatus;

  // Call backend to persist status
  fetch('../update_spot_report_status.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ref: reportId, status: payloadStatus })
  })
    .then((r) => r.json())
    .then((data) => {
      if (data && data.success) {
        // Update the status badge in the same row
        if (editButton) {
          const row = editButton.closest('tr');
          const statusCell = row.querySelector('td:nth-child(8)'); // Status column
          if (statusCell) {
            statusCell.innerHTML = `<span class="badge ${badgeClass}">${payloadStatus}</span>`;
          }
        }
        showActionMessage(`Spot report ${reportId} status updated to "${statusText}" successfully!`, 'success');
      } else {
        showActionMessage(data.message || 'Failed to update status', 'danger');
      }
    })
    .catch((err) => {
      console.error(err);
      showActionMessage('Network or server error while updating status', 'danger');
    })
    .finally(() => {
      if (editButton) {
        editButton.innerHTML = originalContent;
        editButton.disabled = false;
      }
    });
}

function showActionMessage(message, type = 'info') {
  // Remove existing alerts
  const existingAlert = document.querySelector('.action-alert');
  if (existingAlert) {
    existingAlert.remove();
  }

  // Create new alert
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type} alert-dismissible fade show action-alert`;
  alertDiv.style.position = 'fixed';
  alertDiv.style.top = '20px';
  alertDiv.style.right = '20px';
  alertDiv.style.zIndex = '9999';
  alertDiv.style.minWidth = '300px';
  alertDiv.innerHTML = `
    <i class="fa fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;

  // Add to page
  document.body.appendChild(alertDiv);

  // Auto-dismiss after 4 seconds
  setTimeout(() => {
    if (alertDiv.parentNode) {
      alertDiv.remove();
    }
  }, 4000);
}

// Initialize page functionality
document.addEventListener('DOMContentLoaded', function() {
  // Add hover effects to action buttons
  const actionButtons = document.querySelectorAll('.btn-group .btn');
  actionButtons.forEach((btn) => {
    btn.addEventListener('mouseenter', function() {
      if (!this.disabled) {
        this.style.transform = 'scale(1.1)';
        this.style.transition = 'transform 0.2s ease';
      }
    });

    btn.addEventListener('mouseleave', function() {
      this.style.transform = 'scale(1)';
    });
  });

  // Initialize filter functionality
  const applyFilterBtn = document.getElementById('applyFilter');
  const clearFilterBtn = document.getElementById('clearFilter');

  function parseDateOnly(str) {
    if (!str) return null;
    // Try to extract YYYY-MM-DD from the string
    const m = str.match(/(\d{4}-\d{2}-\d{2})/);
    if (m) return new Date(m[1]);
    const d = new Date(str);
    return isNaN(d.getTime()) ? null : d;
  }

  function parseCurrencyToNumber(text) {
    if (!text) return 0;
    const cleaned = text.replace(/[^0-9.\-]/g, '');
    const n = parseFloat(cleaned);
    return isNaN(n) ? 0 : n;
  }

  function applyFilters() {
    const searchTerm = (document.getElementById('searchInput').value || '').trim().toLowerCase();
    const dateFromVal = document.getElementById('dateFrom').value;
    const dateToVal = document.getElementById('dateTo').value;
    const statusVal = (document.getElementById('statusFilter').value || '').trim().toLowerCase();

    const dateFrom = dateFromVal ? new Date(dateFromVal) : null;
    const dateTo = dateToVal ? new Date(dateToVal) : null;
    if (dateTo) {
      // include whole day
      dateTo.setHours(23, 59, 59, 999);
    }

    const rows = document.querySelectorAll('table tbody tr');
    let visibleCount = 0;
    let estSum = 0;

    rows.forEach((row) => {
      // skip placeholder row with colspan
      if (row.querySelector('td') && row.querySelector('td').getAttribute('colspan')) return;

      const cells = row.cells;
      if (!cells || cells.length < 9) return;

      const ref = (cells[0].textContent || '').toLowerCase();
      const incText = (cells[1].textContent || '').trim();
      const loc = (cells[2].textContent || '').toLowerCase();
      const items = (cells[3].textContent || '').toLowerCase();
      const teamLeader = (cells[4].textContent || '').toLowerCase();
      const custodian = (cells[5].textContent || '').toLowerCase();
      const submittedBy = (cells[6].textContent || '').toLowerCase();
      const statusText = (cells[7].textContent || '').toLowerCase();
      const estText = (cells[8].textContent || '').trim();

      let visible = true;

      if (searchTerm) {
        const hay = `${ref} ${loc} ${items} ${teamLeader} ${custodian} ${submittedBy}`;
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
        // Normalize status values (allow matches like 'under review')
        const norm = statusVal.replace(/_/g, ' ');
        if (!statusText.includes(norm)) visible = false;
      }

      if (visible) {
        row.style.display = '';
        visibleCount++;
        estSum += parseCurrencyToNumber(estText);
      } else {
        row.style.display = 'none';
      }
    });

    // Update summary cards
    const totalEl = document.getElementById('summaryTotal');
    const estEl = document.getElementById('summaryEst');
    if (totalEl) totalEl.textContent = visibleCount;
    if (estEl) {
      estEl.textContent = visibleCount > 0
        ? `â‚± ${estSum.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
        : '-';
    }
  }

  if (applyFilterBtn) {
    applyFilterBtn.addEventListener('click', function() {
      applyFilters();
    });
  }

  if (clearFilterBtn) {
    clearFilterBtn.addEventListener('click', function() {
      document.getElementById('searchInput').value = '';
      document.getElementById('dateFrom').value = '';
      document.getElementById('dateTo').value = '';
      document.getElementById('statusFilter').value = '';
      applyFilters();
    });
  }

  // Allow pressing Enter in search box to apply filters
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        applyFilters();
      }
    });
  }
});
