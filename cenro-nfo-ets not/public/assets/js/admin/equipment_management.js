document.addEventListener('DOMContentLoaded', async function() {
      let equipmentData = {};
      let usersData = [];

     // Helper: return first existing property value from a list of possible keys
     function getProp(obj, ...keys) {
       if (!obj) return '';
       for (const k of keys) {
         if (obj[k] !== undefined && obj[k] !== null && String(obj[k]).trim() !== '') return obj[k];
       }
       return '';
     }
    
      // load users first then equipment
      await loadUsers();
      await loadEquipmentData();

     // Helper: wrap service calls to avoid JSON parse errors when server returns HTML (session expired / login redirect)
     async function safeServiceCall(promise) {
       try {
         const res = await promise;

         // If service returned an object that indicates session expiry, handle it
         if (res && (res.sessionExpired === true || (typeof res.error === 'string' && res.error.toLowerCase().includes('session')))) {
          console.warn('Service indicates session expired or returned HTML:', res);
          // give user a short message then redirect to login page
          alert('Session expired or server returned an authentication page. You will be redirected to the login page.');
          window.location.href = '../../../../index.php'; // adjust if your login path differs
          return { error: 'session' , sessionExpired: true };
        }
 
         // If response is undefined/null, normalize
         if (res === undefined || res === null) {
           return { error: 'Empty response from server' };
         }
 
         return res;
       } catch (err) {
         console.error('Service call failed:', err);
         return { error: err && err.message ? err.message : 'Service call failed' };
       }
     }
 
      async function loadUsers() {
        const accountablePersonSelect = document.getElementById('accountablePerson');
        const actualUserSelect = document.getElementById('actualUser');

        if (accountablePersonSelect) accountablePersonSelect.innerHTML = '<option value="">Loading users...</option>';
        if (actualUserSelect) actualUserSelect.innerHTML = '<option value="">Loading users...</option>';

        try {
          let users = [];

          // 1) Try EquipmentService.getUsers() safely
          try {
            if (window.EquipmentService && typeof EquipmentService.getUsers === 'function') {
              const res = await EquipmentService.getUsers();
              console.log('EquipmentService.getUsers() ->', res);

              if (typeof res === 'string' && res.trim().startsWith('<')) {
                // service returned HTML (login redirect or page) — avoid JSON.parse error, will fallback below
                console.warn('getUsers returned HTML string; skipping JSON parse here.');
                console.warn('getUsers returned HTML string; skipping JSON parse here.');
              } else if (res && res.data && Array.isArray(res.data)) {
                users = res.data;
              } else if (Array.isArray(res)) {
                users = res;
              } else if (res && Array.isArray(res.users)) {
                users = res.users;
              }
            }
          } catch (e) {
            console.warn('EquipmentService.getUsers() failed:', e);
          }

          // 2) If empty, try a few known JSON endpoints (safe fetch + try parse)
          if (!users.length) {
            const endpoints = [
              '../../../../public/api/users.php',
              '/api/users.php',
              'user_management.php?format=json',
              'user_management.php' // will be parsed as HTML if JSON fails
            ];

            for (const ep of endpoints) {
              try {
                const resp = await fetch(ep, { credentials: 'same-origin' });
                if (!resp.ok) continue;
                const text = await resp.text();

                // try parse JSON first
                try {
                  const parsed = JSON.parse(text);
                  if (parsed && parsed.data && Array.isArray(parsed.data)) users = parsed.data;
                  else if (Array.isArray(parsed)) users = parsed;
                  if (users.length) break;
                } catch (jsonErr) {
                  // not JSON — if endpoint is HTML (like user_management.php), try parsing table rows
                  const doc = new DOMParser().parseFromString(text, 'text/html');
                  const rows = doc.querySelectorAll('table tbody tr');
                  if (rows && rows.length) {
                    rows.forEach(row => {
                      const cells = row.querySelectorAll('td');
                      if (cells.length >= 2) {
                        const id = cells[0].textContent.trim();
                        const name = cells[1].textContent.trim();
                        if (name) users.push({ id: id || null, full_name: name });
                      }
                    });
                    if (users.length) break;
                  }
                }
              } catch (fetchErr) {
                // ignore and try next endpoint
              }
            }
          }

          // Normalize users objects
          users = (users || []).map(u => {
            if (typeof u === 'string') return { id: null, full_name: u, sex: '' };
            return {
              id: u.id !== undefined ? u.id : (u.user_id !== undefined ? u.user_id : (u.id_user !== undefined ? u.id_user : null)),
              full_name: u.full_name || u.name || `${u.first_name || ''} ${u.last_name || ''}`.trim(),
              sex: u.sex || u.gender || ''
            };
          }).filter(u => u.full_name);

          // sort
          users.sort((a,b)=> (a.full_name||'').toLowerCase().localeCompare((b.full_name||'').toLowerCase()));

          usersData = users;
          populateUserDropdowns();
        } catch (err) {
          console.error('Error loading users (final):', err);
          usersData = [];
          if (accountablePersonSelect) accountablePersonSelect.innerHTML = '<option value="">Unable to load users</option>';
          if (actualUserSelect) actualUserSelect.innerHTML = '<option value="">Unable to load users</option>';
        }

        return usersData;
      }

      function populateUserDropdowns() {
        const accountablePersonSelect = document.getElementById('accountablePerson');
        const actualUserSelect = document.getElementById('actualUser');
        if (!accountablePersonSelect || !actualUserSelect) return;

        // default options
        accountablePersonSelect.innerHTML = '<option value="">Select Person</option>';
        actualUserSelect.innerHTML = '<option value="">Select User</option>';

        if (!usersData || usersData.length === 0) {
          const noOption1 = document.createElement('option');
          noOption1.value = '';
          noOption1.textContent = 'No users found';
          accountablePersonSelect.appendChild(noOption1);

          const noOption2 = document.createElement('option');
          noOption2.value = '';
          noOption2.textContent = 'No users found';
          actualUserSelect.appendChild(noOption2);

          // small helper link
          if (!document.getElementById('manageUsersHint')) {
            const hint = document.createElement('div');
            hint.id = 'manageUsersHint';
            hint.style.marginTop = '6px';
            hint.innerHTML = '<small>No users available. <a href="user_management.php">Open User Management</a> to add users.</small>';
            accountablePersonSelect.parentElement.appendChild(hint);
          }
          return;
        }

        // remove previous hint if present
        const oldHint = document.getElementById('manageUsersHint');
        if (oldHint) oldHint.remove();

        usersData.forEach(user => {
          const display = user.full_name;
          const value = (user.id !== null && user.id !== undefined && String(user.id) !== '') ? String(user.id) : display;

          const option1 = document.createElement('option');
          option1.value = value;
          option1.textContent = display;
          if (user.sex) option1.setAttribute('data-sex', user.sex);
          if (user.id !== undefined && user.id !== null) option1.setAttribute('data-id', String(user.id));
          accountablePersonSelect.appendChild(option1);

          const option2 = document.createElement('option');
          option2.value = value;
          option2.textContent = display;
          if (user.sex) option2.setAttribute('data-sex', user.sex);
          if (user.id !== undefined && user.id !== null) option2.setAttribute('data-id', String(user.id));
          actualUserSelect.appendChild(option2);
        });
      }

      function buildEquipmentSearchText(equipment, displayStatus) {
        const assetId = getProp(equipment, 'asset_id', 'assetId', 'id');
        const assetIdValues = [assetId];
        const assetIdNumber = Number(assetId);
        if (Number.isInteger(assetIdNumber) && assetIdNumber > 0) {
          assetIdValues.push(
            String(assetIdNumber),
            String(assetIdNumber).padStart(3, '0'),
            String(assetIdNumber).padStart(4, '0'),
            String(assetIdNumber).padStart(5, '0'),
            String(assetIdNumber).padStart(6, '0')
          );
        }

        return [
          ...assetIdValues,
          equipment.property_number,
          equipment.propertyNumber,
          equipment.equipment_type,
          equipment.brand,
          equipment.year_acquired,
          equipment.actual_user,
          equipment.accountable_person,
          displayStatus
        ].filter(value => value !== undefined && value !== null && String(value).trim() !== '')
          .join(' ')
          .toLowerCase();
      }

      function filterRenderedEquipmentRows(search, equipmentType = 'All') {
        const tbody = document.querySelector('#equipmentTable tbody');
        if (!tbody) return;

        const needle = (search || '').trim().toLowerCase();
        const normalizedType = normalizeEquipmentType(equipmentType);
        const rows = Array.from(tbody.querySelectorAll('tr'));
        let visibleCount = 0;

        rows.forEach(row => {
          if (row.dataset.emptyState === 'true') {
            row.remove();
            return;
          }

          const searchText = (row.dataset.searchText || '').toLowerCase();
          const rowType = normalizeEquipmentType(row.dataset.equipmentType || '');
          const matchesSearch = needle === '' || searchText.includes(needle);
          const matchesType = normalizedType === 'all' || normalizedType === '' || rowType === normalizedType;
          const matches = matchesSearch && matchesType;
          row.style.display = matches ? '' : 'none';
          if (matches) visibleCount += 1;
        });

        const existingEmptyState = tbody.querySelector('tr[data-empty-state="true"]');
        if (existingEmptyState) existingEmptyState.remove();

        if (visibleCount === 0) {
          const emptyRow = document.createElement('tr');
          emptyRow.dataset.emptyState = 'true';
          emptyRow.innerHTML = '<td colspan="10" class="text-center">No equipment found</td>';
          tbody.appendChild(emptyRow);
        }
      }

      async function loadEquipmentData(search = '', status = 'All') {
        try {
          // Map UI status values to backend/DB values when needed
          let queryStatus = status;
          if (String(status).toLowerCase() === 'assigned') queryStatus = 'In Use';
          const data = await safeServiceCall(EquipmentService.getAll(search, queryStatus));
          if (!data || data.error) {
            console.error('Unable to load equipment data:', data && data.error ? data.error : data);
            if (data && typeof data.error === 'string' && data.error.toLowerCase().includes('session')) {
              alert('Unable to load equipment. ' + data.error);
            }
            const tbody = document.querySelector('#equipmentTable tbody');
            tbody.innerHTML = '<tr><td colspan="10" class="text-center">Unable to load equipment</td></tr>';
            return;
          }
          
          equipmentData = {};
          const tbody = document.querySelector('#equipmentTable tbody');
          tbody.innerHTML = '';

          if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center">No equipment found</td></tr>';
            return;
          }

           data.forEach((equipment, index) => {
            equipmentData[equipment.id] = equipment;

            const normalizedStatus = canonicalizeStatus(equipment.status || '');
            const statusClass = (normalizedStatus || '').toString().toLowerCase().replace(/ /g, '-');
            const displayStatus = ((normalizedStatus || '').toString().toLowerCase() === 'in use') ? 'Assigned' : (normalizedStatus || '');

            const assetId = getProp(equipment, 'asset_id', 'assetId', 'id') || equipment.id;
            const qrPayload = (window.CENRO_QR_VIEW_BASE_URL || '../../../../public/qr_view.php?id=') + encodeURIComponent(equipment.id);
            const qrSrc = `https://api.qrserver.com/v1/create-qr-code/?size=50x50&data=${encodeURIComponent(qrPayload)}`;

            const row = document.createElement('tr');
            row.dataset.searchText = buildEquipmentSearchText(equipment, displayStatus);
            row.dataset.equipmentType = equipment.equipment_type || '';
            row.innerHTML = `
              <td data-label="Asset ID">${assetId}</td>
              <td data-label="Property No.">${equipment.property_number}</td>
              <td data-label="Equipment Type">${equipment.equipment_type || '-'}</td>
              <td data-label="Brand">${equipment.brand || '-'}</td>
              <td data-label="Year Acquired">${equipment.year_acquired || '-'}</td>
              <td data-label="Actual User">${equipment.actual_user || '-'}</td>
              <td data-label="Accountable Person">${equipment.accountable_person || '-'}</td>
              <td data-label="Status"><span class="badge status-${statusClass}">${displayStatus}</span></td>
              <td data-label="QR Code">
                <div class="qr-code-container text-center">
                  <a href="../../../../public/qr_view.php?id=${encodeURIComponent(equipment.id)}" target="_blank" title="Open details in new tab">
                    <img src="${qrSrc}" 
                         alt="QR Code" class="qr-code-img" style="width: 40px; height: 40px; cursor: pointer;">
                  </a>
                </div>
              </td>
              <td data-label="Actions" class="actions-cell">
                <div class="action-buttons">
                  <button type="button" class="btn btn-sm btn-outline-primary view-details" data-id="${equipment.id}" title="View Details" onclick="window.__adminEquipmentActions && window.__adminEquipmentActions.view('${equipment.id}')">
                    <i class="fa fa-eye"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary regenerate-qr" data-id="${equipment.id}" title="Regenerate QR" onclick="window.__adminEquipmentActions && window.__adminEquipmentActions.regenerate('${equipment.id}')">
                    <i class="fa fa-qrcode"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-success edit-equipment" data-id="${equipment.id}" title="Edit" onclick="window.__adminEquipmentActions && window.__adminEquipmentActions.edit('${equipment.id}')">
                    <i class="fa fa-edit"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-danger delete-equipment" data-id="${equipment.id}" title="Delete" onclick="window.__adminEquipmentActions && window.__adminEquipmentActions.delete('${equipment.id}')">
                    <i class="fa fa-trash"></i>
                  </button>
                </div>
              </td>
            `;
            tbody.appendChild(row);
          });

          filterRenderedEquipmentRows(search, getPrintTypeValue());

          // Reattach event listeners
          attachEventListeners();
        } catch (error) {
          console.error('Error loading equipment:', error);
        }
      }

      let equipmentTableActionsBound = false;

      function attachEventListeners() {
        if (equipmentTableActionsBound) return;

        const tbody = document.querySelector('#equipmentTable tbody');
        if (!tbody) return;

        tbody.addEventListener('click', async function(event) {
          const button = event.target.closest('button[data-id]');
          if (!button || !tbody.contains(button)) return;

          const equipmentId = button.getAttribute('data-id');
          if (!equipmentId) return;

          if (button.classList.contains('view-details')) {
            await viewEquipmentDetails(equipmentId);
            return;
          }

          if (button.classList.contains('edit-equipment')) {
            await editEquipment(equipmentId);
            return;
          }

          if (button.classList.contains('delete-equipment')) {
            await deleteEquipment(equipmentId);
            return;
          }

          if (button.classList.contains('regenerate-qr')) {
            if (!confirm('Regenerate QR for this equipment? This will overwrite existing QR image.')) return;
            const res = await safeServiceCall(EquipmentService.generateQR(equipmentId));
            if (!res || res.error || !res.success) {
              alert('Failed to generate QR: ' + (res && (res.error || JSON.stringify(res))));
              return;
            }
            alert('QR regenerated successfully. Refreshing list.');
            await loadEquipmentData();
          }
        });

        equipmentTableActionsBound = true;
      }

    // Helper to set detail fields for modal (handles inputs, textareas, selects and fallback to textContent)
    function setDetailField(id, value) {
      const el = document.getElementById(id);
      if (!el) return;
      const val = (value === null || value === undefined) ? '' : value;
      const tag = (el.tagName || '').toUpperCase();
      if (tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA') {
        el.value = val;
      } else {
        el.textContent = val;
      }
    }

    async function viewEquipmentDetails(id) {
      const equipment = equipmentData[id];
      if (!equipment) return;

      // Populate read-only form fields using helper
      setDetailField('detailAssetId', equipment.id || '');
      setDetailField('detailPropertyNumber', getProp(equipment, 'property_number', 'propertyNumber') || '');
      // support different possible server keys: office_division, office_devision, officeDevision, officeDivision
      setDetailField('detailOfficeDevision', getProp(equipment, 'office_division', 'office_devision', 'officeDevision', 'officeDivision') || '');
      setDetailField('detailEquipmentType', equipment.equipment_type || '');
      setDetailField('detailYearAcquired', equipment.year_acquired || '');
      setDetailField('detailShelfLife', equipment.shelf_life || '');
      setDetailField('detailBrand', equipment.brand || '');
      setDetailField('detailModel', equipment.model || '');
      setDetailField('detailProcessor', equipment.processor || '');
      setDetailField('detailRamSize', equipment.ram_size || '');
      setDetailField('detailGpu', equipment.gpu || '');
      setDetailField('detailRangeCategory', equipment.range_category || '');
      setDetailField('detailComputerName', equipment.computer_name || '');
      setDetailField('detailOsVersion', equipment.os_version || '');
      setDetailField('detailOfficeProductivity', equipment.office_productivity || '');
      setDetailField('detailEndpointProtection', equipment.endpoint_protection || '');
      setDetailField('detailSerialNumber', equipment.serial_number || '');
      setDetailField('detailAccountablePerson', equipment.accountable_person || '');
      setDetailField('detailAccountableSex', equipment.accountable_sex || '');
      setDetailField('detailAccountableEmployment', equipment.accountable_employment || '');
      setDetailField('detailActualUser', equipment.actual_user || '');
      setDetailField('detailActualUserSex', equipment.actual_user_sex || '');
      setDetailField('detailActualUserEmployment', equipment.actual_user_employment || '');
      setDetailField('detailNatureOfWork', equipment.nature_of_work || '');
      setDetailField('detailRemarks', equipment.remarks || 'No remarks');

      // Set QR code in details modal with a portable relative payload.
      try {
        const publicUrl = (window.CENRO_QR_VIEW_BASE_URL || '../../../../public/qr_view.php?id=') + encodeURIComponent(equipment.id);
        const detailQrSrc = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(publicUrl)}`;
        window.__currentEquipmentQrPrintData = {
          id: equipment.id || '',
          propertyNumber: getProp(equipment, 'property_number', 'propertyNumber') || '',
          qrSrc: detailQrSrc
        };
        const qrImg = document.getElementById('detailQrCode');
        if (qrImg) {
          qrImg.setAttribute('src', detailQrSrc);
          qrImg.style.cursor = 'pointer';
          qrImg.onclick = function() {
            window.open('../../../../public/qr_view.php?id=' + encodeURIComponent(equipment.id), '_blank');
          };
        }
      } catch (e) {
        console.warn('Failed to set detail QR image', e);
        window.__currentEquipmentQrPrintData = {
          id: equipment.id || '',
          propertyNumber: getProp(equipment, 'property_number', 'propertyNumber') || '',
          qrSrc: ''
        };
      }

      document.getElementById('equipmentDetailsModal').style.display = 'flex';
    }

    async function editEquipment(id) {
        const equipment = equipmentData[id];
        if (!equipment) return;

        // ensure users are loaded so selects exist and have options
        if (!usersData || usersData.length === 0) {
          await loadUsers();
        }

        // Populate form with equipment data
        // support different possible server keys
        document.getElementById('officeDevision').value = getProp(equipment, 'office_division', 'office_devision', 'officeDevision', 'officeDivision') || '';
        document.getElementById('equipmentType').value = equipment.equipment_type || '';
        document.getElementById('yearAcquired').value = equipment.year_acquired || '';
        document.getElementById('shelfLife').value = equipment.shelf_life || '';
        document.getElementById('brand').value = equipment.brand || '';
        document.getElementById('model').value = equipment.model || '';
        document.getElementById('processor').value = equipment.processor || '';
        document.getElementById('ramSize').value = equipment.ram_size || '';
        document.getElementById('gpu').value = equipment.gpu || '';
        document.getElementById('osVersion').value = equipment.os_version || '';
        document.getElementById('officeProductivity').value = equipment.office_productivity || '';
        document.getElementById('endpointProtection').value = equipment.endpoint_protection || '';
        document.getElementById('computerName').value = equipment.computer_name || '';
        document.getElementById('serialNumber').value = equipment.serial_number || '';
        document.getElementById('propertyNumber').value = equipment.property_number || '';

        // Set accountable person - try matching by full_name first, then by id (if equipment contains accountable_person_id)
        const accountableSelect = document.getElementById('accountablePerson');
        if (accountableSelect) {
          const byName = Array.from(accountableSelect.options).find(opt => opt.value === (equipment.accountable_person || ''));
          const byId = equipment.accountable_person_id ? Array.from(accountableSelect.options).find(opt => opt.getAttribute('data-id') === String(equipment.accountable_person_id)) : null;
          if (byName) accountableSelect.value = byName.value;
          else if (byId) accountableSelect.value = byId.value;
          else accountableSelect.value = equipment.accountable_person || '';
        }

        // Set actual user similarly
        const actualSelect = document.getElementById('actualUser');
        if (actualSelect) {
          const byName = Array.from(actualSelect.options).find(opt => opt.value === (equipment.actual_user || ''));
          const byId = equipment.actual_user_id ? Array.from(actualSelect.options).find(opt => opt.getAttribute('data-id') === String(equipment.actual_user_id)) : null;
          if (byName) actualSelect.value = byName.value;
          else if (byId) actualSelect.value = byId.value;
          else actualSelect.value = equipment.actual_user || '';
        }

        // Continue populating remaining fields
        document.getElementById('accountableSex').value = equipment.accountable_sex || '';
        document.getElementById('accountableEmployment').value = equipment.accountable_employment || '';
        document.getElementById('actualUserSex').value = equipment.actual_user_sex || '';
        document.getElementById('actualUserEmployment').value = equipment.actual_user_employment || '';
        document.getElementById('natureOfWork').value = equipment.nature_of_work || '';
        document.getElementById('remarks').value = equipment.remarks || '';
        // Populate the status select so edits include the current status
        const statusSelect = document.getElementById('status');
        if (statusSelect) {
          // Map DB value 'In Use' to UI label 'Assigned'
          const normalizedStatus = canonicalizeStatus(equipment.status || 'Assigned');
          if ((normalizedStatus || '').toString().toLowerCase() === 'in use') {
            statusSelect.value = 'Assigned';
          } else {
            const match = Array.from(statusSelect.options).find(opt => (opt.value || '').toLowerCase() === String(normalizedStatus || '').toLowerCase());
            statusSelect.value = match ? match.value : 'Assigned';
          }
        }

        // Change modal title and button
        document.querySelector('#addDeviceModal .modal-title').textContent = 'Edit Equipment';
        document.getElementById('addDeviceBtn').textContent = 'Update Equipment';
        document.getElementById('addDeviceBtn').setAttribute('data-edit-id', id);

        document.getElementById('addDeviceModal').style.display = 'flex';
      }

      async function deleteEquipment(id) {
        if (!confirm('Are you sure you want to delete this equipment?')) return;

        const result = await safeServiceCall(EquipmentService.delete(id));
        if (!result || result.error) {
          alert('Error deleting equipment: ' + (result && result.error ? result.error : 'Unknown error'));
          return;
        }

        if (result.success || result.deleted || result.id) {
          alert('Equipment deleted successfully!');
          await loadEquipmentData();
        } else {
          alert('Error deleting equipment: ' + (result.error || 'Unknown error'));
        }
      }

      window.__adminEquipmentActions = {
        view: async function(id) {
          await viewEquipmentDetails(id);
        },
        edit: async function(id) {
          await editEquipment(id);
        },
        delete: async function(id) {
          await deleteEquipment(id);
        },
        regenerate: async function(id) {
          if (!confirm('Regenerate QR for this equipment? This will overwrite existing QR image.')) return;
          const res = await safeServiceCall(EquipmentService.generateQR(id));
          if (!res || res.error || !res.success) {
            alert('Failed to generate QR: ' + (res && (res.error || JSON.stringify(res))));
            return;
          }
          alert('QR regenerated successfully. Refreshing list.');
          await loadEquipmentData();
        }
      };

      // Close modal functionality
      document.getElementById('closeModal').addEventListener('click', function() {
        document.getElementById('equipmentDetailsModal').style.display = 'none';
      });

      document.getElementById('equipmentDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
          this.style.display = 'none';
        }
      });

      // Add Device Modal functionality
      document.getElementById('addNewDeviceBtn').addEventListener('click', async function() {
        // ensure users are loaded before showing modal so dropdowns are populated
        if (!usersData || usersData.length === 0) {
          await loadUsers();
        } else {
          // refresh dropdowns in case usersData changed
          populateUserDropdowns();
        }

        document.getElementById('addDeviceForm').reset();
        document.querySelector('#addDeviceModal .modal-title').textContent = 'Add New Equipment';
        document.getElementById('addDeviceBtn').textContent = 'Add Equipment';
        document.getElementById('addDeviceBtn').removeAttribute('data-edit-id');
        document.getElementById('addDeviceModal').style.display = 'flex';
      });

      document.getElementById('closeAddDeviceModal').addEventListener('click', function() {
        document.getElementById('addDeviceModal').style.display = 'none';
      });

      document.getElementById('cancelAddDeviceBtn').addEventListener('click', function() {
        document.getElementById('addDeviceModal').style.display = 'none';
      });

      document.getElementById('addDeviceModal').addEventListener('click', function(e) {
        if (e.target === this) {
          this.style.display = 'none';
        }
      });

      function canonicalizeStatus(status) {
        const raw = (status || '').toString().trim().toLowerCase();
        if (raw === 'assigned' || raw === 'in use') return 'In Use';
        if (raw === 'available') return 'Available';
        if (raw === 'returned') return 'Returned';
        if (raw === 'under maintenance') return 'Under Maintenance';
        if (raw === 'missing') return 'Missing';
        if (raw === 'damaged') return 'Damaged';
        if (raw === 'out of service') return 'Out of Service';
        return status;
      }

      let equipmentSaveInProgress = false;

      // Add/Update equipment functionality
      document.getElementById('addDeviceBtn').addEventListener('click', async function() {
        if (equipmentSaveInProgress) return;

        equipmentSaveInProgress = true;
        const saveButton = this;
        const originalButtonText = saveButton.innerHTML;
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Saving...';

        try {
        // collect values from form (unchanged)
        const formData = {
          officeDevision: document.getElementById('officeDevision').value,
          equipmentType: document.getElementById('equipmentType').value,
          yearAcquired: document.getElementById('yearAcquired').value,
          shelfLife: document.getElementById('shelfLife').value,
          brand: document.getElementById('brand').value,
          model: document.getElementById('model').value,
          processor: document.getElementById('processor').value,
          ramSize: document.getElementById('ramSize').value,
          gpu: document.getElementById('gpu').value,
          osVersion: document.getElementById('osVersion').value,
          officeProductivity: document.getElementById('officeProductivity').value,
          endpointProtection: document.getElementById('endpointProtection').value,
          computerName: document.getElementById('computerName').value,
          serialNumber: document.getElementById('serialNumber').value,
          propertyNumber: document.getElementById('propertyNumber').value,
          accountablePerson: document.getElementById('accountablePerson').value,
          accountableSex: document.getElementById('accountableSex').value,
          accountableEmployment: document.getElementById('accountableEmployment').value,
          actualUser: document.getElementById('actualUser').value,
          actualUserSex: document.getElementById('actualUserSex').value,
          actualUserEmployment: document.getElementById('actualUserEmployment').value,
          natureOfWork: document.getElementById('natureOfWork').value,
          remarks: document.getElementById('remarks').value,
          status: (document.getElementById('status') ? document.getElementById('status').value : 'Assigned')
        };

        // simple validation (keep existing requirement)
        if (!formData.propertyNumber) {
          alert('Property Number is required!');
          return;
        }

        const normalizedStatus = canonicalizeStatus(formData.status || 'Assigned');
        const actualUserValue = (formData.actualUser || '').toString().trim();
        if (normalizedStatus === 'In Use' && actualUserValue === '') {
          alert('Actual user is required when status is Assigned.');
          return;
        }

        // normalize/mapping to expected backend keys (snake_case)
        const payload = {
          office_division: formData.officeDevision || formData.office_division || formData.officeDevision || '',
          equipment_type: formData.equipmentType || '',
          year_acquired: formData.yearAcquired || '',
          shelf_life: formData.shelfLife || '',
          brand: formData.brand || '',
          model: formData.model || '',
          processor: formData.processor || '',
          ram_size: formData.ramSize || '',
          gpu: formData.gpu || '',
          os_version: formData.osVersion || '',
          office_productivity: formData.officeProductivity || '',
          endpoint_protection: formData.endpointProtection || '',
          computer_name: formData.computerName || '',
          serial_number: formData.serialNumber || '',
          property_number: formData.propertyNumber || '',
          accountable_person: formData.accountablePerson || '',
          accountable_sex: formData.accountableSex || '',
          accountable_employment: formData.accountableEmployment || '',
          actual_user: actualUserValue,
          actual_user_sex: formData.actualUserSex || '',
          actual_user_employment: formData.actualUserEmployment || '',
          nature_of_work: formData.natureOfWork || '',
          remarks: formData.remarks || '',
          status: normalizedStatus || 'In Use'
        };

        const editId = this.getAttribute('data-edit-id');
        let result = null;
        try {
          console.log('Sending payload for ' + (editId ? ('update id=' + editId) : 'create') + ':', payload);
        } catch (e) { console.warn('Failed to log payload', e); }

        if (editId) {
          result = await safeServiceCall(EquipmentService.update(editId, payload));
        } else {
          result = await safeServiceCall(EquipmentService.create(payload));
        }

        if (!result || result.error) {
          alert('Error: ' + (result && result.error ? result.error : 'Unknown error'));
          return;
        }

        if (result.success || result.id) {
          // Debug: show server's saved object to confirm shelf_life persisted
          try {
            console.log('Equipment save result:', result);
            if (result.saved) {
              console.log('Saved object:', result.saved);
              console.log('Saved shelf_life:', result.saved.shelf_life);
              console.log('Saved status:', result.saved.status);
            }
          } catch (e) { console.warn('Logging response failed', e); }

          // Notify user and show the status that the server reports as saved
          if (result.saved && result.saved.status !== undefined) {
            alert((editId ? 'Equipment updated successfully!\n' : 'Equipment added successfully!\n') + 'Status saved as: ' + result.saved.status);
          } else {
            alert(editId ? 'Equipment updated successfully!' : 'Equipment added successfully!');
          }
          document.getElementById('addDeviceModal').style.display = 'none';
          await loadEquipmentData();
        } else {
          alert('Error: ' + (result.error || 'Unknown error'));
        }
        } finally {
          equipmentSaveInProgress = false;
          saveButton.disabled = false;
          saveButton.innerHTML = originalButtonText;
        }
      });
 
      // Search and filter functionality
      const searchInput = document.getElementById('searchInput');
      const statusFilter = document.getElementById('statusFilter');
      const clearFiltersBtn = document.getElementById('clearFiltersBtn');
      const searchInputMobile = document.getElementById('searchInputMobile');
      const searchInputModal = document.getElementById('searchInputModal');
      const statusFilterModal = document.getElementById('statusFilterModal');
      const openEquipmentFiltersMobile = document.getElementById('openEquipmentFiltersMobile');
      const equipmentMobileFilterModal = document.getElementById('equipmentMobileFilterModal');
      const equipmentActiveFilterChips = document.getElementById('equipmentActiveFilterChips');
      const clearEquipmentFiltersMobile = document.getElementById('clearEquipmentFiltersMobile');
      const applyEquipmentFiltersMobile = document.getElementById('applyEquipmentFiltersMobile');
      const printEquipmentListBtnMobile = document.getElementById('printEquipmentListBtnMobile');
      const exportEquipmentExcelBtnMobile = document.getElementById('exportEquipmentExcelBtnMobile');
      const printQRCodesBtnMobile = document.getElementById('printQRCodesBtnMobile');
      const addNewDeviceBtnMobile = document.getElementById('addNewDeviceBtnMobile');
      const yearAcquiredInput = document.getElementById('yearAcquired');

      if (yearAcquiredInput) {
        yearAcquiredInput.max = String(new Date().getFullYear());
      }
      const printTypeFilter = document.getElementById('printTypeFilter');
      const printTypeFilterModal = document.getElementById('printTypeFilterModal');

      function getSearchValue() {
        return searchInput ? searchInput.value.trim() : '';
      }

      function getStatusValue() {
        return statusFilter ? statusFilter.value : 'All';
      }

      function getPrintTypeValue() {
        return printTypeFilter ? (printTypeFilter.value || 'All') : 'All';
      }

      function normalizeEquipmentType(value) {
        return String(value || '').trim().toLowerCase();
      }

      function syncPrintTypeInputs(printType) {
        [printTypeFilter, printTypeFilterModal].forEach(select => {
          if (select) select.value = printType;
        });
      }

      function populatePrintTypeOptions() {
        const sourceSelect = document.getElementById('equipmentType');
        if (!sourceSelect) return;

        const optionMap = new Map();
        Array.from(sourceSelect.options).forEach(option => {
          const value = (option.value || '').trim();
          const text = (option.textContent || '').trim();
          if (!value || !text) return;
          if (!optionMap.has(value)) optionMap.set(value, text);
        });

        [printTypeFilter, printTypeFilterModal].forEach(select => {
          if (!select) return;
          const selectedValue = select.value || 'All';
          select.innerHTML = '<option value="All">Select Equipment Type</option>';
          optionMap.forEach((text, value) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = text;
            select.appendChild(option);
          });
          select.value = optionMap.has(selectedValue) || selectedValue === 'All' ? selectedValue : 'All';
        });
      }

      function syncFilterInputs(search, status) {
        [searchInput, searchInputMobile, searchInputModal].forEach(input => {
          if (input) input.value = search;
        });
        [statusFilter, statusFilterModal].forEach(select => {
          if (select) select.value = status;
        });
      }

      function updateFilterChips(search, status, equipmentType) {
        if (!equipmentActiveFilterChips) return;
        equipmentActiveFilterChips.innerHTML = '';
        equipmentActiveFilterChips.style.display = 'none';
      }

      async function applyEquipmentFilters(search, status) {
        const normalizedSearch = (search || '').trim();
        const normalizedStatus = status || 'All';
        syncFilterInputs(normalizedSearch, normalizedStatus);
        updateFilterChips(normalizedSearch, normalizedStatus, getPrintTypeValue());
        await loadEquipmentData(normalizedSearch, normalizedStatus);
        filterRenderedEquipmentRows(normalizedSearch, getPrintTypeValue());
      }

      function openEquipmentFilterSheet() {
        if (!equipmentMobileFilterModal) return;
        syncFilterInputs(getSearchValue(), getStatusValue());
        syncPrintTypeInputs(getPrintTypeValue());
        equipmentMobileFilterModal.classList.add('is-open');
        equipmentMobileFilterModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
      }

      function closeEquipmentFilterSheet() {
        if (!equipmentMobileFilterModal) return;
        equipmentMobileFilterModal.classList.remove('is-open');
        equipmentMobileFilterModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      }

      const debouncedApplyEquipmentFilters = debounce(async function(search, status) {
        await applyEquipmentFilters(search, status);
      }, 250);

      if (searchInput) {
        searchInput.addEventListener('input', function() {
          debouncedApplyEquipmentFilters(this.value, getStatusValue());
        });
      }

      if (searchInputMobile) {
        searchInputMobile.addEventListener('input', function() {
          debouncedApplyEquipmentFilters(this.value, getStatusValue());
        });
      }

      if (searchInputModal) {
        searchInputModal.addEventListener('input', function() {
          debouncedApplyEquipmentFilters(this.value, statusFilterModal ? statusFilterModal.value : getStatusValue());
        });
      }

      if (statusFilter) {
        statusFilter.addEventListener('change', async function() {
          await applyEquipmentFilters(getSearchValue(), this.value);
        });
      }

      if (statusFilterModal) {
        statusFilterModal.addEventListener('change', async function() {
          await applyEquipmentFilters(searchInputModal ? searchInputModal.value : getSearchValue(), this.value);
        });
      }

      if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', async function() {
          await applyEquipmentFilters('', 'All');
        });
      }

      if (clearEquipmentFiltersMobile) {
        clearEquipmentFiltersMobile.addEventListener('click', async function() {
          await applyEquipmentFilters('', 'All');
        });
      }

      if (applyEquipmentFiltersMobile) {
        applyEquipmentFiltersMobile.addEventListener('click', async function() {
          await applyEquipmentFilters(
            searchInputModal ? searchInputModal.value : getSearchValue(),
            statusFilterModal ? statusFilterModal.value : getStatusValue()
          );
          closeEquipmentFilterSheet();
        });
      }

      if (openEquipmentFiltersMobile) {
        openEquipmentFiltersMobile.addEventListener('click', openEquipmentFilterSheet);
      }

      if (equipmentMobileFilterModal) {
        equipmentMobileFilterModal.querySelectorAll('[data-close-equipment-filters="true"]').forEach(button => {
          button.addEventListener('click', closeEquipmentFilterSheet);
        });
      }

      if (printEquipmentListBtnMobile) {
        printEquipmentListBtnMobile.addEventListener('click', function() {
          if (printTypeFilterModal && printTypeFilter) {
            printTypeFilter.value = printTypeFilterModal.value || 'All';
          }
          const desktopButton = document.getElementById('printEquipmentListBtn');
          if (desktopButton) desktopButton.click();
        });
      }

      if (exportEquipmentExcelBtnMobile) {
        exportEquipmentExcelBtnMobile.addEventListener('click', function() {
          if (printTypeFilterModal && printTypeFilter) {
            printTypeFilter.value = printTypeFilterModal.value || 'All';
          }
          const desktopButton = document.getElementById('exportEquipmentExcelBtn');
          if (desktopButton) desktopButton.click();
        });
      }


      if (printTypeFilter) {
        printTypeFilter.addEventListener('change', function() {
          const selectedType = this.value || 'All';
          syncPrintTypeInputs(selectedType);
          updateFilterChips(getSearchValue(), getStatusValue(), selectedType);
          filterRenderedEquipmentRows(getSearchValue(), selectedType);
        });
      }

      if (printTypeFilterModal) {
        printTypeFilterModal.addEventListener('change', function() {
          syncPrintTypeInputs(this.value || 'All');
        });
      }

      if (printQRCodesBtnMobile) {
        printQRCodesBtnMobile.addEventListener('click', function() {
          const desktopButton = document.getElementById('printQRCodesBtn');
          if (desktopButton) desktopButton.click();
        });
      }

      if (addNewDeviceBtnMobile) {
        addNewDeviceBtnMobile.addEventListener('click', function() {
          const desktopButton = document.getElementById('addNewDeviceBtn');
          if (desktopButton) desktopButton.click();
        });
      }

      syncFilterInputs(getSearchValue(), getStatusValue());
      populatePrintTypeOptions();
      syncPrintTypeInputs(getPrintTypeValue());
      updateFilterChips(getSearchValue(), getStatusValue(), getPrintTypeValue());
 
      // Debounce helper
      function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
          const later = () => {
            clearTimeout(timeout);
            func(...args);
          };
          clearTimeout(timeout);
          timeout = setTimeout(later, wait);
        };
      }
 
      function buildPrintableEquipmentList() {
        const now = new Date();
        const currentDate = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        const currentTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        const statusFilterEl = document.getElementById('statusFilter');
        const filterValue = statusFilterEl ? (statusFilterEl.value || 'All') : 'All';
        const printTypeValue = getPrintTypeValue();

        document.getElementById('footerDate').textContent = `${currentDate} at ${currentTime}`;
        const footerFilterEl = document.getElementById('footerFilter');
        if (footerFilterEl) footerFilterEl.textContent = filterValue;
        const footerTypeFilterEl = document.getElementById('footerTypeFilter');
        if (footerTypeFilterEl) footerTypeFilterEl.textContent = printTypeValue;

        const printTableBody = document.getElementById('printTableBody');
        printTableBody.innerHTML = '';

        const items = Object.values(equipmentData || {}).filter(eq => {
          if (printTypeValue === 'All') return true;
          return normalizeEquipmentType(eq.equipment_type) === normalizeEquipmentType(printTypeValue);
        });
        let totalCount = 0;
        const rowsData = [];

        if (!items || items.length === 0) {
          printTableBody.innerHTML = '<tr><td colspan="19" style="text-align:center;">No equipment to print</td></tr>';
        } else {
          const rows = [];
          items.forEach(eq => {
            // show only loaded/visible items (equipmentData holds loaded results based on filters)
            const id = eq.id || '';
            const prop = eq.property_number || '';
            const type = eq.equipment_type || '';
            const brandModel = ((eq.brand || '') + ' / ' + (eq.model || '')).replace(/^\s*\/\s*$/, '');
            const year = eq.year_acquired || '';
            const office = getProp(eq, 'office_division', 'office_devision', 'officeDevision', 'officeDivision') || '';
            const accountable = eq.accountable_person || '';
            const accountable_sex = eq.accountable_sex || '';
            const accountable_employment = eq.accountable_employment || '';
            const actual = eq.actual_user || '';
            const actual_sex = eq.actual_user_sex || '';
            const actual_employment = eq.actual_user_employment || '';
            const nature = eq.nature_of_work || '';
            const specs = [(eq.processor || ''), (eq.ram_size || ''), (eq.gpu || '')].filter(Boolean).join(' / ');
            const software = [(eq.office_productivity || ''), (eq.endpoint_protection || '')].filter(Boolean).join(' / ');
            const serial = eq.serial_number || '';
            const shelf = eq.shelf_life || '';
            const statusRaw = canonicalizeStatus((eq.status || '').toString());
            const statusDisplay = statusRaw.toLowerCase() === 'in use' ? 'Assigned' : statusRaw;
            const remarks = eq.remarks || '';
            const rowData = [
              id,
              prop,
              type,
              brandModel,
              year,
              office,
              accountable,
              accountable_sex,
              accountable_employment,
              actual,
              actual_sex,
              actual_employment,
              nature,
              specs,
              software,
              serial,
              shelf,
              statusDisplay,
              remarks
            ];
            rowsData.push(rowData);

            rows.push(`<tr>
              <td>${escapeHtml(rowData[0])}</td>
              <td>${escapeHtml(rowData[1])}</td>
              <td>${escapeHtml(rowData[2])}</td>
              <td>${escapeHtml(rowData[3])}</td>
              <td>${escapeHtml(rowData[4])}</td>
              <td>${escapeHtml(rowData[5])}</td>
              <td>${escapeHtml(rowData[6])}</td>
              <td>${escapeHtml(rowData[7])}</td>
              <td>${escapeHtml(rowData[8])}</td>
              <td>${escapeHtml(rowData[9])}</td>
              <td>${escapeHtml(rowData[10])}</td>
              <td>${escapeHtml(rowData[11])}</td>
              <td>${escapeHtml(rowData[12])}</td>
              <td>${escapeHtml(rowData[13])}</td>
              <td>${escapeHtml(rowData[14])}</td>
              <td>${escapeHtml(rowData[15])}</td>
              <td>${escapeHtml(rowData[16])}</td>
              <td>${escapeHtml(rowData[17])}</td>
              <td>${escapeHtml(rowData[18])}</td>
            </tr>`);

            totalCount++;
          });

          printTableBody.innerHTML = rows.join('\n');
        }

        document.getElementById('totalCount').textContent = totalCount;
        return { printTypeValue, rowsData };
      }

      function getExportFilename(printTypeValue, extension) {
        const stamp = new Date().toISOString().slice(0, 10);
        const typePart = (printTypeValue && printTypeValue !== 'All')
          ? printTypeValue.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
          : 'all-types';
        return `equipment-list-${typePart}-${stamp}.${extension}`;
      }

      function exportEquipmentExcel(meta) {
        if (typeof XLSX === 'undefined') {
          alert('Excel export library failed to load. Please try again.');
          return;
        }

        const headers = [
          'Asset ID',
          'Property No.',
          'Type',
          'Brand / Model',
          'Year',
          'Office/Division',
          'Accountable Person',
          'A. Sex',
          'A. Employment',
          'Actual User',
          'U. Sex',
          'U. Employment',
          'Nature of Work',
          'Specs (Proc / RAM / GPU)',
          'Software / Protection',
          'Serial No.',
          'Shelf Life',
          'Status',
          'Remarks'
        ];

        const generatedAt = new Date().toLocaleString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });

        const sheetData = [
          ['Equipment List Report'],
          ['Status Filter', document.getElementById('footerFilter') ? document.getElementById('footerFilter').textContent : 'All', 'Type Filter', meta.printTypeValue || 'All', 'Generated', generatedAt],
          [],
          headers
        ].concat(meta.rowsData || []);

        const ws = XLSX.utils.aoa_to_sheet(sheetData);
        ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: headers.length - 1 } }];
        ws['!freeze'] = { xSplit: 0, ySplit: 4 };
        ws['!autofilter'] = { ref: `A4:${XLSX.utils.encode_col(headers.length - 1)}4` };
        ws['!cols'] = [
          { wch: 10 }, { wch: 14 }, { wch: 18 }, { wch: 22 }, { wch: 8 },
          { wch: 18 }, { wch: 24 }, { wch: 10 }, { wch: 18 }, { wch: 24 },
          { wch: 10 }, { wch: 18 }, { wch: 22 }, { wch: 26 }, { wch: 24 },
          { wch: 18 }, { wch: 12 }, { wch: 14 }, { wch: 24 }
        ];
        ws['!rows'] = [{ hpt: 24 }, { hpt: 20 }, { hpt: 8 }, { hpt: 28 }];

        const range = XLSX.utils.decode_range(ws['!ref']);
        const border = {
          top: { style: 'thin', color: { rgb: 'B7C0CE' } },
          bottom: { style: 'thin', color: { rgb: 'B7C0CE' } },
          left: { style: 'thin', color: { rgb: 'B7C0CE' } },
          right: { style: 'thin', color: { rgb: 'B7C0CE' } }
        };

        for (let row = range.s.r; row <= range.e.r; row++) {
          for (let col = range.s.c; col <= range.e.c; col++) {
            const cellRef = XLSX.utils.encode_cell({ r: row, c: col });
            if (!ws[cellRef]) continue;

            ws[cellRef].s = {
              font: { name: 'Arial', sz: 10 },
              alignment: { vertical: 'center', horizontal: col === 0 || col === 4 || col === 7 || col === 10 || col === 16 ? 'center' : 'left', wrapText: true },
              border: border
            };

            if (row === 0) {
              ws[cellRef].s = {
                font: { name: 'Arial', sz: 14, bold: true, color: { rgb: 'FFFFFF' } },
                fill: { fgColor: { rgb: '1F4E78' } },
                alignment: { vertical: 'center', horizontal: 'center' },
                border: border
              };
            } else if (row === 1) {
              ws[cellRef].s = {
                font: { name: 'Arial', sz: 10, bold: col % 2 === 0 },
                fill: { fgColor: { rgb: 'D9EAF7' } },
                alignment: { vertical: 'center', horizontal: col % 2 === 0 ? 'center' : 'left', wrapText: true },
                border: border
              };
            } else if (row === 3) {
              ws[cellRef].s = {
                font: { name: 'Arial', sz: 10, bold: true, color: { rgb: 'FFFFFF' } },
                fill: { fgColor: { rgb: '4F81BD' } },
                alignment: { vertical: 'center', horizontal: 'center', wrapText: true },
                border: border
              };
            } else if (row > 3) {
              const statusValue = String(ws[XLSX.utils.encode_cell({ r: row, c: 17 })]?.v || '').toLowerCase();
              const baseFill = row % 2 === 0 ? 'F8FBFF' : 'FFFFFF';
              ws[cellRef].s.fill = { fgColor: { rgb: baseFill } };

              if (col === 17) {
                let statusFill = 'E2E3E5';
                let statusFont = '212529';
                if (statusValue.includes('assigned')) { statusFill = 'D1E7DD'; statusFont = '0F5132'; }
                else if (statusValue.includes('available')) { statusFill = 'CFE2FF'; statusFont = '084298'; }
                else if (statusValue.includes('maintenance')) { statusFill = 'FFF3CD'; statusFont = '664D03'; }
                else if (statusValue.includes('missing')) { statusFill = 'FCE8B2'; statusFont = '8A4B00'; }
                else if (statusValue.includes('damaged')) { statusFill = 'F8D7DA'; statusFont = '842029'; }
                else if (statusValue.includes('out of service')) { statusFill = 'E2E3E5'; statusFont = '41464B'; }
                else if (statusValue.includes('returned')) { statusFill = 'D1ECF1'; statusFont = '055160'; }

                ws[cellRef].s.fill = { fgColor: { rgb: statusFill } };
                ws[cellRef].s.font = { name: 'Arial', sz: 10, bold: true, color: { rgb: statusFont } };
                ws[cellRef].s.alignment = { vertical: 'center', horizontal: 'center', wrapText: true };
              }
            }
          }
        }

        const wb = XLSX.utils.book_new();
        wb.Props = {
          Title: 'Equipment List Report',
          Subject: 'Equipment Inventory Export',
          Author: 'CENRO Equipment Management',
          CreatedDate: new Date()
        };
        XLSX.utils.book_append_sheet(wb, ws, 'Equipment List');
        XLSX.writeFile(wb, getExportFilename(meta.printTypeValue, 'xlsx'));
      }

      window.__adminEquipmentTopActions = {
        printList: function() {
          printEquipmentList();
        },
        exportExcel: function() {
          const meta = buildPrintableEquipmentList();
          exportEquipmentExcel(meta);
        }
      };

      // Print Equipment List Button Event
      document.getElementById('printEquipmentListBtn').addEventListener('click', function() {
        printEquipmentList();
      });

      const exportEquipmentExcelBtn = document.getElementById('exportEquipmentExcelBtn');
      if (exportEquipmentExcelBtn) {
        exportEquipmentExcelBtn.addEventListener('click', function() {
          const meta = buildPrintableEquipmentList();
          exportEquipmentExcel(meta);
        });
      }

      function printEquipmentList() {
        buildPrintableEquipmentList();

        // Show and print
        const printContainer = document.getElementById('printContainer');
        printContainer.style.display = 'block';
        setTimeout(function() {
          window.print();
          setTimeout(function() { printContainer.style.display = 'none'; }, 200);
        }, 150);
      }

      // small helper to avoid XSS-injecting innerHTML
      function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }
      
      // Print QR Codes functionality
      const printQRBtn = document.getElementById('printQRCodesBtn');
      if (printQRBtn) {
        printQRBtn.addEventListener('click', function() {
          printQRCodes();
        });

      }
      
      function printQRCodes() {
        // Create a new window for printing QR codes
        const printWindow = window.open('', '_blank');
        
        // Get equipment data from table
        const equipmentTable = document.getElementById('equipmentTable');
        const rows = equipmentTable.querySelector('tbody').querySelectorAll('tr');
        const qrEquipmentData = [];
        
        rows.forEach(function(row) {
          if (row.style.display !== 'none') {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
              const equipmentId = cells[0].textContent.trim();
              const qrPayload = (window.CENRO_QR_VIEW_BASE_URL || '../../../../public/qr_view.php?id=') + encodeURIComponent(equipmentId);
              const qrSrc = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(qrPayload)}`;
              qrEquipmentData.push({
                propertyNumber: cells[1].textContent.trim(),
                qrSrc: qrSrc
              });
            }
          }
        });
        
        printWindow.document.write(`
          <!DOCTYPE html>
          <html>
          <head>
            <title>QR Codes - CENRO NASIPIT</title>
            <style>
              @page {
                size: letter portrait;
                margin: 0.4in;
              }
              body {
                font-family: "Times New Roman", Times, serif;
                margin: 0;
                padding: 0;
                background: white;
              }
              .qr-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.2in;
                margin: 0;
              }
              .qr-card {
                border: 2px solid #2c5530;
                padding: 0.08in;
                text-align: center;
                background: white;
                page-break-inside: avoid;
                width: 100%;
                height: 3.25in;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
              }
              .header {
                text-align: center;
                margin-bottom: 0.06in;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.08in;
              }
              .denr-logo {
                width: 50px;
                height: 50px;
                object-fit: contain;
              }
              .header-text {
                flex: 1;
                text-align: center;
              }
              .header h3 {
                color: #000;
                margin: 2px 0;
                font-size: 12px;
                line-height: 1.1;
                font-weight: bold;
                font-family: "Times New Roman", Times, serif;
              }
              .header h4 {
                color: #000;
                margin: 1px 0;
                font-size: 10px;
                line-height: 1.1;
                font-weight: normal;
                font-family: "Times New Roman", Times, serif;
              }
              .property-title {
                background: #2c5530;
                color: white;
                padding: 4px;
                margin: 0.06in 0 0.08in 0;
                font-weight: bold;
                font-size: 11px;
                letter-spacing: 1px;
                width: 100%;
                font-family: "Times New Roman", Times, serif;
              }
              .qr-code {
                margin: 0.05in 0;
              }
              .qr-code img {
                width: 132px;
                height: 132px;
                border: 1px solid #ccc;
              }
              .property-number {
                font-weight: bold;
                font-size: 13px;
                color: #2c5530;
                margin-top: 0.08in;
                text-transform: uppercase;
                letter-spacing: 1px;
                font-family: "Times New Roman", Times, serif;
              }
              @media print {
                html, body {
                  width: 100%;
                  height: auto;
                }
                body {
                  margin: 0;
                  padding: 0;
                  -webkit-print-color-adjust: exact;
                  print-color-adjust: exact;
                }
                .qr-grid { gap: 0.2in; }
                .qr-card { 
                  page-break-inside: avoid;
                  break-inside: avoid;
                  margin-bottom: 0;
                }
              }
            </style>
          </head>
          <body>
            <div class="qr-grid">
        `);
        
        // Generate QR code cards
        qrEquipmentData.forEach(equipment => {
          printWindow.document.write(`
            <div class="qr-card">
              <div class="header">
                <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo" class="denr-logo">
                <div class="header-text">
                  <h3>Department of Environment and Natural Resources</h3>
                  <h4>Community Environment and Natural Resources Office</h4>
                  <h4>CENRO Nasipit, Agusan del Norte</h4>
                </div>
              </div>
              
              <div class="property-title">RP GOVERNMENT PROPERTY</div>
              
              <div class="qr-code">
                ${ equipment.qrSrc 
                    ? ('<img src="' + equipment.qrSrc + '" alt="QR Code">')
                    : ('<div style="width:150px;height:150px;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;color:#666">No QR</div>') }
              </div>
              
              <div class="property-number">${equipment.propertyNumber}</div>
            </div>
          `);
        });
        
        printWindow.document.write(`
            </div>
          </body>
          </html>
        `);
        
        printWindow.document.close();
        
        // Wait for images to load before printing
        setTimeout(() => {
          printWindow.print();
        }, 1000);
      }
    });

    // Global functions for modal actions
    function closeEquipmentDetails() {
      document.getElementById('equipmentDetailsModal').style.display = 'none';
    }

    function printEquipmentDetails() {
      const modal = document.getElementById('equipmentDetailsModal');
      const modalStyle = modal.style.display;
      modal.style.display = 'none';
      
      setTimeout(() => {
        window.print();
        modal.style.display = modalStyle;
      }, 100);
    }

    function printQRCode() {
      const equipment = window.__currentEquipmentQrPrintData;
      if (!equipment || !equipment.id) {
        alert('Please open an equipment record first before printing its QR code.');
        return;
      }

      const escapeText = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

      const printWindow = window.open('', '_blank');
      if (!printWindow) {
        alert('Please allow pop-ups to print the QR code.');
        return;
      }

      const propertyNumber = escapeText(equipment.propertyNumber || equipment.id);
      const qrMarkup = equipment.qrSrc
        ? `<img src="${escapeText(equipment.qrSrc)}" alt="QR Code">`
        : '<div style="width:150px;height:150px;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;color:#666">No QR</div>';

      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>QR Code - CENRO NASIPIT</title>
          <style>
            @page { size: letter portrait; margin: 0.4in; }
            body {
              font-family: "Times New Roman", Times, serif;
              margin: 0;
              padding: 0;
              background: white;
            }
            .qr-grid {
              display: grid;
              grid-template-columns: repeat(2, 1fr);
              gap: 0.2in;
              margin: 0;
            }
            .qr-card {
              border: 2px solid #2c5530;
              padding: 0.08in;
              text-align: center;
              background: white;
              page-break-inside: avoid;
              width: 100%;
              height: 3.25in;
              box-sizing: border-box;
              display: flex;
              flex-direction: column;
              justify-content: center;
              align-items: center;
            }
            .header {
              text-align: center;
              margin-bottom: 0.06in;
              display: flex;
              align-items: center;
              justify-content: center;
              gap: 0.08in;
            }
            .denr-logo {
              width: 50px;
              height: 50px;
              object-fit: contain;
            }
            .header-text {
              flex: 1;
              text-align: center;
            }
            .header h3 {
              color: #000;
              margin: 2px 0;
              font-size: 12px;
              line-height: 1.1;
              font-weight: bold;
              font-family: "Times New Roman", Times, serif;
            }
            .header h4 {
              color: #000;
              margin: 1px 0;
              font-size: 10px;
              line-height: 1.1;
              font-weight: normal;
              font-family: "Times New Roman", Times, serif;
            }
            .property-title {
              background: #2c5530;
              color: white;
              padding: 4px;
              margin: 0.06in 0 0.08in 0;
              font-weight: bold;
              font-size: 11px;
              letter-spacing: 1px;
              width: 100%;
              font-family: "Times New Roman", Times, serif;
            }
            .qr-code {
              margin: 0.05in 0;
            }
            .qr-code img {
              width: 132px;
              height: 132px;
              border: 1px solid #ccc;
            }
            .property-number {
              font-weight: bold;
              font-size: 13px;
              color: #2c5530;
              margin-top: 0.08in;
              text-transform: uppercase;
              letter-spacing: 1px;
              font-family: "Times New Roman", Times, serif;
            }
            @media print {
              html, body { width: 100%; height: auto; }
              body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
              }
              .qr-grid { gap: 0.2in; }
              .qr-card {
                page-break-inside: avoid;
                break-inside: avoid;
                margin-bottom: 0;
              }
            }
          </style>
        </head>
        <body>
          <div class="qr-grid">
            <div class="qr-card">
              <div class="header">
                <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo" class="denr-logo">
                <div class="header-text">
                  <h3>Department of Environment and Natural Resources</h3>
                  <h4>Community Environment and Natural Resources Office</h4>
                  <h4>CENRO Nasipit, Agusan del Norte</h4>
                </div>
              </div>
              <div class="property-title">RP GOVERNMENT PROPERTY</div>
              <div class="qr-code">${qrMarkup}</div>
              <div class="property-number">${propertyNumber}</div>
            </div>
          </div>
        </body>
        </html>
      `);

      printWindow.document.close();
      setTimeout(() => {
        printWindow.print();
      }, 1000);
    }
