// Filtering and datepicker initialization extracted from new_requests.php
document.addEventListener('DOMContentLoaded', function () {
  if (window.flatpickr) {
    flatpickr('.date-picker', { dateFormat: 'm/d/Y', allowInput: true });
  }

  function parseMDY(str) {
    if (!str) return null;
    var parts = str.split('/');
    if (parts.length !== 3) return null;
    var m = parseInt(parts[0], 10) - 1;
    var d = parseInt(parts[1], 10);
    var y = parseInt(parts[2], 10);
    if (isNaN(m) || isNaN(d) || isNaN(y)) return null;
    return new Date(y, m, d);
  }

  var searchInput = document.getElementById('newRequestsSearch');
  var searchMobileInput = document.getElementById('newRequestsSearchMobile');
  var searchModalInput = document.getElementById('newRequestsSearchModal');
  var dateFromInput = document.getElementById('date_from');
  var dateToInput = document.getElementById('date_to');
  var dateFromModalInput = document.getElementById('newRequestsDateFromModal');
  var dateToModalInput = document.getElementById('newRequestsDateToModal');
  var applyBtn = document.getElementById('applyFilter');
  var clearBtn = document.getElementById('clearFilter');
  var applyBtnMobile = document.getElementById('applyFilterMobile');
  var clearBtnMobile = document.getElementById('clearFilterMobile');
  var activeFilters = document.getElementById('newRequestsActiveFilters');
  var filtersModal = document.getElementById('newRequestsFiltersModal');
  var table = document.getElementById('newRequestsTable');
  var tbody = table ? table.tBodies[0] : null;
  var baseRows = tbody ? Array.from(tbody.rows).filter(function (row) { return row.cells && row.cells.length > 1; }) : [];

  function escapeHtml(value) {
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function setAllSearch(value) {
    if (searchInput) searchInput.value = value;
    if (searchMobileInput) searchMobileInput.value = value;
    if (searchModalInput) searchModalInput.value = value;
  }

  function setAllDates(fromValue, toValue) {
    if (dateFromInput) dateFromInput.value = fromValue;
    if (dateToInput) dateToInput.value = toValue;
    if (dateFromModalInput) dateFromModalInput.value = fromValue;
    if (dateToModalInput) dateToModalInput.value = toValue;
  }

  function syncFromDesktop() {
    setAllSearch(searchInput ? searchInput.value : '');
    setAllDates(dateFromInput ? dateFromInput.value : '', dateToInput ? dateToInput.value : '');
  }

  function syncFromModal() {
    setAllSearch(searchModalInput ? searchModalInput.value : '');
    setAllDates(dateFromModalInput ? dateFromModalInput.value : '', dateToModalInput ? dateToModalInput.value : '');
  }

  function removeEmptyState() {
    if (!tbody) return;
    var existing = tbody.querySelector('tr[data-empty-state="true"]');
    if (existing) existing.remove();
  }

  function ensureEmptyState(visibleRows) {
    if (!tbody) return;
    removeEmptyState();
    if (visibleRows > 0) return;
    var row = document.createElement('tr');
    row.setAttribute('data-empty-state', 'true');
    row.innerHTML = '<td colspan="9" class="text-center">No pending requests.</td>';
    tbody.appendChild(row);
  }

  function renderActiveFilters() {
    if (!activeFilters) return;
    activeFilters.innerHTML = '';
    activeFilters.style.display = 'none';
  }

  function applyFilter() {
    var search = (searchInput || { value: '' }).value.trim().toLowerCase();
    var fromStr = dateFromInput ? dateFromInput.value.trim() : '';
    var toStr = dateToInput ? dateToInput.value.trim() : '';
    var fromDate = parseMDY(fromStr);
    var toDate = parseMDY(toStr);
    var visibleRows = 0;

    if (tbody) {
      removeEmptyState();
      baseRows.forEach(function (row) {
        var cells = row.cells;
        if (!cells || cells.length < 2) return;
        var dateText = cells[1].innerText.trim();
        var rowDate = parseMDY(dateText);
        var textContent = row.innerText.toLowerCase();

        var matchesSearch = !search || textContent.indexOf(search) !== -1;
        var matchesDate = true;
        if (fromDate && rowDate) {
          matchesDate = rowDate >= fromDate;
        }
        if (toDate && rowDate && matchesDate) {
          matchesDate = rowDate <= toDate;
        }
        // If user entered a date range but row has no parsable date, treat as non-matching
        if ((fromDate || toDate) && !rowDate) matchesDate = false;

        if (matchesSearch && matchesDate) {
          row.style.display = '';
          visibleRows += 1;
        } else {
          row.style.display = 'none';
        }
      });
      ensureEmptyState(visibleRows);
    }
    renderActiveFilters();
  }

  function clearFilter() {
    setAllSearch('');
    setAllDates('', '');
    if (baseRows.length) {
      baseRows.forEach(function (row) {
        row.style.display = '';
      });
    }
    removeEmptyState();
    renderActiveFilters();
  }

  if (applyBtn) {
    applyBtn.addEventListener('click', function (e) {
      e.preventDefault();
      applyFilter();
    });
  }
  if (clearBtn) {
    clearBtn.addEventListener('click', function (e) {
      e.preventDefault();
      clearFilter();
    });
  }

  if (applyBtnMobile) {
    applyBtnMobile.addEventListener('click', function (e) {
      e.preventDefault();
      syncFromModal();
      applyFilter();
    });
  }

  if (clearBtnMobile) {
    clearBtnMobile.addEventListener('click', function (e) {
      e.preventDefault();
      clearFilter();
    });
  }

  if (searchMobileInput) {
    searchMobileInput.addEventListener('input', function () {
      setAllSearch(searchMobileInput.value);
      applyFilter();
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      setAllSearch(searchInput.value);
      applyFilter();
    });
  }

  if (searchModalInput) {
    searchModalInput.addEventListener('input', function () {
      syncFromModal();
      applyFilter();
    });
  }

  [dateFromInput, dateToInput].forEach(function (input) {
    if (!input) return;
    input.addEventListener('change', applyFilter);
  });

  [dateFromModalInput, dateToModalInput].forEach(function (input) {
    if (!input) return;
    input.addEventListener('change', function () {
      syncFromModal();
      applyFilter();
    });
  });

  if (filtersModal) {
    filtersModal.addEventListener('show.bs.modal', function () {
      syncFromDesktop();
    });
  }

  syncFromDesktop();
  renderActiveFilters();
});
