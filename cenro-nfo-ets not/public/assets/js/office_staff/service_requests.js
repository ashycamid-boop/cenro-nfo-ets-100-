document.addEventListener('DOMContentLoaded', function () {
  initializeProfileDropdown();
  initializeServiceRequestFilters();
});

function initializeProfileDropdown() {
  const profileCard = document.getElementById('profileCard');
  const profileDropdown = document.getElementById('profileDropdown');

  if (!profileCard || !profileDropdown) return;

  let dropdownOpen = false;

  function toggleDropdown() {
    dropdownOpen = !dropdownOpen;
    profileDropdown.style.display = dropdownOpen ? 'flex' : 'none';
  }

  profileCard.addEventListener('click', function (e) {
    toggleDropdown();
    e.stopPropagation();
  });

  document.addEventListener('click', function (e) {
    if (!profileCard.contains(e.target)) {
      dropdownOpen = false;
      profileDropdown.style.display = 'none';
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && dropdownOpen) {
      dropdownOpen = false;
      profileDropdown.style.display = 'none';
    }
  });
}

function initializeServiceRequestFilters() {
  const searchDesktop = document.getElementById('srDesktopSearch');
  const searchMobileToolbar = document.querySelector('.sr-mobile-search-form input[name="search"]');
  const searchMobileModal = document.getElementById('srMobileSearch');
  const dateFromDesktop = document.getElementById('srDesktopDateFrom');
  const dateToDesktop = document.getElementById('srDesktopDateTo');
  const dateFromMobile = document.getElementById('srMobileDateFrom');
  const dateToMobile = document.getElementById('srMobileDateTo');
  const activeFilters = document.getElementById('serviceRequestActiveFilters');
  const mobileClearAll = document.getElementById('srMobileClearAll');
  const tableBody = document.querySelector('.sr-table tbody');
  const filtersModal = document.getElementById('serviceRequestFiltersModal');
  const desktopForm = document.querySelector('.sr-filters-desktop');
  const mobileSearchForm = document.querySelector('.sr-mobile-search-form');
  const modalForm = filtersModal ? filtersModal.querySelector('form') : null;

  if (!tableBody) return;

  const rows = Array.from(tableBody.querySelectorAll('tr')).filter((row) => !row.querySelector('td[colspan]'));
  const originalEmptyRow = tableBody.querySelector('td[colspan]') ? tableBody.querySelector('tr') : null;

  function normalize(value) {
    return (value || '').toString().trim().toLowerCase();
  }

  function parseDate(value) {
    if (!value) return null;
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  function currentFilters() {
    return {
      search: (searchMobileToolbar?.value || searchDesktop?.value || searchMobileModal?.value || '').trim(),
      dateFrom: dateFromDesktop?.value || dateFromMobile?.value || '',
      dateTo: dateToDesktop?.value || dateToMobile?.value || ''
    };
  }

  function syncValues(source, value) {
    const controls = [searchDesktop, searchMobileToolbar, searchMobileModal, dateFromDesktop, dateFromMobile, dateToDesktop, dateToMobile];
    controls.forEach((control) => {
      if (control && control !== source) {
        if ((source === searchDesktop || source === searchMobileToolbar || source === searchMobileModal) &&
            (control === searchDesktop || control === searchMobileToolbar || control === searchMobileModal)) {
          control.value = value;
        }
        if ((source === dateFromDesktop || source === dateFromMobile) &&
            (control === dateFromDesktop || control === dateFromMobile)) {
          control.value = value;
        }
        if ((source === dateToDesktop || source === dateToMobile) &&
            (control === dateToDesktop || control === dateToMobile)) {
          control.value = value;
        }
      }
    });
  }

  function renderActiveFilters() {
    if (!activeFilters) return;
    activeFilters.innerHTML = '';
    activeFilters.style.display = 'none';
  }

  function updateEmptyState(visibleCount) {
    if (visibleCount === 0) {
      if (!document.getElementById('srNoResultsRow')) {
        const row = document.createElement('tr');
        row.id = 'srNoResultsRow';
        row.innerHTML = '<td colspan="6" class="text-center">No service requests found.</td>';
        tableBody.appendChild(row);
      }
    } else {
      const noResultsRow = document.getElementById('srNoResultsRow');
      if (noResultsRow) noResultsRow.remove();
    }

    if (originalEmptyRow) {
      originalEmptyRow.classList.toggle('sr-row-hidden', visibleCount !== 0);
      originalEmptyRow.classList.toggle('sr-row-visible', visibleCount === 0);
    }
  }

  function applyFilters() {
    const { search, dateFrom, dateTo } = currentFilters();
    const searchTerm = normalize(search);
    const fromDate = parseDate(dateFrom);
    const toDate = parseDate(dateTo);
    if (toDate) toDate.setHours(23, 59, 59, 999);

    let visibleCount = 0;

    rows.forEach((row) => {
      const rowText = normalize(row.dataset.search || row.textContent);
      const rowDate = parseDate(row.dataset.date || '');

      const matchesSearch = !searchTerm || rowText.includes(searchTerm);
      const matchesFrom = !fromDate || (rowDate && rowDate >= fromDate);
      const matchesTo = !toDate || (rowDate && rowDate <= toDate);
      const visible = matchesSearch && matchesFrom && matchesTo;

      row.classList.toggle('sr-row-hidden', !visible);
      row.classList.toggle('sr-row-visible', visible);
      if (visible) visibleCount += 1;
    });

    updateEmptyState(visibleCount);
    renderActiveFilters();
  }

  function bindRealtime(control) {
    if (!control) return;
    const eventName = control.type === 'date' ? 'change' : 'input';
    control.addEventListener(eventName, function () {
      syncValues(control, control.value);
      applyFilters();
    });
  }

  [searchDesktop, searchMobileToolbar, searchMobileModal, dateFromDesktop, dateFromMobile, dateToDesktop, dateToMobile].forEach(bindRealtime);

  [desktopForm, mobileSearchForm, modalForm].forEach((form) => {
    if (!form) return;
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      applyFilters();
    });
  });

  if (mobileClearAll) {
    mobileClearAll.addEventListener('click', function (event) {
      event.preventDefault();
      [searchDesktop, searchMobileToolbar, searchMobileModal, dateFromDesktop, dateFromMobile, dateToDesktop, dateToMobile]
        .forEach((control) => {
          if (control) control.value = '';
        });
      applyFilters();
    });
  }

  if (filtersModal) {
    filtersModal.addEventListener('show.bs.modal', function () {
      if (searchMobileModal && searchMobileToolbar) searchMobileModal.value = searchMobileToolbar.value;
      if (dateFromMobile && dateFromDesktop) dateFromMobile.value = dateFromDesktop.value;
      if (dateToMobile && dateToDesktop) dateToMobile.value = dateToDesktop.value;
    });
  }

  applyFilters();
}

function escapeHtml(value) {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
