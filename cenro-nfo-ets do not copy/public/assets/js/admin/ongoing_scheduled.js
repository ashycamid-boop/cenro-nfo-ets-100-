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

  var searchInput = document.getElementById('ongoingScheduledSearch');
  var searchMobileInput = document.getElementById('ongoingScheduledSearchMobile');
  var searchModalInput = document.getElementById('ongoingScheduledSearchModal');
  var dateFromInput = document.getElementById('date_from');
  var dateToInput = document.getElementById('date_to');
  var dateFromModalInput = document.getElementById('ongoingScheduledDateFromModal');
  var dateToModalInput = document.getElementById('ongoingScheduledDateToModal');
  var applyBtn = document.getElementById('applyFilter');
  var clearBtn = document.getElementById('clearFilter');
  var applyBtnMobile = document.getElementById('applyFilterMobile');
  var clearBtnMobile = document.getElementById('clearFilterMobile');
  var activeFilters = document.getElementById('ongoingScheduledActiveFilters');
  var table = document.getElementById('ongoingRequestsTable');
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

        if (matchesSearch && matchesDate) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
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

  if (applyBtn) applyBtn.addEventListener('click', function (e) { e.preventDefault(); applyFilter(); });
  if (clearBtn) clearBtn.addEventListener('click', function (e) { e.preventDefault(); clearFilter(); });

  if (applyBtnMobile) applyBtnMobile.addEventListener('click', function (e) { e.preventDefault(); syncFromModal(); applyFilter(); });
  if (clearBtnMobile) clearBtnMobile.addEventListener('click', function (e) { e.preventDefault(); clearFilter(); });

  if (searchMobileInput) {
    searchMobileInput.addEventListener('input', function () {
      setAllSearch(searchMobileInput.value);
      applyFilter();
    });
  }

  syncFromDesktop();
  renderActiveFilters();
});
