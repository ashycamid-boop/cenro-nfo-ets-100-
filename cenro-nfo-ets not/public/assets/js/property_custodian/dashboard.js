// Register Chart.js plugins
if (typeof Chart !== 'undefined' && Chart && Chart.register) {
  try { Chart.register(ChartDataLabels); } catch (e) {}
}

// Fetch equipment list via EquipmentService and populate counts + status chart
async function fetchAndPopulateCounts() {
  try {
    let items = [];
    if (typeof EquipmentService !== 'undefined' && EquipmentService && EquipmentService.getAll) {
      const resp = await EquipmentService.getAll();
      // EquipmentService.getAll returns { data: [...] } or similar
      if (Array.isArray(resp)) items = resp;
      else if (resp && Array.isArray(resp.data)) items = resp.data;
      else if (resp && Array.isArray(resp.results)) items = resp.results;
    } else {
      // fallback: try fetching the API directly
      const r = await fetch('../../../../app/api/equipment/equipment_api.php?action=getAll');
      const json = await r.json();
      if (Array.isArray(json)) items = json; else if (json && Array.isArray(json.data)) items = json.data;
    }

    const total = items.length;
    const counts = { available: 0, assigned: 0, returned: 0, maintenance: 0, missing: 0, damaged: 0, out_of_service: 0 };
    for (const it of items) {
      const s = (it.status || '').toString().toLowerCase();
      if (s === 'available' || s === 'available ') counts.available++;
      else if (s === 'in use' || s === 'assigned' || s === 'assigned ') counts.assigned++;
      else if (s === 'returned' || s === 'returned ') counts.returned++;
      else if (s.includes('maintenance') || s === 'under maintenance') counts.maintenance++;
      else if (s === 'missing') counts.missing++;
      else if (s === 'damaged') counts.damaged++;
      else if (s.includes('out') || s.includes('service')) counts.out_of_service++;
    }

    // Update DOM placeholders
    const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    setText('totalEquipmentCount', total);
    setText('assignedDevicesCount', counts.assigned);
    setText('availableEquipmentCount', counts.available);
    setText('pendingRequestsCount', 0);
    setText('legendAvailableCount', counts.available);
    setText('legendAssignedCount', counts.assigned);
    setText('legendReturnedCount', counts.returned);
    setText('legendUnderMaintenanceCount', counts.maintenance);
    setText('legendMissingCount', counts.missing);
    setText('legendDamagedCount', counts.damaged);
    setText('legendOutOfServiceCount', counts.out_of_service);

    // Update equipmentStatusChart if present
    if (window.dashboardCharts && window.dashboardCharts.equipmentStatusChart) {
      const chart = window.dashboardCharts.equipmentStatusChart;
      chart.data.datasets[0].data = [counts.available, counts.assigned, counts.returned, counts.maintenance, counts.missing, counts.damaged, counts.out_of_service];
      chart.update();
    }

    // Fetch service request counts (pending/ongoing/completed) and set pendingRequestsCount
    try {
      const sc = await fetch('../../../../app/api/service_counts.php');
      const scj = await sc.json();
      if (scj && typeof scj.new_requests !== 'undefined') {
        setText('pendingRequestsCount', scj.new_requests);
      }
    } catch (e) {
      // ignore service counts errors
    }
  } catch (err) {
    console.warn('Failed to fetch equipment counts:', err);
  }
}

// Fetch requests grouped by department and populate departmentRequestsChart
async function fetchAndPopulateDepartmentChart() {
  try {
    const resp = await fetch('../../../../app/api/service_requests_by_department.php');
    const json = await resp.json();
    if (!json || !json.success || !Array.isArray(json.data)) return;
    const data = json.data.slice(0, 12); // limit to top 12
    const labels = data.map(d => d.label);
    const counts = data.map(d => d.count);
    const colors = labels.map((_, i) => {
      // generate pleasant palette
      const palette = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#6f42c1', '#20c997', '#fd7e14', '#6610f2', '#20a8d8', '#f3a712'];
      return palette[i % palette.length];
    });

    if (window.dashboardCharts && window.dashboardCharts.departmentRequestsChart) {
      const ch = window.dashboardCharts.departmentRequestsChart;
      ch.data.labels = labels;
      ch.data.datasets[0].data = counts;
      ch.data.datasets[0].backgroundColor = colors;
      ch.update();
    }
  } catch (e) {
    console.warn('Failed to fetch department requests:', e);
  }
}

// Initialize profile dropdown
function initializeProfileDropdown() {
  const profileCard = document.getElementById('profileCard');
  const profileDropdown = document.getElementById('profileDropdown');
  if (!profileCard || !profileDropdown) return;
  let dropdownOpen = false;
  function toggleDropdown() { dropdownOpen = !dropdownOpen; profileDropdown.style.display = dropdownOpen ? 'flex' : 'none'; }
  profileCard.addEventListener('click', function (e) { toggleDropdown(); e.stopPropagation(); });
  document.addEventListener('click', function (e) { if (!profileCard.contains(e.target)) { dropdownOpen = false; profileDropdown.style.display = 'none'; } });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && dropdownOpen) { dropdownOpen = false; profileDropdown.style.display = 'none'; } });
}

// Initialize dashboard charts (no demo/static data)
// Exposes chart instances on `window.dashboardCharts` for later updates
function initializeDashboardCharts() {
  // Department Requests Bar Chart (no static demo data)
  const ctx1 = document.getElementById('departmentRequestsChart');
  if (ctx1 && typeof Chart !== 'undefined') {
    const deptChart = new Chart(ctx1, {
      type: 'bar',
      data: { labels: [], datasets: [{ label: 'Equipment Requests', data: [], backgroundColor: [], borderRadius: 4, borderSkipped: false }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, datalabels: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true } } }
    });
    window.dashboardCharts = window.dashboardCharts || {};
    window.dashboardCharts.departmentRequestsChart = deptChart;
  }

  // Equipment Status Doughnut - start with zeros
  const ctx2 = document.getElementById('equipmentStatusChart');
  if (ctx2 && typeof Chart !== 'undefined') {
    const labels = ['Available', 'Assigned', 'Returned', 'Under Maintenance', 'Missing', 'Damaged', 'Out of Service'];
    const data = [0, 0, 0, 0, 0, 0, 0];
    const statusChart = new Chart(ctx2, {
      type: 'doughnut',
      data: { labels: labels, datasets: [{ data: data, backgroundColor: ['#0d6efd', '#198754', '#0dcaf0', '#ffc107', '#6f42c1', '#dc3545', '#6c757d'], borderWidth: 3, borderColor: '#ffffff', hoverOffset: 6 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '40%', plugins: { legend: { display: false }, datalabels: { display: true, color: '#ffffff', font: { weight: 'bold', size: 12 }, formatter: (value, context) => { const total = context.dataset.data.reduce((sum, val) => sum + val, 0) || 1; const percentage = ((value / total) * 100).toFixed(0); return `${percentage}%`; }, anchor: 'center', align: 'center' } } }
    });
    window.dashboardCharts = window.dashboardCharts || {};
    window.dashboardCharts.equipmentStatusChart = statusChart;
  }

  // Update counts placeholders (leave zeros - expected to be populated by backend calls later)
  const setZero = (id) => { const el = document.getElementById(id); if (el) el.textContent = 0; };
  setZero('totalEquipmentCount');
  setZero('assignedDevicesCount');
  setZero('pendingRequestsCount');
  setZero('availableEquipmentCount');
  setZero('legendAvailableCount');
  setZero('legendAssignedCount');
  setZero('legendReturnedCount');
  setZero('legendUnderMaintenanceCount');
  setZero('legendMissingCount');
  setZero('legendDamagedCount');
  setZero('legendOutOfServiceCount');
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
  initializeProfileDropdown();
  // Service Desk dropdown
  setTimeout(function () {
    const serviceDeskToggle = document.getElementById('serviceDeskToggle');
    const serviceDeskMenu = document.getElementById('serviceDeskMenu');
    if (serviceDeskToggle && serviceDeskMenu) {
      serviceDeskToggle.addEventListener('click', function (e) {
        e.preventDefault();
        const currentDisplay = serviceDeskMenu.style.display;
        if (currentDisplay === 'block') { serviceDeskMenu.style.display = 'none'; serviceDeskToggle.classList.remove('active'); } else { serviceDeskMenu.style.display = 'block'; serviceDeskMenu.style.maxHeight = '300px'; serviceDeskMenu.style.opacity = '1'; serviceDeskMenu.style.padding = '5px 0'; serviceDeskToggle.classList.add('active'); }
        serviceDeskMenu.classList.toggle('show');
        const arrow = serviceDeskToggle.querySelector('.dropdown-arrow'); if (arrow) { arrow.classList.toggle('rotated'); }
      });
    }
  }, 200);

  // Initialize charts after a short delay and populate counts
  setTimeout(async function () {
    if (typeof Chart !== 'undefined') initializeDashboardCharts();
    // populate counts and department chart once charts are initialized
    try {
      await fetchAndPopulateCounts();
      await fetchAndPopulateDepartmentChart();
    } catch (e) {}
  }, 300);
});
