// Case Details JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // File item click handlers
    const fileItems = document.querySelectorAll('.file-name');
    fileItems.forEach(item => {
        item.addEventListener('click', function() {
            // Simulate file opening/download
            const fileName = this.textContent;
            console.log('Opening file:', fileName);
            
            // You can add actual file opening logic here
            // For now, just show an alert
            alert('Opening file: ' + fileName);
        });
    });

    // Status badge hover effects
    const badges = document.querySelectorAll('.badge');
    badges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Print functionality
    function printCaseDetails() {
        window.print();
    }

    // Export functionality
    function exportCaseDetails() {
        // Implement export logic here
        alert('Export functionality to be implemented');
    }

    // Update case status functionality
    function updateCaseStatus() {
        // Implement update logic here
        alert('Update functionality to be implemented');
    }

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+P for print
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            printCaseDetails();
        }
        
        // Escape to go back
        if (e.key === 'Escape') {
            window.history.back();
        }
    });

    // Auto-resize tables on window resize
    window.addEventListener('resize', function() {
        const tables = document.querySelectorAll('.table-responsive');
        tables.forEach(table => {
            // Force reflow to handle responsive table layout
            table.style.display = 'none';
            table.offsetHeight; // Trigger reflow
            table.style.display = '';
        });
    });

    // Add smooth scrolling for long content
    const smoothScrollToTop = () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };

    // Add scroll to top functionality
    let scrollToTopButton = document.createElement('button');
    scrollToTopButton.innerHTML = '<i class="fa fa-arrow-up"></i>';
    scrollToTopButton.className = 'btn btn-primary position-fixed';
    scrollToTopButton.style.cssText = `
        bottom: 20px;
        right: 20px;
        z-index: 1000;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: none;
    `;
    scrollToTopButton.onclick = smoothScrollToTop;
    document.body.appendChild(scrollToTopButton);

    // Show/hide scroll to top button
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollToTopButton.style.display = 'block';
        } else {
            scrollToTopButton.style.display = 'none';
        }
    });

    console.log('Case Details page loaded successfully');
});

// Extracted from case_details.php inline status-management script
    // Edit Person Status
    function editPersonStatus(personId) {
      const statusOptions = [
        { value: 'under-custody', text: 'Under Custody / Detained', class: 'bg-warning' },
        { value: 'under-inquest', text: 'Under Inquest / For Filing of Case', class: 'bg-primary' },
        { value: 'respondent-accused', text: 'Respondent / Accused', class: 'bg-secondary' },
        { value: 'released-pending-investigation', text: 'Released Pending Investigation', class: 'bg-success' },
        { value: 'on-bail', text: 'On Bail', class: 'bg-cyan' },
        { value: 'convicted', text: 'Convicted', class: 'bg-dark' },
        { value: 'case-dismissed', text: 'Case Dismissed / Acquitted', class: 'bg-teal' }
      ];

      showStatusModal(`person-status-${personId}`, 'Person', statusOptions, (newStatus) => {
        saveStatusToDB('person', personId, newStatus);
      }, false);
    }
    
    // Edit Vehicle Status
    function editVehicleStatus(vehicleId) {
      const statusOptions = [
        { value: 'for-custody', text: 'For Custody', class: 'bg-warning' },
        { value: 'impounded', text: 'Impounded', class: 'bg-info' },
        { value: 'under-investigation', text: 'Under Investigation', class: 'bg-primary' },
        { value: 'for-auction', text: 'For Public Auction', class: 'bg-orange' },
        { value: 'released', text: 'Released to Owner', class: 'bg-success' },
        { value: 'forfeited', text: 'Forfeited to Government', class: 'bg-purple' },
        { value: 'donated', text: 'Donated', class: 'bg-teal' },
        { value: 'destroyed', text: 'Destroyed', class: 'bg-danger' }
      ];
      
      showStatusModal(`vehicle-status-${vehicleId}`, 'Vehicle', statusOptions, (newStatus) => {
        saveStatusToDB('vehicle', vehicleId, newStatus);
      }, false);
    }
    
    // Edit Item Status
    function editItemStatus(itemId) {
      const statusOptions = [
        { value: 'confiscated', text: 'Confiscated', class: 'bg-warning' },
        { value: 'seized', text: 'Seized', class: 'bg-info' },
        { value: 'under-custody', text: 'Under Custody', class: 'bg-primary' },
        { value: 'for-disposal', text: 'For Disposal', class: 'bg-orange' },
        { value: 'disposed', text: 'Disposed', class: 'bg-success' },
        { value: 'burned', text: 'Burned/Destroyed', class: 'bg-danger' },
        { value: 'forfeited', text: 'Forfeited to Government', class: 'bg-purple' },
        { value: 'donated', text: 'Donated to LGU', class: 'bg-teal' },
        { value: 'returned', text: 'Returned to Owner', class: 'bg-cyan' },
        { value: 'auctioned', text: 'Publicly Auctioned', class: 'bg-indigo' }
      ];
      
      showStatusModal(`item-status-${itemId}`, 'Item', statusOptions, (newStatus) => {
        saveStatusToDB('item', itemId, newStatus);
      }, false);
    }
    
    // Edit Case Status
    function editCaseStatus() {
      const statusOptions = [
        { value: 'under-investigation', text: 'Under Investigation', class: 'bg-primary' },
        { value: 'pending-review', text: 'Pending Review', class: 'bg-warning' },
        { value: 'for-filing', text: 'For Filing', class: 'bg-warning' },
        { value: 'filed-in-court', text: 'Filed in Court', class: 'bg-secondary' },
        { value: 'ongoing-trial', text: 'Ongoing Trial', class: 'bg-info' },
        { value: 'resolved', text: 'Resolved', class: 'bg-success' },
        { value: 'dismissed', text: 'Dismissed', class: 'bg-danger' },
        { value: 'archived', text: 'Archived', class: 'bg-dark' },
        { value: 'on-hold', text: 'On Hold', class: 'bg-danger' },
        { value: 'appealed', text: 'Under Appeal', class: 'bg-teal' }
      ];
      
      // Do not show comments field for Case status
      showStatusModal('case-status', 'Case', statusOptions, (newStatus) => {
        saveStatusToDB('case', window.reportId || 0, newStatus);
      }, false);
    }

    // Save status to server and update UI on success
    async function saveStatusToDB(type, id, newStatus) {
      if (!window.updateStatusUrl) {
        console.error('update URL not set');
        alert('Update URL not configured.');
        return;
      }

      try {
        const res = await fetch(window.updateStatusUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            type: type,
            id: parseInt(id, 10) || 0,
            status: newStatus.text,
            status_key: newStatus.value
          })
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Update failed');

        // Update UI after successful DB update
        if (type === 'vehicle') updateApprehendedStatus(`vehicle-status-${id}`, newStatus);
        else if (type === 'item') updateApprehendedStatus(`item-status-${id}`, newStatus);
        else if (type === 'case') updateApprehendedStatus('case-status', newStatus);
        else if (type === 'person') updateApprehendedStatus(`person-status-${id}`, newStatus);

      } catch (err) {
        console.error('Failed to save status:', err);
        alert('Hindi na-save sa database: ' + (err.message || err));
      }
    }
    
    // Show Status Modal
    function showStatusModal(elementId, itemType, statusOptions, callback, showComments = true) {
      const currentBadge = document.getElementById(elementId);
      const currentStatus = currentBadge ? currentBadge.textContent.trim().toLowerCase().replace(/\s+/g, '-') : '';

      let optionsHtml = '';
      statusOptions.forEach(option => {
        const selected = option.value === currentStatus ? 'selected' : '';
        optionsHtml += `<option value="${option.value}" data-class="${option.class}" ${selected}>${option.text}</option>`;
      });

      const commentsHtml = showComments ? `
                <div class="mb-3">
                  <label for="statusComments" class="form-label">Comments (Optional):</label>
                  <textarea class="form-control" id="statusComments" rows="3" placeholder="Add comments for this status change..."></textarea>
                </div>
      ` : '';

      const modalHtml = `
        <div class="modal fade" id="statusModal" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Edit ${itemType} Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label for="statusSelect" class="form-label">Select Status:</label>
                  <select class="form-select" id="statusSelect">
                    ${optionsHtml}
                  </select>
                </div>
                ${commentsHtml}
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmStatusUpdate()">
                  <i class="fa fa-save me-2"></i>Update Status
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
      
      // Remove existing modal
      const existingModal = document.getElementById('statusModal');
      if (existingModal) {
        existingModal.remove();
      }
      
      document.body.insertAdjacentHTML('beforeend', modalHtml);
      
      const modal = new bootstrap.Modal(document.getElementById('statusModal'));
      modal.show();
      
      // Store callback for later use
      window.currentStatusCallback = callback;
    }
    
    // Confirm Status Update
    function confirmStatusUpdate() {
      const statusSelect = document.getElementById('statusSelect');
      const selectedOption = statusSelect.options[statusSelect.selectedIndex];
      const newStatus = {
        value: statusSelect.value,
        text: selectedOption.text,
        class: selectedOption.getAttribute('data-class')
      };
      const commentsEl = document.getElementById('statusComments');
      const comments = commentsEl ? commentsEl.value : '';
      
      if (confirm(`Are you sure you want to change the status to "${newStatus.text}"?`)) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
        modal.hide();
        
        // Execute callback with new status
        if (window.currentStatusCallback) {
          window.currentStatusCallback(newStatus);
        }
        
        // Log the change
        console.log('Status updated:', {
          newStatus: newStatus,
          comments: comments,
          timestamp: new Date().toLocaleString(),
          user: 'Pj Mordeno (Enforcement Officer)'
        });
      }
    }
    
    // Update Apprehended Status
    function updateApprehendedStatus(elementId, newStatus) {
      const badge = document.getElementById(elementId);
      if (badge) {
        badge.className = `badge ${newStatus.class}`;
        badge.textContent = newStatus.text;
        
        // Show success message
        showSuccessMessage(`Status updated to "${newStatus.text}" successfully!`);
      }
    }
    
    // Show Success Message
    function showSuccessMessage(message) {
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-success alert-dismissible fade show';
      alertDiv.style.position = 'fixed';
      alertDiv.style.top = '20px';
      alertDiv.style.right = '20px';
      alertDiv.style.zIndex = '9999';
      alertDiv.style.minWidth = '300px';
      alertDiv.innerHTML = `
        <i class="fa fa-check-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      
      document.body.appendChild(alertDiv);
      
      setTimeout(() => {
        if (alertDiv.parentNode) {
          alertDiv.remove();
        }
      }, 4000);
    }
    
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Case Details page initialized with status editing functionality');
    });
