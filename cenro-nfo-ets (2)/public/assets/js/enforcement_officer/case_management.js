document.addEventListener('DOMContentLoaded', function() {
  const actionButtons = document.querySelectorAll('.btn-outline-secondary');
  actionButtons.forEach((btn) => {
    btn.addEventListener('mouseenter', function() {
      this.style.transform = 'scale(1.1)';
      this.style.transition = 'transform 0.2s ease';
    });

    btn.addEventListener('mouseleave', function() {
      this.style.transform = 'scale(1)';
    });
  });

  const applyFilterBtn = document.getElementById('applyFilter');
  const clearFilterBtn = document.getElementById('clearFilter');
  const applyFilterBtnMobile = document.getElementById('applyFilterMobile');
  const clearFilterBtnMobile = document.getElementById('clearFilterMobile');
  const searchInput = document.getElementById('searchInput');
  const searchInputMobile = document.getElementById('searchInputMobile');
  const searchInputModal = document.getElementById('searchInputModal');
  const dateFromInput = document.getElementById('dateFrom');
  const dateToInput = document.getElementById('dateTo');
  const statusFilterInput = document.getElementById('statusFilter');
  const dateFromModalInput = document.getElementById('dateFromModal');
  const dateToModalInput = document.getElementById('dateToModal');
  const statusFilterModalInput = document.getElementById('statusFilterModal');
  const activeFilters = document.getElementById('caseManagementActiveFiltersOfficer');
  const filtersModal = document.getElementById('caseManagementFiltersModalOfficer');

  function escapeHtml(value) {
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function setAllSearch(value) {
    if (searchInput) searchInput.value = value;
    if (searchInputMobile) searchInputMobile.value = value;
    if (searchInputModal) searchInputModal.value = value;
  }

  function setAllDates(fromValue, toValue) {
    if (dateFromInput) dateFromInput.value = fromValue;
    if (dateToInput) dateToInput.value = toValue;
    if (dateFromModalInput) dateFromModalInput.value = fromValue;
    if (dateToModalInput) dateToModalInput.value = toValue;
  }

  function setAllStatuses(value) {
    if (statusFilterInput) statusFilterInput.value = value;
    if (statusFilterModalInput) statusFilterModalInput.value = value;
  }

  function syncFromDesktop() {
    setAllSearch(searchInput ? searchInput.value : '');
    setAllDates(dateFromInput ? dateFromInput.value : '', dateToInput ? dateToInput.value : '');
    setAllStatuses(statusFilterInput ? statusFilterInput.value : '');
  }

  function syncFromModal() {
    setAllSearch(searchInputModal ? searchInputModal.value : '');
    setAllDates(dateFromModalInput ? dateFromModalInput.value : '', dateToModalInput ? dateToModalInput.value : '');
    setAllStatuses(statusFilterModalInput ? statusFilterModalInput.value : '');
  }

  function applyRealtimeFromModal() {
    syncFromModal();
    applyFilters();
  }

  function renderActiveFilters() {
    if (!activeFilters) return;
    activeFilters.innerHTML = '';
    activeFilters.style.display = 'none';
  }

  function parseDateOnly(str) {
    if (!str) return null;
    const match = str.match(/(\d{4}-\d{2}-\d{2})/);
    if (match) return new Date(match[1]);
    const parsedDate = new Date(str);
    return Number.isNaN(parsedDate.getTime()) ? null : parsedDate;
  }

  function applyFilters() {
    const searchTerm = (searchInput ? searchInput.value : '').trim().toLowerCase();
    const dateFromVal = dateFromInput ? dateFromInput.value : '';
    const dateToVal = dateToInput ? dateToInput.value : '';
    const statusVal = (statusFilterInput ? statusFilterInput.value : '').trim().toLowerCase();
    const dateFrom = dateFromVal ? new Date(dateFromVal) : null;
    const dateTo = dateToVal ? new Date(dateToVal) : null;

    if (dateTo) dateTo.setHours(23, 59, 59, 999);

    const rows = document.querySelectorAll('#casesTableBody tr');
    let visibleCount = 0;
    const counts = {
      'under-investigation': 0,
      'pending-review': 0,
      'for-filing': 0,
      'filed-in-court': 0,
      'ongoing-trial': 0,
      'resolved': 0,
      'dismissed': 0,
      'archived': 0,
      'on-hold': 0,
      'under-appeal': 0
    };

    rows.forEach((row) => {
      if (row.querySelector('td') && row.querySelector('td').getAttribute('colspan')) return;
      const cells = row.cells;
      if (!cells) return;

      const ref = (cells[0].textContent || '').toLowerCase();
      const incidentText = (cells[1].textContent || '').trim();
      const location = (cells[2].textContent || '').toLowerCase();
      const teamLeader = (cells[3] ? cells[3].textContent || '' : '').toLowerCase();
      const submittedBy = (cells[4] ? cells[4].textContent || '' : '').toLowerCase();
      const reviewText = (cells[5] ? cells[5].textContent || '' : '').toLowerCase();
      const caseStatusText = (cells[6] ? cells[6].textContent || '' : '').toLowerCase();

      let visible = true;

      if (searchTerm) {
        const haystack = ref + ' ' + location + ' ' + teamLeader + ' ' + submittedBy + ' ' + reviewText + ' ' + caseStatusText;
        if (!haystack.includes(searchTerm)) visible = false;
      }

      if (visible && (dateFrom || dateTo)) {
        const incidentDate = parseDateOnly(incidentText);
        if (!incidentDate) visible = false;
        if (incidentDate && dateFrom && incidentDate < dateFrom) visible = false;
        if (incidentDate && dateTo && incidentDate > dateTo) visible = false;
      }

      if (visible && statusVal) {
        const normalizedStatus = statusVal.replace(/_/g, ' ');
        if (!caseStatusText.includes(normalizedStatus)) visible = false;
      }

      if (visible) {
        row.style.display = '';
        visibleCount++;

        if (caseStatusText.includes('under') || caseStatusText.includes('invest')) counts['under-investigation']++;
        else if (caseStatusText.includes('pending')) counts['pending-review']++;
        else if (caseStatusText.includes('for filing') || caseStatusText.includes('for-filing')) counts['for-filing']++;
        else if (caseStatusText.includes('filed') || caseStatusText.includes('filed in court') || caseStatusText.includes('filed-in-court')) counts['filed-in-court']++;
        else if (caseStatusText.includes('ongoing') || caseStatusText.includes('trial')) counts['ongoing-trial']++;
        else if (caseStatusText.includes('dismiss')) counts['dismissed']++;
        else if (caseStatusText.includes('resolv')) counts['resolved']++;
        else if (caseStatusText.includes('archiv')) counts['archived']++;
        else if (caseStatusText.includes('hold')) counts['on-hold']++;
        else if (caseStatusText.includes('appeal')) counts['under-appeal']++;
      } else {
        row.style.display = 'none';
      }
    });

    document.getElementById('count-under-investigation').textContent = counts['under-investigation'];
    document.getElementById('count-pending-review').textContent = counts['pending-review'];
    document.getElementById('count-for-filing').textContent = counts['for-filing'];
    document.getElementById('count-filed-in-court').textContent = counts['filed-in-court'];
    document.getElementById('count-ongoing-trial').textContent = counts['ongoing-trial'];
    document.getElementById('count-resolved').textContent = counts['resolved'];
    document.getElementById('count-dismissed').textContent = counts['dismissed'];
    document.getElementById('count-archived').textContent = counts['archived'];
    document.getElementById('count-on-hold').textContent = counts['on-hold'];
    document.getElementById('count-under-appeal').textContent = counts['under-appeal'];

    const tbody = document.getElementById('casesTableBody');
    if (tbody) {
      const placeholder = tbody.querySelector('tr[data-placeholder]');
      if (visibleCount === 0) {
        if (!placeholder) {
          const row = document.createElement('tr');
          row.setAttribute('data-placeholder', '1');
          row.innerHTML = '<td colspan="10" class="text-center">No approved cases found.</td>';
          tbody.appendChild(row);
        }
      } else if (placeholder) {
        placeholder.remove();
      }
    }

    renderActiveFilters();
  }

  if (applyFilterBtn) {
    applyFilterBtn.addEventListener('click', function() {
      syncFromDesktop();
      applyFilters();
    });
  }

  if (clearFilterBtn) {
    clearFilterBtn.addEventListener('click', function() {
      setAllSearch('');
      setAllDates('', '');
      setAllStatuses('');
      applyFilters();
    });
  }

  if (applyFilterBtnMobile) {
    applyFilterBtnMobile.addEventListener('click', function() {
      syncFromModal();
      applyFilters();
    });
  }

  if (clearFilterBtnMobile) {
    clearFilterBtnMobile.addEventListener('click', function() {
      setAllSearch('');
      setAllDates('', '');
      setAllStatuses('');
      applyFilters();
    });
  }

  if (searchInputModal) {
    searchInputModal.addEventListener('input', function() {
      applyRealtimeFromModal();
    });
  }

  if (dateFromModalInput) {
    dateFromModalInput.addEventListener('input', function() {
      applyRealtimeFromModal();
    });
    dateFromModalInput.addEventListener('change', function() {
      applyRealtimeFromModal();
    });
  }

  if (dateToModalInput) {
    dateToModalInput.addEventListener('input', function() {
      applyRealtimeFromModal();
    });
    dateToModalInput.addEventListener('change', function() {
      applyRealtimeFromModal();
    });
  }

  if (statusFilterModalInput) {
    statusFilterModalInput.addEventListener('input', function() {
      applyRealtimeFromModal();
    });
    statusFilterModalInput.addEventListener('change', function() {
      applyRealtimeFromModal();
    });
  }

  if (searchInput) {
    searchInput.addEventListener('keydown', function(event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        syncFromDesktop();
        applyFilters();
      }
    });
  }

  if (searchInputMobile) {
    searchInputMobile.addEventListener('input', function() {
      setAllSearch(searchInputMobile.value);
      applyFilters();
    });
  }

  if (filtersModal) {
    filtersModal.addEventListener('show.bs.modal', function() {
      syncFromDesktop();
    });
  }

  syncFromDesktop();
  renderActiveFilters();
});
