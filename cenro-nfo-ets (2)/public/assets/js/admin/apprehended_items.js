document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('searchInput');
  const searchInputMobile = document.getElementById('searchInputMobile');
  const searchInputModal = document.getElementById('searchInputModal');
  const filterButtons = document.querySelectorAll('.btn-filter');
  const mobileItemFilter = document.getElementById('mobileItemFilter');
  const mobileItemFilterModal = document.getElementById('mobileItemFilterModal');
  const applyApprehendedFiltersMobile = document.getElementById('applyApprehendedFiltersMobile');
  const clearApprehendedFiltersMobile = document.getElementById('clearApprehendedFiltersMobile');
  const activeFilters = document.getElementById('apprehendedItemsActiveFiltersAdmin');
  const itemsTable = document.getElementById('itemsTable');
  const filtersModal = document.getElementById('apprehendedItemsFiltersModalAdmin');

  if (!itemsTable) return;

  const tableBody = itemsTable.querySelector('tbody');
  const tableRows = Array.from(itemsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

  function normalizeDimensionPresentation() {
    const dimensionLabel = 'Dimension (T \u00D7 W \u00D7 L)';
    const mangledTimesPattern = /\u00C3[\u0097\u2014]/g;

    itemsTable.querySelectorAll('thead th').forEach((cell) => {
      if (cell.textContent.toLowerCase().includes('dimension')) {
        cell.textContent = dimensionLabel;
      }
    });

    itemsTable.querySelectorAll('tbody td[data-label]').forEach((cell) => {
      const dataLabel = cell.getAttribute('data-label') || '';
      if (dataLabel.toLowerCase().includes('dimension')) {
        cell.setAttribute('data-label', dimensionLabel);

        if (cell.childElementCount === 0 && cell.textContent.indexOf('\u00C3') !== -1) {
          cell.textContent = cell.textContent.replace(mangledTimesPattern, '\u00D7');
        }
      }
    });
  }

  function escapeHtml(value) {
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function getCurrentFilter() {
    if (mobileItemFilterModal && mobileItemFilterModal.closest('.modal') && mobileItemFilterModal.closest('.modal').classList.contains('show')) {
      return mobileItemFilterModal.value || 'all';
    }
    if (mobileItemFilter && window.getComputedStyle(mobileItemFilter).display !== 'none') {
      return mobileItemFilter.value || 'all';
    }
    const activeButton = document.querySelector('.btn-filter.active');
    return activeButton ? activeButton.getAttribute('data-filter') : 'all';
  }

  function setAllSearch(value) {
    if (searchInput) searchInput.value = value;
    if (searchInputMobile) searchInputMobile.value = value;
    if (searchInputModal) searchInputModal.value = value;
  }

  function setAllFilters(value) {
    if (mobileItemFilter) mobileItemFilter.value = value;
    if (mobileItemFilterModal) mobileItemFilterModal.value = value;
    filterButtons.forEach((btn) => {
      const isActive = btn.getAttribute('data-filter') === value;
      btn.classList.toggle('active', isActive);
    });
  }

  function syncFromDesktop() {
    setAllSearch(searchInput ? searchInput.value : '');
    setAllFilters(getCurrentFilter());
  }

  function syncFromModal() {
    setAllSearch(searchInputModal ? searchInputModal.value : '');
    setAllFilters(mobileItemFilterModal ? mobileItemFilterModal.value || 'all' : 'all');
  }

  function applyRealtimeFromModal() {
    syncFromModal();
    filterTable((searchInput ? searchInput.value : '').toLowerCase(), getCurrentFilter());
  }

  function renderActiveFilters() {
    if (!activeFilters) return;
    activeFilters.innerHTML = '';
    activeFilters.style.display = 'none';
  }

  function showEmptyState(show) {
    let emptyState = tableBody.querySelector('.empty-state-row');

    if (show && !emptyState) {
      emptyState = document.createElement('tr');
      emptyState.className = 'empty-state-row';
      emptyState.innerHTML = '<td colspan="8" class="empty-state"><i class="fa fa-search"></i><h5>No items found</h5><p>Try adjusting your search or filter criteria</p></td>';
      tableBody.appendChild(emptyState);
    } else if (!show && emptyState) {
      emptyState.remove();
    }
  }

  function filterTable(searchTerm, filter) {
    let visibleRows = 0;

    tableRows.forEach((row) => {
      const text = row.textContent.toLowerCase();
      const type = row.getAttribute('data-type');
      const matchesSearch = searchTerm === '' || text.includes(searchTerm);
      const matchesFilter = filter === 'all' || type === filter;

      row.style.display = matchesSearch && matchesFilter ? '' : 'none';
      if (matchesSearch && matchesFilter) visibleRows++;
    });

    showEmptyState(visibleRows === 0);
    renderActiveFilters();
  }

  if (searchInput) {
    searchInput.addEventListener('input', function() {
      setAllSearch(this.value);
      filterTable(this.value.toLowerCase(), getCurrentFilter());
    });
  }

  if (searchInputMobile) {
    searchInputMobile.addEventListener('input', function() {
      setAllSearch(this.value);
      filterTable(this.value.toLowerCase(), getCurrentFilter());
    });
  }

  filterButtons.forEach((button) => {
    button.addEventListener('click', function() {
      const filter = this.getAttribute('data-filter');
      setAllFilters(filter);
      filterTable((searchInput ? searchInput.value : '').toLowerCase(), filter);
    });
  });

  if (mobileItemFilter) {
    mobileItemFilter.addEventListener('change', function() {
      const filter = this.value || 'all';
      setAllFilters(filter);
      filterTable((searchInput ? searchInput.value : '').toLowerCase(), filter);
    });
  }

  if (applyApprehendedFiltersMobile) {
    applyApprehendedFiltersMobile.addEventListener('click', function() {
      syncFromModal();
      filterTable((searchInput ? searchInput.value : '').toLowerCase(), getCurrentFilter());
    });
  }

  if (clearApprehendedFiltersMobile) {
    clearApprehendedFiltersMobile.addEventListener('click', function() {
      setAllSearch('');
      setAllFilters('all');
      filterTable('', 'all');
    });
  }

  if (searchInputModal) {
    searchInputModal.addEventListener('input', function() {
      applyRealtimeFromModal();
    });
  }

  if (mobileItemFilterModal) {
    mobileItemFilterModal.addEventListener('input', function() {
      applyRealtimeFromModal();
    });
    mobileItemFilterModal.addEventListener('change', function() {
      applyRealtimeFromModal();
    });
  }

  if (filtersModal) {
    filtersModal.addEventListener('show.bs.modal', function() {
      syncFromDesktop();
    });
  }

  document.addEventListener('keydown', function(event) {
    if (event.ctrlKey && event.key === 'f' && searchInput) {
      event.preventDefault();
      searchInput.focus();
    }

    if (event.key === 'Escape' && searchInput) {
      setAllSearch('');
      filterTable('', getCurrentFilter());
      searchInput.blur();
    }
  });

  syncFromDesktop();
  normalizeDimensionPresentation();
  renderActiveFilters();
});
