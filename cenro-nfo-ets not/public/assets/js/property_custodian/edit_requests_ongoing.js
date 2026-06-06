(function () {
  // Visual style for required signature (red edge + badge + subtle pulse).
  const style = document.createElement('style');
  style.innerHTML = `
    .signature-box{ position: relative; }
    .signature-box.sig-required{ border:2px solid #b72b2b !important; box-shadow:0 0 8px rgba(183,43,43,0.18); animation: sigPulse 1.6s ease-in-out infinite; }
    .signature-box.sig-required::after{ content: 'Required'; position: absolute; top: -10px; right: -2px; background: #b72b2b; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 3px; font-weight: 700; }
    @keyframes sigPulse{ 0%{ box-shadow:0 0 0 rgba(183,43,43,0.06);} 50%{ box-shadow:0 0 14px rgba(183,43,43,0.12);} 100%{ box-shadow:0 0 0 rgba(183,43,43,0.06);} }
  `;
  document.head.appendChild(style);

  const CURRENT_USER_ID = document.body.dataset.currentUserId || '';
  let currentField = null;
  const sigModalEl = document.getElementById('signatureModal');
  const canvas = document.getElementById('sigCanvas');
  let signaturePad = null;
  const sigModal = new bootstrap.Modal(sigModalEl);

  const actionStaffOptionsTemplate = document.getElementById('actionStaffOptionsTemplate');
  const staffOptionsHtml = actionStaffOptionsTemplate ? actionStaffOptionsTemplate.innerHTML : '<option value="">-- Select staff --</option>';

  function ensurePreviewIsImg(id) {
    const existing = document.getElementById(id);
    if (!existing) return null;
    if (existing.tagName === 'IMG') return existing;
    const img = document.createElement('img');
    img.id = id;
    img.style.maxHeight = '48px';
    img.style.maxWidth = '100%';
    img.style.display = 'block';
    existing.parentNode.replaceChild(img, existing);
    return img;
  }

  function resizeCanvas() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = canvas.getBoundingClientRect();
    // Guard: if modal is hidden, rect may be 0. Use sensible fallback size.
    const w = rect.width || 400;
    const h = rect.height || 200;
    canvas.width = w * ratio;
    canvas.height = h * ratio;
    const ctx = canvas.getContext('2d');
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.scale(ratio, ratio);
  }

  function createSignaturePad() {
    if (signaturePad) {
      try {
        signaturePad.off && signaturePad.off();
      } catch (e) {}
      signaturePad = null;
    }
    signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgba(255,255,255,0)' });
  }

  window.addEventListener('resize', function () {
    const prevData = signaturePad && !signaturePad.isEmpty() ? signaturePad.toDataURL() : null;
    resizeCanvas();
    createSignaturePad();
    if (prevData) {
      try {
        signaturePad.fromDataURL(prevData);
      } catch (e) {
        console.warn('restore prev signature failed', e);
      }
    }
  });

  sigModalEl.addEventListener('shown.bs.modal', function () {
    const previewId = (currentField || '') + '_preview';
    resizeCanvas();
    createSignaturePad();
    const preview = document.getElementById(previewId);
    if (preview && preview.tagName === 'IMG' && preview.src) {
      if (preview.src.indexOf('data:') === 0) {
        try {
          signaturePad.fromDataURL(preview.src);
        } catch (e) {
          signaturePad.clear();
          console.warn('fromDataURL failed', e);
        }
      } else {
        signaturePad.clear();
      }
    } else {
      signaturePad.clear();
    }
  });

  // Use event delegation so dynamically added .signature-box elements work.
  document.addEventListener('click', function (e) {
    const box = e.target.closest && e.target.closest('.signature-box');
    if (!box) return;
    const field = box.getAttribute('data-field') || '';

    // If this is an action signature, only allow the assigned staff (current user) to open the pad.
    if (field.indexOf('action_sig_') === 0) {
      let staffId = box.getAttribute('data-staff-id') || '';
      if (!staffId) {
        const row = box.closest && box.closest('tr[data-action-row]');
        if (row) {
          const sel = row.querySelector('select[name="action_staff[]"]');
          if (sel) staffId = sel.value || '';
        }
      }
      if (!staffId || String(staffId) !== String(CURRENT_USER_ID)) {
        alert('Only the assigned Action Staff may sign this action.');
        return;
      }
    }

    currentField = field;
    sigModal.show();
  });

  document.getElementById('sigClear').addEventListener('click', function () {
    if (signaturePad) signaturePad.clear();
  });

  function savePadToHidden() {
    if (!signaturePad) return false;
    const hidden = document.getElementById(currentField + '_signature_data');
    const previewId = (currentField || '') + '_preview';
    const preview = ensurePreviewIsImg(previewId) || document.getElementById(previewId);

    try {
      if (signaturePad.isEmpty()) {
        if (preview) {
          preview.src = '';
          preview.style.display = 'none';
        }
        if (hidden) hidden.value = '';
        return false;
      }

      let dataURL;
      try {
        dataURL = signaturePad.toDataURL('image/png');
      } catch (err) {
        console.warn('signaturePad.toDataURL failed, falling back to canvas.toDataURL', err);
        dataURL = canvas.toDataURL('image/png');
      }

      if (hidden) hidden.value = dataURL;
      if (preview) {
        preview.src = dataURL;
        preview.style.display = 'block';
      }
      console.log('Signature saved to hidden for', currentField);
      return true;
    } catch (err) {
      console.error('Signature export failed:', err);
      alert('Hindi ma-save ang signature: browser security restriction or invalid image. I-clear at muling i-draw, o i-upload ang scanned signature.');
      return false;
    }
  }

  document.getElementById('sigSave').addEventListener('click', function () {
    savePadToHidden();
    sigModal.hide();
  });

  sigModalEl.addEventListener('hide.bs.modal', function () {
    try {
      savePadToHidden();
    } catch (e) {
      console.error('autosave failed', e);
    }
  });

  const editForm = document.getElementById('editForm');
  if (editForm) {
    editForm.addEventListener('submit', function (e) {
      const a1 = document.getElementById('auth1_signature_data');
      const a2 = document.getElementById('auth2_signature_data');
      console.log(
        'Submitting form - auth1 signature length:',
        a1 && a1.value ? a1.value.length : 0,
        'auth2:',
        a2 && a2.value ? a2.value.length : 0
      );

      const completed = document.getElementById('completed_checkbox');

      function findMissingSignatures() {
        const rows = document.querySelectorAll('tr[data-action-row]');
        const missing = [];
        rows.forEach(function (row) {
          const sel = row.querySelector('select[name="action_staff[]"]');
          if (!sel) return;
          const staffId = sel.value || '';
          if (!staffId) return;
          const idx = row.getAttribute('data-action-row');
          const hid = document.getElementById('action_sig_' + idx + '_signature_data');
          const existing = row.querySelector('input[name="action_existing_signature_path[]"]');
          const hasNew = hid && hid.value && hid.value.trim() !== '';
          const hasExisting = existing && existing.value && existing.value.trim() !== '';
          if (!hasNew && !hasExisting) {
            missing.push({
              row: row,
              staffId: staffId,
              staffName: (sel.options[sel.selectedIndex] && sel.options[sel.selectedIndex].text) || ''
            });
          }
        });
        return missing;
      }

      function updateHighlights(missing) {
        document.querySelectorAll('.signature-box.sig-required').forEach(function (el) {
          el.classList.remove('sig-required');
        });
        if (!missing || missing.length === 0) return;
        missing.forEach(function (item) {
          const box = item.row.querySelector('.signature-box');
          if (box) box.classList.add('sig-required');
        });
      }

      function validateCompletedUI(showAlert) {
        if (!completed || !completed.checked) {
          updateHighlights([]);
          return true;
        }

        const missing = findMissingSignatures();
        updateHighlights(missing);
        if (missing.length > 0) {
          if (showAlert) {
            const names = missing.map(function (m) { return m.staffName || m.staffId; }).join(', ');
            alert('Cannot mark as Completed. Missing signature from the assigned Action Staff: ' + names);
          }
          if (missing[0] && missing[0].row) {
            missing[0].row.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          return false;
        }

        return true;
      }

      if (completed) {
        completed.addEventListener('change', function () {
          validateCompletedUI(false);
        });
      }

      document.getElementById('actions_tbody').addEventListener('change', function (ev) {
        const sel = ev.target.closest && ev.target.closest('select[name="action_staff[]"]');
        if (sel) {
          const row = sel.closest('tr[data-action-row]');
          if (row) {
            const box = row.querySelector('.signature-box');
            const previousStaffId = box ? (box.getAttribute('data-staff-id') || '') : '';
            if (box) box.setAttribute('data-staff-id', sel.value || '');
            if (previousStaffId && previousStaffId !== (sel.value || '')) {
              const hiddenSig = row.querySelector('input[name="action_signature_data[]"]');
              const existingSig = row.querySelector('input[name="action_existing_signature_path[]"]');
              const preview = row.querySelector('[id^="action_sig_"][id$="_preview"]');
              if (hiddenSig) hiddenSig.value = '';
              if (existingSig) existingSig.value = '';
              if (preview) {
                if (preview.tagName && preview.tagName.toLowerCase() === 'img') {
                  const blank = document.createElement('div');
                  blank.id = preview.id;
                  blank.style.cssText = 'width:100%; height:100%;';
                  preview.replaceWith(blank);
                } else {
                  preview.innerHTML = '';
                }
              }
            }
          }
          validateCompletedUI(false);
        }
      });

      document.getElementById('actions_tbody').addEventListener('input', function (ev) {
        const hid = ev.target.closest && ev.target.closest('input[id^="action_sig_"]');
        if (hid && hid.id) {
          const m = hid.id.match(/action_sig_(\d+)_signature_data/);
          if (m) {
            const idx = m[1];
            const row = document.querySelector('tr[data-action-row="' + idx + '"]');
            if (row) {
              const box = row.querySelector('.signature-box');
              if (box) box.classList.remove('sig-required');
            }
          }
          validateCompletedUI(false);
        }
      });

      if (!validateCompletedUI(false)) {
        e.preventDefault();
        validateCompletedUI(true);
        return false;
      }

      return true;
    });
  }

  // Add-row handling.
  (function () {
    const tbody = document.getElementById('actions_tbody');
    const addBtn = document.getElementById('addActionRow');
    let nextIndex = tbody.querySelectorAll('tr[data-action-row]').length + 1;

    function createRow(index) {
      const tr = document.createElement('tr');
      tr.setAttribute('data-action-row', index);
      tr.innerHTML = `
        <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 2px; height: 25px;">
          <input type="date" name="action_date[]" style="width: 100%; border: none; font-size: 8px; padding: 2px;" />
        </td>
        <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 2px;">
          <input type="time" name="action_time[]" style="width: 100%; border: none; font-size: 8px; padding: 2px;" />
        </td>
        <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 2px;">
          <textarea name="action_details[]" style="width: 100%; border: none; font-size: 8px; padding: 2px; height: 20px; resize: none;" placeholder="Action details..."></textarea>
        </td>
        <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 2px;">
          <select name="action_staff[]" class="form-select form-select-sm" style="width:100%; border:none; font-size:8px; padding:2px;">
            ${staffOptionsHtml}
          </select>
        </td>
        <td style="border-bottom: 1px solid black; padding: 2px;">
          <div class="signature-box" data-field="action_sig_${index}" data-staff-id="" style="border: 1px solid #000; height:40px; display:flex; align-items:center; justify-content:center; padding:4px; cursor:pointer;">
            <div id="action_sig_${index}_preview" style="width:100%; height:100%;"></div>
          </div>
          <input type="hidden" name="action_signature_data[]" id="action_sig_${index}_signature_data" value="">
          <input type="hidden" name="action_existing_signature_path[]" value="">
          <input type="hidden" name="action_old_staff_id[]" value="">
        </td>
      `;
      return tr;
    }

    if (addBtn) {
      addBtn.addEventListener('click', function () {
        const row = createRow(nextIndex++);
        tbody.appendChild(row);
      });
    }
  })();

  try {
    resizeCanvas();
    createSignaturePad();
  } catch (e) {
    console.warn('initial signature pad setup failed', e);
  }
})();
