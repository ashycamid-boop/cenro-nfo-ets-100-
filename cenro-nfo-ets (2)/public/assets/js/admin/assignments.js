// Assignments JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const officeUnitFilter = document.getElementById('officeUnitFilter');
    const applyBtn = document.getElementById('applyBtn');
    const clearBtn = document.getElementById('clearBtn');
    const searchInputMobile = document.getElementById('searchInputMobile');
    const searchInputModal = document.getElementById('searchInputModal');
    const roleFilterModal = document.getElementById('roleFilterModal');
    const officeUnitFilterModal = document.getElementById('officeUnitFilterModal');
    const openAssignmentsFiltersMobile = document.getElementById('openAssignmentsFiltersMobile');
    const assignmentsMobileFilterModal = document.getElementById('assignmentsMobileFilterModal');
    const assignmentsActiveFilterChips = document.getElementById('assignmentsActiveFilterChips');
    const clearAssignmentsFiltersMobile = document.getElementById('clearAssignmentsFiltersMobile');
    const applyAssignmentsFiltersMobile = document.getElementById('applyAssignmentsFiltersMobile');
    const printAllQrMobile = document.getElementById('printAllQrMobile');
    const assignmentsTable = document.getElementById('assignmentsTable');
    const tableBody = assignmentsTable.querySelector('tbody');
    const tableRows = Array.from(assignmentsTable.querySelectorAll('tbody tr')).filter(row => row.querySelectorAll('td').length > 1);

    function getSearchValue() {
        return searchInput ? searchInput.value.trim().toLowerCase() : '';
    }

    function getRoleValue() {
        return roleFilter ? roleFilter.value.trim() : '';
    }

    function getOfficeUnitValue() {
        return officeUnitFilter ? officeUnitFilter.value.trim() : '';
    }

    function syncFilterInputs(search, role, officeUnit) {
        [searchInput, searchInputMobile, searchInputModal].forEach(input => {
            if (input) input.value = search;
        });
        [roleFilter, roleFilterModal].forEach(select => {
            if (select) select.value = role;
        });
        [officeUnitFilter, officeUnitFilterModal].forEach(select => {
            if (select) select.value = officeUnit;
        });
    }

    function updateFilterChips(search, role, officeUnit) {
        if (!assignmentsActiveFilterChips) return;
        assignmentsActiveFilterChips.innerHTML = '';
        assignmentsActiveFilterChips.style.display = 'none';
    }

    function removeAssignmentsEmptyState() {
        const existing = tableBody.querySelector('tr[data-empty-state="true"]');
        if (existing) existing.remove();
    }

    function ensureAssignmentsEmptyState(visibleRows) {
        removeAssignmentsEmptyState();
        if (visibleRows > 0) return;
        const row = document.createElement('tr');
        row.dataset.emptyState = 'true';
        row.innerHTML = '<td colspan="9" class="text-center text-muted py-3">No users found.</td>';
        tableBody.appendChild(row);
    }

    // Search functionality
    function performSearch(searchTerm = getSearchValue(), selectedRole = getRoleValue(), selectedOfficeUnit = getOfficeUnitValue()) {
        const normalizedSearch = (searchTerm || '').toLowerCase();
        const normalizedRole = (selectedRole || '').toLowerCase();
        const normalizedOfficeUnit = (selectedOfficeUnit || '').toLowerCase();
        let visibleRows = 0;

        tableRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const userId = cells[0].textContent.toLowerCase();
            const fullName = cells[1].textContent.toLowerCase();
            const email = cells[2].textContent.toLowerCase();
            const role = cells[3].textContent.toLowerCase();
            const officeUnit = cells[4].textContent.toLowerCase();
            const devices = cells[5].textContent.toLowerCase();
            
            const matchesSearch = normalizedSearch === '' || 
                                userId.includes(normalizedSearch) ||
                                fullName.includes(normalizedSearch) || 
                                email.includes(normalizedSearch) ||
                                role.includes(normalizedSearch) ||
                                officeUnit.includes(normalizedSearch) ||
                                devices.includes(normalizedSearch);
            
            const matchesRole = normalizedRole === '' || role.includes(normalizedRole);
            const matchesOfficeUnit = normalizedOfficeUnit === '' || officeUnit.includes(normalizedOfficeUnit);
            
            if (matchesSearch && matchesRole && matchesOfficeUnit) {
                row.style.display = '';
                visibleRows += 1;
            } else {
                row.style.display = 'none';
            }
        });

        ensureAssignmentsEmptyState(visibleRows);
        updateFilterChips(searchTerm, selectedRole, selectedOfficeUnit);
    }

    function openAssignmentsFilterSheet() {
        if (!assignmentsMobileFilterModal) return;
        syncFilterInputs(searchInput ? searchInput.value : '', getRoleValue(), getOfficeUnitValue());
        assignmentsMobileFilterModal.classList.add('is-open');
        assignmentsMobileFilterModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeAssignmentsFilterSheet() {
        if (!assignmentsMobileFilterModal) return;
        assignmentsMobileFilterModal.classList.remove('is-open');
        assignmentsMobileFilterModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    // Search input event listener
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            performSearch(this.value, getRoleValue(), getOfficeUnitValue());
        });
    }

    if (searchInputMobile) {
        searchInputMobile.addEventListener('input', function() {
            syncFilterInputs(this.value, getRoleValue(), getOfficeUnitValue());
            performSearch(this.value, getRoleValue(), getOfficeUnitValue());
        });
    }

    if (searchInputModal) {
        searchInputModal.addEventListener('input', function() {
            performSearch(this.value, roleFilterModal ? roleFilterModal.value : getRoleValue(), officeUnitFilterModal ? officeUnitFilterModal.value : getOfficeUnitValue());
        });
    }

    // Apply button event listener
    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            performSearch(searchInput ? searchInput.value : '', getRoleValue(), getOfficeUnitValue());
        });
    }

    if (applyAssignmentsFiltersMobile) {
        applyAssignmentsFiltersMobile.addEventListener('click', function() {
            performSearch(
                searchInputModal ? searchInputModal.value : '',
                roleFilterModal ? roleFilterModal.value : '',
                officeUnitFilterModal ? officeUnitFilterModal.value : ''
            );
            syncFilterInputs(
                searchInputModal ? searchInputModal.value : '',
                roleFilterModal ? roleFilterModal.value : '',
                officeUnitFilterModal ? officeUnitFilterModal.value : ''
            );
            closeAssignmentsFilterSheet();
        });
    }

    // Clear button event listener
    function clearAllAssignmentFilters() {
        syncFilterInputs('', '', '');
        tableRows.forEach(row => {
            row.style.display = '';
        });
        removeAssignmentsEmptyState();
        updateFilterChips('', '', '');
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', clearAllAssignmentFilters);
    }

    if (clearAssignmentsFiltersMobile) {
        clearAssignmentsFiltersMobile.addEventListener('click', clearAllAssignmentFilters);
    }

    // Filter change event listeners
    if (roleFilter) {
        roleFilter.addEventListener('change', function() {
            performSearch(searchInput ? searchInput.value : '', this.value, getOfficeUnitValue());
        });
    }

    if (officeUnitFilter) {
        officeUnitFilter.addEventListener('change', function() {
            performSearch(searchInput ? searchInput.value : '', getRoleValue(), this.value);
        });
    }

    if (roleFilterModal) {
        roleFilterModal.addEventListener('change', function() {
            performSearch(searchInputModal ? searchInputModal.value : '', this.value, officeUnitFilterModal ? officeUnitFilterModal.value : '');
        });
    }

    if (officeUnitFilterModal) {
        officeUnitFilterModal.addEventListener('change', function() {
            performSearch(searchInputModal ? searchInputModal.value : '', roleFilterModal ? roleFilterModal.value : '', this.value);
        });
    }

    if (openAssignmentsFiltersMobile) {
        openAssignmentsFiltersMobile.addEventListener('click', openAssignmentsFilterSheet);
    }

    if (assignmentsMobileFilterModal) {
        assignmentsMobileFilterModal.querySelectorAll('[data-close-assignments-filters="true"]').forEach(button => {
            button.addEventListener('click', closeAssignmentsFilterSheet);
        });
    }

    if (printAllQrMobile) {
        printAllQrMobile.addEventListener('click', function() {
            printAllQRCodes();
        });
    }

    // Details button handlers
    const detailsButtons = document.querySelectorAll('.btn-link');
    detailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const userId = row.querySelector('td:first-child').textContent;
            const fullName = row.querySelector('td:nth-child(2)').textContent;
            
            // Here you would typically open a modal or navigate to a details page
            alert(`View details for ${fullName} (ID: ${userId})`);
        });
    });

    // Print button handlers
    const printButtons = document.querySelectorAll('.btn-outline-dark:not(:contains("Print All"))');
    printButtons.forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const fullName = row.querySelector('td:nth-child(2)').textContent;
            
            // Here you would typically generate and print QR code
            alert(`Print QR code for ${fullName}`);
        });
    });

    // Print All QR Codes button
    const printAllBtn = document.querySelector('.btn-outline-dark');
    if (printAllBtn && printAllBtn.textContent.includes('Print All QR Codes')) {
        printAllBtn.addEventListener('click', function() {
            // Get visible rows count
            const visibleRows = Array.from(tableRows).filter(row => 
                row.style.display !== 'none'
            );
            
            if (visibleRows.length === 0) {
                alert('No users to print QR codes for');
                return;
            }
            
            alert(`Printing QR codes for ${visibleRows.length} users`);
        });
    }

    // Checkbox handlers
    const checkboxes = document.querySelectorAll('.form-check-input');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('tr');
            if (this.checked) {
                row.style.backgroundColor = '#e3f2fd';
            } else {
                row.style.backgroundColor = '';
            }
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Focus search on Ctrl+F
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            searchInput.focus();
        }
        
        // Clear filters on Escape
        if (e.key === 'Escape') {
            clearAllAssignmentFilters();
            if (searchInput) searchInput.blur();
        }
        
        // Apply filters on Ctrl+Enter
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            applyBtn.click();
        }
    });

    // Auto-resize table on window resize
    window.addEventListener('resize', function() {
        const table = document.querySelector('.table-responsive');
        // Force reflow to handle responsive table layout
        table.style.display = 'none';
        table.offsetHeight; // Trigger reflow
        table.style.display = '';
    });

    syncFilterInputs(searchInput ? searchInput.value : '', getRoleValue(), getOfficeUnitValue());
    updateFilterChips(searchInput ? searchInput.value : '', getRoleValue(), getOfficeUnitValue());

    console.log('Assignments page loaded successfully');
    console.log('Keyboard shortcuts: Ctrl+F (search), Ctrl+Enter (apply), Escape (clear)');
});

// Extracted from inline scripts in assignments.php
function printAllQRCodes() {
  const printWindow = window.open('', '_blank');

  // Collect user rows from the assignments table to build printable data
  const rows = document.querySelectorAll('#assignmentsTable tbody tr');
  const userData = [];
  rows.forEach(r => {
    const idCell = r.cells[0];
    if (!idCell) return;
    // Skip empty/no-data rows
    const possibleText = idCell.textContent.trim();
    if (!possibleText) return;
    const qrImg = r.querySelector('img.qr-code-image');
    const qrSrc = qrImg ? qrImg.src : '';
    const name = r.cells[1] ? r.cells[1].innerText.trim() : '';
    const unit = r.cells[4] ? r.cells[4].innerText.trim() : '';
    userData.push({ name, unit, qrSrc });
  });

  if (userData.length === 0) {
    alert('No QR codes found to print.');
    printWindow.close();
    return;
  }

  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>User QR Codes - CENRO NASIPIT</title>
      <style>
        @page { size: A4; margin: 10mm; }
        body {
          font-family: "Times New Roman", Times, serif;
          margin: 0;
          padding: 0;
          background: white;
        }
        .qr-grid {
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          grid-auto-rows: 86mm;
          gap: 4mm 6mm;
          margin: 0;
        }
        .qr-card {
          box-sizing: border-box;
          border: 1px solid #2c5530;
          padding: 5px;
          text-align: center;
          background: white;
          page-break-inside: avoid;
          width: 100%;
          height: 100%;
          display: flex;
          flex-direction: column;
          justify-content: flex-start;
          align-items: center;
        }
        .header {
          text-align: left;
          margin-bottom: 8px;
          display: flex;
          align-items: center;
          gap: 10px;
        }
        .denr-logo {
          width: 40px;
          height: 40px;
          object-fit: contain;
        }
        .header-text {
          flex: 1;
          text-align: left;
        }
        .header h3 {
          color: #000;
          margin: 0;
          font-size: 11px;
          font-weight: bold;
          font-family: "Times New Roman", Times, serif;
        }
        .header h4 {
          color: #000;
          margin: 2px 0 0 0;
          font-size: 10px;
          font-weight: normal;
          font-family: "Times New Roman", Times, serif;
        }
        .property-title {
          background: #2c5530;
          color: white;
          padding: 6px;
          margin: 8px 0 12px 0;
          font-weight: bold;
          font-size: 12px;
          letter-spacing: 1px;
          width: 100%;
          font-family: "Times New Roman", Times, serif;
        }
        .qr-code {
          margin: 8px 0;
        }
        .qr-code img {
          width: 36mm;
          height: 36mm;
          border: 1px solid #ccc;
        }
        .user-info {
          margin-top: 8px;
        }
        .user-name {
          font-weight: bold;
          font-size: 13px;
          color: #2c5530;
          margin-bottom: 4px;
          text-transform: uppercase;
          font-family: "Times New Roman", Times, serif;
        }
        .unit-name {
          font-size: 10px;
          color: #666;
          font-style: italic;
          font-family: "Times New Roman", Times, serif;
        }
        @media print {
          body { margin: 0; padding: 0; }
          .qr-grid { gap: 4mm 6mm; }
          .qr-card { page-break-inside: avoid; }
        }
      </style>
    </head>
    <body>
      <div class="qr-grid">
  `);

  // Generate QR code cards
  userData.forEach(user => {
    printWindow.document.write(`
      <div class="qr-card">
        <div class="header">
          <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo" class="denr-logo">
          <div class="header-text">
            <div style="font-weight:bold;font-size:11px;">Department of Environment and Natural Resources</div>
            <div style="font-size:10px;margin-top:2px;">Community Environment and Natural Resources Office</div>
            <div style="font-size:10px;margin-top:2px;">CENRO Nasipit, Agusan del Norte</div>
          </div>
        </div>

        <div class="property-title">RP GOVERNMENT PROPERTY</div>

        <div class="qr-code">
          <img src="${user.qrSrc}" alt="QR Code">
        </div>

        <div class="user-info">
          <div class="user-name">${user.name}</div>
          <div class="unit-name">${user.unit}</div>
        </div>
      </div>
    `);
  });

  printWindow.document.write(`
      </div>
    </body>
    </html>
  `);

  printWindow.document.close();

  // Wait for images to load before printing
  setTimeout(() => {
    printWindow.print();
  }, 1000);
}

function printAssignedDevices(userId) {
  if (!userId) return;

  // Find the table row for this user to extract the QR src, full name and office/unit
  const rows = document.querySelectorAll('#assignmentsTable tbody tr');
  let targetRow = null;
  rows.forEach(r => {
    const idCell = r.cells[0];
    if (idCell && idCell.textContent.trim() === String(userId)) targetRow = r;
  });

  if (!targetRow) {
    alert('User row not found.');
    return;
  }

  const qrImg = targetRow.querySelector('img.qr-code-image');
  const qrSrc = qrImg ? qrImg.src : '';
  const fullName = (targetRow.cells[1] && targetRow.cells[1].innerText) ? targetRow.cells[1].innerText.trim() : '';
  const office = (targetRow.cells[4] && targetRow.cells[4].innerText) ? targetRow.cells[4].innerText.trim() : '';

  const w = window.open('', '_blank');
  if (!w) {
    alert('Popup blocked. Please allow popups for this site to print.');
    return;
  }

  const html = `<!doctype html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>QR Sticker - ${escapeHtml(fullName)}</title>
      <style>
        @page { size: A4 portrait; margin: 10mm; }
        body { font-family: "Times New Roman", Times, serif; margin: 0; padding: 0; background: #fff; color: #000; }
        .sticker-wrap { width: 70mm; height: 90mm; margin: 12mm auto; border: 2px solid #2c5530; padding: 4mm; box-sizing: border-box; }
        .sticker-header { display:flex; gap:6px; align-items:flex-start; }
        .sticker-logo img { width: 14mm; height: 14mm; object-fit:contain; }
        .sticker-text { flex:1; text-align:center; font-size:9px; line-height:1.05; }
        .sticker-text .line1 { font-weight:bold; font-size:10px; text-transform:uppercase; }
        .sticker-text .line2 { font-size:8px; }
        .property-title { background:#2c5530; color:#fff; padding:3px 6px; margin:6px 0; font-weight:bold; font-size:9px; letter-spacing:1px; text-align:center; }
        .qr-block { text-align:center; margin-top:4mm; }
        .qr-block img { width: 36mm; height: 36mm; object-fit:contain; border:1px solid #ddd; padding:2px; background:#fff; }
        .info { text-align:center; margin-top:4mm; }
        .info .name { font-weight:bold; font-size:9px; text-transform:uppercase; color:#2c5530; }
        .info .unit { font-size:8px; font-style:italic; color:#444; }
        @media print {
          body { margin: 0; padding: 0; }
          .sticker-wrap { margin: 0; }
        }
      </style>
    </head>
    <body>
      <div class="sticker-wrap">
        <div class="sticker-header">
          <div class="sticker-logo"><img src="../../../../public/assets/images/denr-logo.png" alt="DENR"></div>
          <div class="sticker-text">
            <div class="line1">Department of Environment and Natural Resources</div>
            <div class="line2">Community Environment and Natural Resources Office</div>
            <div class="line2">CENRO Nasipit, Agusan del Norte</div>
          </div>
        </div>

        <div class="property-title">RP GOVERNMENT PROPERTY</div>

        <div class="qr-block">
          <img src="${qrSrc}" alt="QR">
        </div>

        <div class="info">
          <div class="name">${escapeHtml(fullName)}</div>
          <div class="unit">${escapeHtml(office)}</div>
        </div>
      </div>
    </body>
    </html>`;

  w.document.open();
  w.document.write(html);
  w.document.close();

  // Give images a moment to load then print
  setTimeout(() => {
    try {
      w.focus();
      w.print();
    } catch (e) {}
  }, 600);
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

