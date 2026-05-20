document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.report-section table').forEach(function(table) {
    var headers = Array.from(table.querySelectorAll('thead th')).map(function(th) {
      return th.textContent.trim();
    });

    if (!headers.length) return;

    table.querySelectorAll('tbody tr').forEach(function(row) {
      Array.from(row.children).forEach(function(cell, index) {
        if (headers[index] && !cell.hasAttribute('data-label')) {
          cell.setAttribute('data-label', headers[index]);
        }
      });
    });
  });
});
