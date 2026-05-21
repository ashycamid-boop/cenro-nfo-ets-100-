(function () {
  'use strict';

  function buildApiUrl(fileName) {
    return new URL(`../../../../app/api/${fileName}`, window.location.href).href;
  }

  function normalizeKey(value) {
    if (value === null || value === undefined) {
      return '';
    }
    return String(value).toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
  }

  function initializeDashboardCharts() {
    const charts = {};

    const spotReportsCanvas = document.getElementById('spotReportsChart');
    const caseStatusCanvas = document.getElementById('caseStatusChart');
    const equipmentCanvas = document.getElementById('equipmentChart');
    const userRolesCanvas = document.getElementById('userRolesChart');

    if (spotReportsCanvas) {
      charts.spotReports = new Chart(spotReportsCanvas, {
        type: 'bar',
        data: {
          labels: ['Approved', 'Pending', 'Rejected'],
          datasets: [{
            label: 'Spot Reports',
            data: [0, 0, 0],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });
    }

    if (caseStatusCanvas) {
      charts.caseStatus = new Chart(caseStatusCanvas, {
        type: 'doughnut',
        data: {
          labels: [
            'Under Investigation',
            'Pending Review',
            'For Filing',
            'Filed in Court',
            'Ongoing Trial',
            'Resolved',
            'Dismissed',
            'Archived',
            'On Hold',
            'Under Appeal'
          ],
          datasets: [{
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            backgroundColor: ['#007bff', '#ffc107', '#ffc107', '#6c757d', '#17a2b8', '#28a745', '#dc3545', '#343a40', '#dc3545', '#20c997']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '45%',
          plugins: {
            legend: {
              position: 'right',
              labels: { boxWidth: 12 }
            },
            datalabels: {
              color: '#ffffff',
              formatter: function (value) {
                return value > 0 ? value : '';
              },
              font: {
                weight: 'bold',
                size: 12
              }
            },
            tooltip: {
              callbacks: {
                label: function (context) {
                  const pointValue = context.parsed || 0;
                  const dataset = context.chart.data.datasets[0].data || [];
                  const total = dataset.reduce((sum, item) => sum + (item || 0), 0);
                  const percentage = total ? ((pointValue / total) * 100).toFixed(1) + '%' : '0%';
                  return context.label + ': ' + pointValue + ' (' + percentage + ')';
                }
              }
            }
          }
        }
      });
    }

    if (equipmentCanvas) {
      charts.equipment = new Chart(equipmentCanvas, {
        type: 'bar',
        data: {
          labels: ['Assigned', 'Available', 'Returned', 'Under Maintenance', 'Missing', 'Damaged', 'Out of Service'],
          datasets: [{
            label: 'Equipment',
            data: [0, 0, 0, 0, 0, 0, 0],
            backgroundColor: ['#28a745', '#ffc107', '#0dcaf0', '#17a2b8', '#6f42c1', '#dc3545', '#6c757d']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });
    }

    if (userRolesCanvas) {
      charts.userRoles = new Chart(userRolesCanvas, {
        type: 'doughnut',
        data: {
          labels: [],
          datasets: [{
            data: [],
            backgroundColor: []
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });
    }

    window.dashboardCharts = charts;
    return charts;
  }

  function setText(id, value, fallback) {
    const node = document.getElementById(id);
    if (!node) {
      return;
    }
    node.textContent = value !== undefined && value !== null ? value : fallback;
  }

  function loadDashboardData() {
    fetch(buildApiUrl('dashboard_counts.php'), { credentials: 'same-origin' })
      .then((response) => response.json())
      .then((data) => {
        setText('totalUsersCount', data.total_users, '—');

        if (data.spot_reports) {
          setText('spotReportsCount', data.spot_reports.total, '—');
          setText('spotApprovedCount', data.spot_reports.approved, '—');
          setText('spotPendingCount', data.spot_reports.pending, '—');
          setText('spotRejectedCount', data.spot_reports.rejected, '—');

          const spotReportsChart = window.dashboardCharts && window.dashboardCharts.spotReports;
          if (spotReportsChart) {
            spotReportsChart.data.datasets[0].data = [
              data.spot_reports.approved ?? 0,
              data.spot_reports.pending ?? 0,
              data.spot_reports.rejected ?? 0
            ];
            spotReportsChart.update();
          }
        }

        if (data.cases) {
          setText('caseManagementCount', data.cases.total, '—');

          const statusByKey = {};
          Object.keys(data.cases.statuses || {}).forEach((status) => {
            statusByKey[normalizeKey(status)] = data.cases.statuses[status];
          });

          const caseToElement = {
            'Under Investigation': 'caseUnderInvestigation',
            'Pending Review': 'casePendingReview',
            'For Filing': 'caseForFiling',
            'Filed in Court': 'caseFiledInCourt',
            'Ongoing Trial': 'caseOngoingTrial',
            'Resolved': 'caseResolved',
            'Dismissed': 'caseDismissed',
            'Archived': 'caseArchived',
            'On Hold': 'caseOnHold',
            'Under Appeal': 'caseUnderAppeal'
          };

          Object.keys(caseToElement).forEach((label) => {
            const count = statusByKey[normalizeKey(label)] ?? 0;
            setText(caseToElement[label], count, 0);
          });

          const caseStatusChart = window.dashboardCharts && window.dashboardCharts.caseStatus;
          if (caseStatusChart) {
            caseStatusChart.data.datasets[0].data = caseStatusChart.data.labels.map((label) => {
              return statusByKey[normalizeKey(label)] ?? 0;
            });
            caseStatusChart.update();
          }
        }

        if (data.equipment) {
          setText('equipmentCount', data.equipment.total, '—');
          setText('equipmentAssignedCount', data.equipment.assigned, 0);
          setText('equipmentAvailableCount', data.equipment.available, 0);
          setText('equipmentReturnedCount', data.equipment.returned, 0);
          setText('equipmentUnderMaintenanceCount', data.equipment.under_maintenance, 0);
          setText('equipmentMissingCount', data.equipment.missing, 0);
          setText('equipmentDamagedCount', data.equipment.damaged, 0);
          setText('equipmentOutOfServiceCount', data.equipment.out_of_service, 0);

          const equipmentChart = window.dashboardCharts && window.dashboardCharts.equipment;
          if (equipmentChart) {
            equipmentChart.data.datasets[0].data = [
              data.equipment.assigned ?? 0,
              data.equipment.available ?? 0,
              data.equipment.returned ?? 0,
              data.equipment.under_maintenance ?? 0,
              data.equipment.missing ?? 0,
              data.equipment.damaged ?? 0,
              data.equipment.out_of_service ?? 0
            ];
            equipmentChart.update();
          }
        }

        if (data.service_requests) {
          setText('serviceRequestsCount', data.service_requests.total, '—');
          setText('servicePendingCount', data.service_requests.pending, '—');
          setText('serviceOnGoingCount', data.service_requests.ongoing, '—');
          setText('serviceCompletedCount', data.service_requests.completed, '—');
        }

        if (data.apprehended) {
          const totalApprehended = (data.apprehended.persons ?? 0) + (data.apprehended.vehicles ?? 0) + (data.apprehended.items ?? 0);
          setText('apprehendedCount', totalApprehended, 0);
          setText('apprehendedPersonCount', data.apprehended.persons, '—');
          setText('apprehendedVehiclesCount', data.apprehended.vehicles, '—');
          setText('apprehendedItemsCount', data.apprehended.items, '—');
        }

        const userRolesChart = window.dashboardCharts && window.dashboardCharts.userRoles;
        if (userRolesChart) {
          const roleCounts = {};
          const roleSource = (data.user_roles && Object.keys(data.user_roles).length) ? data.user_roles : (data.user_roles_raw || {});

          Object.keys(roleSource).forEach((role) => {
            roleCounts[normalizeKey(role)] = roleSource[role] || 0;
          });

          const desiredRoles = [
            { key: 'enforcement', label: 'Enforcement Officer' },
            { key: 'enforcer', label: 'Enforcer' },
            { key: 'property custodian', label: 'Property Custodian' },
            { key: 'office staff', label: 'Office Staff' }
          ];

          const labels = desiredRoles.map((role) => role.label);
          const values = desiredRoles.map((role) => roleCounts[role.key] || 0);
          const palette = ['#28a745', '#fd7e14', '#6f42c1', '#20c997', '#007bff', '#6c757d', '#e83e8c', '#ffc107'];

          userRolesChart.data.labels = labels;
          userRolesChart.data.datasets[0].data = values;
          userRolesChart.data.datasets[0].backgroundColor = labels.map((_, index) => palette[index % palette.length]);
          userRolesChart.update();
        }
      })
      .catch((error) => {
        console.error('Failed to load dashboard counts', error);
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
      return;
    }

    if (typeof ChartDataLabels !== 'undefined') {
      Chart.register(ChartDataLabels);
    }

    initializeDashboardCharts();
    loadDashboardData();
    setInterval(loadDashboardData, 30000);
  });
})();




