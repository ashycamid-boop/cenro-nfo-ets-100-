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

  var searchInput = document.getElementById('allRequestsSearch');
  var searchMobileInput = document.getElementById('allRequestsSearchMobile');
  var searchModalInput = document.getElementById('allRequestsSearchModal');
  var dateFromInput = document.getElementById('date_from');
  var dateToInput = document.getElementById('date_to');
  var dateFromModalInput = document.getElementById('allRequestsDateFromModal');
  var dateToModalInput = document.getElementById('allRequestsDateToModal');
  var clearBtn = document.getElementById('clearFilter');
  var clearBtnMobile = document.getElementById('clearFilterMobile');
  var activeFilters = document.getElementById('allRequestsActiveFilters');
  var filtersModal = document.getElementById('allRequestsFiltersModal');
  var table = document.getElementById('allRequestsTable');
  var tbody = table ? table.tBodies[0] : null;

  function escapeHtml(value) {
    return value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
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

    if (tbody) {
      Array.from(tbody.rows).forEach(function (row) {
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
        if ((fromDate || toDate) && !rowDate) matchesDate = false;

        row.style.display = matchesSearch && matchesDate ? '' : 'none';
      });
    }
    renderActiveFilters();
  }

  function clearFilter() {
    setAllSearch('');
    setAllDates('', '');
    if (tbody) Array.from(tbody.rows).forEach(function (row) { row.style.display = ''; });
    renderActiveFilters();
  }

  if (clearBtn) clearBtn.addEventListener('click', function (e) { e.preventDefault(); clearFilter(); });

  if (clearBtnMobile) clearBtnMobile.addEventListener('click', function (e) { e.preventDefault(); clearFilter(); });

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
    input.addEventListener('input', applyFilter);
    input.addEventListener('change', applyFilter);
  });

  [dateFromModalInput, dateToModalInput].forEach(function (input) {
    if (!input) return;
    function filterFromModal() {
      syncFromModal();
      applyFilter();
    }
    input.addEventListener('input', filterFromModal);
    input.addEventListener('change', filterFromModal);
  });

  if (filtersModal) {
    filtersModal.addEventListener('show.bs.modal', function () {
      syncFromDesktop();
    });
  }

  syncFromDesktop();
  renderActiveFilters();
});
