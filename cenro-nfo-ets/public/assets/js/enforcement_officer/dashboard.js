// Register Chart.js plugins
    Chart.register(ChartDataLabels);
    // persistent chart instances for live updates
    let spotChart = null;
    let caseChart = null;
    
    // Initialize profile dropdown (from dashboard.js)
    function initializeProfileDropdown() {
      const profileCard = document.getElementById('profileCard');
      const profileDropdown = document.getElementById('profileDropdown');
      
      if (!profileCard || !profileDropdown) return;
      
      let dropdownOpen = false;

      function toggleDropdown() {
          dropdownOpen = !dropdownOpen;
          profileDropdown.style.display = dropdownOpen ? 'flex' : 'none';
      }

      profileCard.addEventListener('click', function(e) {
          toggleDropdown();
          e.stopPropagation();
      });

      document.addEventListener('click', function(e) {
          if (!profileCard.contains(e.target)) {
              dropdownOpen = false;
              profileDropdown.style.display = 'none';
          }
      });

      document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape' && dropdownOpen) {
              dropdownOpen = false;
              profileDropdown.style.display = 'none';
          }
      });
    }

    // Initialize or update dashboard charts with server-provided data
    function initializeDashboardCharts(data) {
      const ctx1 = document.getElementById('spotReportsChart');
      const ctx2 = document.getElementById('caseStatusChart');

      function palette(n) {
        // Colors aligned with requested mapping:
        // bg-primary, bg-warning, bg-warning, bg-secondary, bg-info, bg-success,
        // bg-danger, bg-dark, bg-danger, bg-teal
        const base = ['#0d6efd','#ffc107','#ffc107','#6c757d','#0dcaf0','#198754','#dc3545','#212529','#dc3545','#20c997'];
        const out = [];
        for (let i=0;i<n;i++) out.push(base[i % base.length]);
        return out;
      }

      // Spot reports bar chart
      if (ctx1) {
        const spotBy = (data && data.spot_reports && data.spot_reports.by_status) ? data.spot_reports.by_status : {};
        const labels = Object.keys(spotBy).map(k => k.charAt(0).toUpperCase() + k.slice(1));
        const values = Object.keys(spotBy).map(k => spotBy[k]);

        if (spotChart) {
          spotChart.data.labels = labels;
          spotChart.data.datasets[0].data = values;
          spotChart.data.datasets[0].backgroundColor = palette(values.length);
          spotChart.update();
        } else {
          spotChart = new Chart(ctx1, {
            type: 'bar',
            data: {
              labels: labels,
              datasets: [{ label: 'Spot Reports', data: values, backgroundColor: palette(values.length) }]
            },
            options: { responsive: true, maintainAspectRatio: false }
          });
        }
      }

      // Case statuses doughnut chart
      if (ctx2) {
        const caseBy = (data && data.case_statuses && data.case_statuses.by_status) ? data.case_statuses.by_status : {};
        // Aggregate and normalize case status keys into canonical buckets so the chart
        // always shows the main lifecycle categories even if DB keys vary.
        const agg = {
          'Under Investigation': 0,
          'Pending Review': 0,
          'For Filing': 0,
          'Filed in Court': 0,
          'Ongoing Trial': 0,
          'Resolved': 0,
          'Dismissed': 0,
          'Archived': 0,
          'On Hold': 0,
          'Under Appeal': 0
        };
        const normalizeKey = s => ('' + s).toLowerCase();
        Object.keys(caseBy).forEach(k => {
          const v = caseBy[k] || 0;
          const n = normalizeKey(k);
          if (n === '' || n.includes('under') && (n.includes('invest') || n.includes('investigation') || !n.includes('review'))) {
            agg['Under Investigation'] += v;
          } else if (n.includes('pending')) {
            agg['Pending Review'] += v;
          } else if (n.includes('for filing') || n.includes('for-filing') || n.includes('forfiling')) {
            agg['For Filing'] += v;
          } else if (n.includes('filed') || n.includes('court')) {
            agg['Filed in Court'] += v;
          } else if (n.includes('ongoing') || n.includes('trial')) {
            agg['Ongoing Trial'] += v;
          } else if (n.includes('resolv')) {
            agg['Resolved'] += v;
          } else if (n.includes('dismiss')) {
            agg['Dismissed'] += v;
          } else if (n.includes('archiv')) {
            agg['Archived'] += v;
          } else if (n.includes('hold')) {
            agg['On Hold'] += v;
          } else if (n.includes('appeal')) {
            agg['Under Appeal'] += v;
          } else {
            // fallback into Under Investigation
            agg['Under Investigation'] += v;
          }
        });

        // Always show the canonical case status buckets (include zeros)
        const labels = Object.keys(agg);
        const values = labels.map(k => agg[k]);

        if (caseChart) {
          caseChart.data.labels = labels;
          caseChart.data.datasets[0].data = values;
          caseChart.data.datasets[0].backgroundColor = palette(values.length);
          caseChart.update();
        } else {
          caseChart = new Chart(ctx2, {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: values, backgroundColor: palette(values.length) }] },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                  display: true,
                  color: '#fff',
                  font: { weight: 'bold', size: 12 },
                  formatter: (value, context) => {
                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0) || 0;
                    const percentage = total ? ((value / total) * 100).toFixed(1) : '0.0';
                    return `${value}\n${percentage}%`;
                  }
                }
              }
            }
          });
        }
      }
    }

    // Fetch counts from server and populate UI
    async function fetchDashboardCounts(url = 'actions/get_dashboard_counts.php') {
      try {
        console.log('Requesting dashboard counts from', url);
        const noCacheUrl = new URL(url, window.location.href);
        noCacheUrl.searchParams.set('_ts', Date.now().toString());
        const res = await fetch(noCacheUrl.href, {
          credentials: 'same-origin',
          cache: 'no-store'
        });
        if (!res.ok) {
          const txt = await res.text().catch(() => '[no body]');
          console.error('Counts endpoint returned non-OK', res.status, txt);
          return;
        }

        let json;
        try {
          json = await res.json();
        } catch (e) {
          const txt = await res.text().catch(() => '[no body]');
          console.error('Failed to parse JSON from counts endpoint:', txt);
          return;
        }

        if (!json || json.ok === false) {
          console.error('Counts endpoint returned error object', json);
          return;
        }

        const spot = json.spot_reports || { total: 0, by_status: {} };
        const cases = json.case_statuses || { total: 0, by_status: {} };
        const app = json.apprehended || { persons: 0, vehicles: 0, items: 0, total: 0 };

        // helper to get status-insensitive value (normalize hyphens/underscores/spaces)
        function getStatus(map, name) {
          if (!map) return 0;
          const norm = (s) => ('' + s).toLowerCase().replace(/[_\s\-]+/g, '');
          const target = norm(name);
          for (const k of Object.keys(map)) {
            if (norm(k) === target) return map[k];
          }
          return 0;
        }

        document.getElementById('spotReportsCount').textContent = spot.total || 0;
        document.getElementById('spotApprovedCount').textContent = getStatus(spot.by_status, 'approved') || 0;
        document.getElementById('spotPendingCount').textContent = getStatus(spot.by_status, 'pending') || 0;
        document.getElementById('spotRejectedCount').textContent = getStatus(spot.by_status, 'rejected') || 0;

        // Normalize case statuses so blank/legacy values still count correctly on cards.
        const caseAgg = {
          underInvestigation: 0,
          pendingReview: 0,
          forFiling: 0,
          filedInCourt: 0,
          ongoingTrial: 0,
          resolved: 0,
          dismissed: 0,
          archived: 0,
          onHold: 0,
          underAppeal: 0
        };
        Object.keys(cases.by_status || {}).forEach((k) => {
          const rawVal = Number(cases.by_status[k] || 0);
          const n = ('' + k).toLowerCase().trim();
          if (!n || (n.includes('under') && (n.includes('invest') || !n.includes('review')))) caseAgg.underInvestigation += rawVal;
          else if (n.includes('pending')) caseAgg.pendingReview += rawVal;
          else if (n.includes('for filing') || n.includes('for-filing') || n.includes('forfiling')) caseAgg.forFiling += rawVal;
          else if (n.includes('filed') || n.includes('court')) caseAgg.filedInCourt += rawVal;
          else if (n.includes('ongoing') || n.includes('trial')) caseAgg.ongoingTrial += rawVal;
          else if (n.includes('resolv')) caseAgg.resolved += rawVal;
          else if (n.includes('dismiss')) caseAgg.dismissed += rawVal;
          else if (n.includes('archiv')) caseAgg.archived += rawVal;
          else if (n.includes('hold')) caseAgg.onHold += rawVal;
          else if (n.includes('appeal')) caseAgg.underAppeal += rawVal;
          else caseAgg.underInvestigation += rawVal;
        });

        const caseTotalFromAgg = Object.values(caseAgg).reduce((sum, val) => sum + val, 0);
        document.getElementById('caseManagementCount').textContent = (cases.total || caseTotalFromAgg || 0);
        document.getElementById('caseUnderInvestigation').textContent = caseAgg.underInvestigation;
        document.getElementById('casePendingReview').textContent = caseAgg.pendingReview;
        document.getElementById('caseForFiling').textContent = caseAgg.forFiling;
        document.getElementById('caseFiledInCourt').textContent = caseAgg.filedInCourt;
        document.getElementById('caseOnGoing').textContent = caseAgg.ongoingTrial;
        document.getElementById('caseResolved').textContent = caseAgg.resolved;
        document.getElementById('caseDismissed').textContent = caseAgg.dismissed;
        document.getElementById('caseArchived').textContent = caseAgg.archived;
        document.getElementById('caseOnHold').textContent = caseAgg.onHold;
        document.getElementById('caseUnderAppeal').textContent = caseAgg.underAppeal;

        document.getElementById('apprehendedCount').textContent = app.total || 0;
        document.getElementById('apprehendedPersonCount').textContent = app.persons || 0;
        document.getElementById('apprehendedVehiclesCount').textContent = app.vehicles || 0;
        document.getElementById('apprehendedItemsCount').textContent = app.items || 0;

        // Initialize charts with fetched data
        console.log('Dashboard counts received', json);
        initializeDashboardCharts(json);
      } catch (err) {
        console.error('Failed to fetch dashboard counts', err);
      }
    }
    
    // Initialize everything when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
      initializeProfileDropdown();
      
      // Debug: Check if Service Desk elements exist
      setTimeout(function() {
        const serviceDeskToggle = document.getElementById('serviceDeskToggle');
        const serviceDeskMenu = document.getElementById('serviceDeskMenu');
        
        console.log('=== DASHBOARD DEBUG ===');
        console.log('Service Desk Toggle found:', !!serviceDeskToggle);
        console.log('Service Desk Menu found:', !!serviceDeskMenu);
        
        if (serviceDeskToggle && serviceDeskMenu) {
          console.log('Adding manual click handler for dashboard...');
          
          // Force show dropdown with basic display toggle for testing
          serviceDeskToggle.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Service Desk CLICKED on dashboard!');
            
            // Simple display toggle test
            const currentDisplay = serviceDeskMenu.style.display;
            
            if (currentDisplay === 'block') {
              serviceDeskMenu.style.display = 'none';
              serviceDeskToggle.classList.remove('active');
              console.log('Hiding with display none...');
            } else {
              serviceDeskMenu.style.display = 'block';
              serviceDeskMenu.style.maxHeight = '300px';
              serviceDeskMenu.style.opacity = '1';
              serviceDeskMenu.style.padding = '5px 0';
              serviceDeskToggle.classList.add('active');
              console.log('Showing with display block...');
            }
            
            // Also try the class method
            serviceDeskMenu.classList.toggle('show');
            
            // Handle arrow
            const arrow = serviceDeskToggle.querySelector('.dropdown-arrow');
            if (arrow) {
              arrow.classList.toggle('rotated');
            }
          });
        } else {
          console.log('Service Desk elements NOT FOUND!');
        }
      }, 200);
      
      // Fetch real dashboard counts and initialize charts
      setTimeout(function() {
        if (typeof Chart !== 'undefined') {
          console.log('Fetching dashboard counts...');
          const countsUrl = new URL('actions/get_dashboard_counts.php', window.location.href).href;
          fetchDashboardCounts(countsUrl);
          // Keep dashboard stats fresh without manual page reload
          setInterval(function() {
            fetchDashboardCounts(countsUrl);
          }, 10000);
        }
      }, 500);
    });
