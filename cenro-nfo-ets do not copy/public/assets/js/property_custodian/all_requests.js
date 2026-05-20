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

  var applyBtn = document.getElementById('applyFilter');
  var clearBtn = document.getElementById('clearFilter');
  var table = document.getElementById('allRequestsTable');
  var tbody = table ? table.tBodies[0] : null;

  function applyFilter() {
    var search = (document.querySelector('input[type="text"].form-control') || { value: '' }).value.trim().toLowerCase();
    var fromStr = document.getElementById('date_from').value.trim();
    var toStr = document.getElementById('date_to').value.trim();
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
  }

  function clearFilter() {
    var searchInput = document.querySelector('input[type="text"].form-control');
    if (searchInput) searchInput.value = '';
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value = '';
    if (tbody) Array.from(tbody.rows).forEach(function (row) { row.style.display = ''; });
  }

  if (applyBtn) applyBtn.addEventListener('click', function (e) { e.preventDefault(); applyFilter(); });
  if (clearBtn) clearBtn.addEventListener('click', function (e) { e.preventDefault(); clearFilter(); });
});
