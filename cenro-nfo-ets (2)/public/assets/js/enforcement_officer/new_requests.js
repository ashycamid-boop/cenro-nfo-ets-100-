// Auto-generate ticket number and set current date
document.addEventListener('DOMContentLoaded', function () {
  // Ticket number and current date are provided by the server; no client-side override.

  // Signature pad setup
  function resizeCanvas(canvas) {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const w = canvas.offsetWidth;
    const h = canvas.offsetHeight;
    canvas.width = w * ratio;
    canvas.height = h * ratio;
    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);
  }

  window.signaturePads = {};
  function initPad(canvasId, clearBtnId, key) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof SignaturePad === 'undefined') return;
    resizeCanvas(canvas);
    const pad = new SignaturePad(canvas, { backgroundColor: 'rgba(255,255,255,0)' });
    window.signaturePads[key] = pad;
    const clearBtn = document.getElementById(clearBtnId);
    if (clearBtn) {
      clearBtn.addEventListener('click', function () { pad.clear(); });
    }
  }

  initPad('requester_signature_pad', 'requester_sig_clear', 'requester');
  initPad('auth1_signature_pad', 'auth1_sig_clear', 'auth1');
  initPad('auth2_signature_pad', 'auth2_sig_clear', 'auth2');

  // Modal signature pad (initialize after modal is shown to ensure sizes)
  let modalPad = null;
  let modalCurrent = null;
  const signatureModalEl = document.getElementById('signatureModal');
  const bsModal = signatureModalEl ? new bootstrap.Modal(signatureModalEl) : null;

  // When modal is shown, resize canvas and (re)create SignaturePad instance
  if (signatureModalEl) {
    signatureModalEl.addEventListener('shown.bs.modal', function () {
      const canvas = document.getElementById('signature_modal_canvas');
      if (!canvas) return;
      resizeCanvas(canvas);
      // destroy previous instance
      try { if (modalPad) modalPad.off && modalPad.off(); } catch (e) {}
      modalPad = new SignaturePad(canvas, { backgroundColor: 'rgba(255,255,255,0)' });

      // If there's existing data for the current target, draw it onto the modal canvas
      if (modalCurrent && modalCurrent.hiddenInputId) {
        const hidden = document.getElementById(modalCurrent.hiddenInputId);
        if (hidden && hidden.value) {
          const img = new Image();
          img.onload = function () {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            // draw image scaled to canvas CSS size
            ctx.drawImage(img, 0, 0, canvas.width / (window.devicePixelRatio || 1), canvas.height / (window.devicePixelRatio || 1));
          };
          img.src = hidden.value;
        } else {
          modalPad.clear();
        }
      }
    });

    signatureModalEl.addEventListener('hidden.bs.modal', function () {
      // clear modalPad to free memory
      try { if (modalPad) modalPad.clear(); } catch (e) {}
      modalPad = null;
    });
  }

  function openModalFor(key, smallCanvasId, hiddenInputId) {
    modalCurrent = { key, smallCanvasId, hiddenInputId };
    if (bsModal) bsModal.show();
  }

  function drawDataUrlToSmallCanvas(smallCanvasId, dataUrl) {
    const small = document.getElementById(smallCanvasId);
    if (!small) return;
    const ctx = small.getContext('2d');
    const img = new Image();
    img.onload = function () {
      // clear and draw
      ctx.clearRect(0, 0, small.width, small.height);
      ctx.drawImage(img, 0, 0, small.width, small.height);
    };
    img.src = dataUrl;
  }

  // wire small canvases to open modal on click
  ['requester', 'auth1', 'auth2'].forEach(function (k) {
    const smallCanvas = document.getElementById(k + '_signature_pad');
    const hiddenId = k === 'requester' ? 'requester_signature_data' : (k === 'auth1' ? 'auth1_signature_data' : 'auth2_signature_data');
    if (smallCanvas) {
      smallCanvas.style.cursor = 'pointer';
      smallCanvas.addEventListener('click', function () { openModalFor(k, k + '_signature_pad', hiddenId); });
    }
  });

  // modal controls
  const modalSaveBtn = document.getElementById('modal_save');
  const modalClearBtn = document.getElementById('modal_clear');
  const modalCancelBtn = document.getElementById('modal_cancel');
  if (modalSaveBtn) {
    modalSaveBtn.addEventListener('click', function () {
      if (!modalPad || !modalCurrent) return;
      const dataUrl = modalPad.toDataURL('image/png');
      const hidden = document.getElementById(modalCurrent.hiddenInputId);
      if (hidden) hidden.value = dataUrl;
      drawDataUrlToSmallCanvas(modalCurrent.smallCanvasId, dataUrl);
      if (bsModal) bsModal.hide();
    });
  }
  if (modalClearBtn) modalClearBtn.addEventListener('click', function () { if (modalPad) modalPad.clear(); });
  if (modalCancelBtn) modalCancelBtn.addEventListener('click', function () { if (modalPad) modalPad.clear(); });

  // Save Draft and Submit button wiring
  const saveBtn = document.getElementById('saveDraftBtn');
  const submitBtn = document.getElementById('submitBtn');
  const saveHidden = document.getElementById('save_draft');
  if (saveBtn) saveBtn.addEventListener('click', function () { if (saveHidden) saveHidden.value = '1'; form.submit(); });
  if (submitBtn) submitBtn.addEventListener('click', function () { if (saveHidden) saveHidden.value = ''; });

  window.addEventListener('resize', function () {
    ['requester_signature_pad', 'auth1_signature_pad', 'auth2_signature_pad'].forEach(function (id) {
      const c = document.getElementById(id);
      if (c) resizeCanvas(c);
    });
  });
});

// Preview signature function
function previewSignature(input) {
  const preview = document.getElementById('signature_preview');

  if (input.files && input.files[0]) {
    // Check file size (max 5MB)
    if (input.files[0].size > 5 * 1024 * 1024) {
      alert('File size should be less than 5MB');
      input.value = '';
      return;
    }

    // Check file type
    if (!input.files[0].type.match('image.*')) {
      alert('Please select an image file');
      input.value = '';
      return;
    }

    const reader = new FileReader();

    reader.onload = function (e) {
      preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 100%; object-fit: contain;" alt="Signature Preview">`;
    };

    reader.readAsDataURL(input.files[0]);
  } else {
    preview.innerHTML = `
      <div style="text-align: center;">
        <div style="font-size: 7px; color: #666; margin-bottom: 2px;">Click to upload</div>
        <div style="font-size: 6px; color: #999;">signature image</div>
      </div>
    `;
  }
}

// Save as draft function
function saveDraft() {
  const form = document.getElementById('serviceRequestForm');
  if (form) {
    const formData = new FormData(form);
    formData.append('action', 'save_draft');

    // Here you would send the data to your backend
    alert('Request saved as draft!');
  }
}

// Form validation and submission
const form = document.getElementById('serviceRequestForm');
if (form) {
  form.addEventListener('submit', function (e) {
    // Save signature pad data into hidden inputs (if any)
    try {
      if (window.signaturePads) {
        const pads = window.signaturePads;
        if (pads.requester && !pads.requester.isEmpty()) {
          const el = document.getElementById('requester_signature_data');
          if (el) el.value = pads.requester.toDataURL('image/png');
        }
        if (pads.auth1 && !pads.auth1.isEmpty()) {
          const el = document.getElementById('auth1_signature_data');
          if (el) el.value = pads.auth1.toDataURL('image/png');
        }
        if (pads.auth2 && !pads.auth2.isEmpty()) {
          const el = document.getElementById('auth2_signature_data');
          if (el) el.value = pads.auth2.toDataURL('image/png');
        }
      }
    } catch (err) {
      // ignore
    }

    const isDraft = document.getElementById('save_draft') && document.getElementById('save_draft').value === '1';
    const requesterSignature = document.getElementById('requester_signature_data');
    const requesterSignaturePad = document.getElementById('requester_signature_pad');
    if (!isDraft && (!requesterSignature || !requesterSignature.value.trim())) {
      e.preventDefault();
      if (requesterSignaturePad) requesterSignaturePad.style.border = '2px solid red';
      alert('Please provide your requester signature before submitting.');
      return;
    } else if (requesterSignaturePad) {
      requesterSignaturePad.style.border = '';
    }

    // Basic validation for required fields
    const requiredFields = [
      'requester_name', 'requester_position', 'requester_office',
      'requester_division', 'requester_phone', 'requester_email',
      'request_type', 'request_description'
    ];

    let isValid = true;

    requiredFields.forEach(function (field) {
      const input = document.querySelector(`[name="${field}"]`);
      if (input && !input.value.trim()) {
        input.style.borderBottom = '2px solid red';
        isValid = false;
      } else if (input) {
        input.style.borderBottom = '';
      }
    });

    if (!isValid) {
      e.preventDefault();
      alert('Please fill in all required fields.');
    }
    // If valid, allow normal POST to `../controllers/save_request.php`
  });
}
