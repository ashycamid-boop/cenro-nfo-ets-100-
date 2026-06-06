(function () {
  const ackBox = document.getElementById('ack_sig_box');
  const ackModalEl = document.getElementById('ackSignatureModal');
  const ackSigCanvas = document.getElementById('ackSigCanvas');
  const ackSigPreview = document.getElementById('ack_sig_preview');
  const ackPlaceholder = document.getElementById('ack_sig_placeholder');
  const ackHidden = document.getElementById('ack_signature_data');
  const ackSaveBtn = document.getElementById('ack_save_btn_global');
  const ackSavedLabel = document.getElementById('ack_saved_label_global');
  let signaturePad = null;
  const ackModal = ackModalEl ? new bootstrap.Modal(ackModalEl) : null;
  const ratingFinalized = document.body?.dataset?.ratingFinalized === '1';

  function resizeCanvas() {
    if (!ackSigCanvas) return;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = ackSigCanvas.getBoundingClientRect();
    const w = rect.width || 400;
    const h = rect.height || 200;
    ackSigCanvas.width = w * ratio;
    ackSigCanvas.height = h * ratio;
    const ctx = ackSigCanvas.getContext('2d');
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.scale(ratio, ratio);
  }

  function createPad() {
    if (!ackSigCanvas) return;
    if (signaturePad) {
      try { signaturePad.off && signaturePad.off(); } catch (e) {}
    }
    signaturePad = new SignaturePad(ackSigCanvas, { backgroundColor: 'rgba(255,255,255,0)' });
  }

  function formatDate12(d) {
    if (!d || !(d instanceof Date)) return '';
    const pad = (n) => String(n).padStart(2, '0');
    const month = pad(d.getMonth() + 1);
    const day = pad(d.getDate());
    const year = d.getFullYear();
    let hour = d.getHours();
    const minute = pad(d.getMinutes());
    const second = pad(d.getSeconds());
    const ampm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12;
    hour = hour ? hour : 12;
    const hourPad = String(hour).padStart(2, '0');
    return `${month}/${day}/${year} ${hourPad}:${minute}:${second} ${ampm}`;
  }

  if (ackBox) {
    ackBox.addEventListener('click', function () {
      if (ackBox.dataset.saved === '1') return;
      if (!ackModal) return;
      try { resizeCanvas(); createPad(); } catch (e) { console.warn(e); }
      if (ackHidden && ackHidden.value) {
        try { signaturePad.fromDataURL(ackHidden.value); } catch (e) {}
      } else if (signaturePad) {
        signaturePad.clear();
      }
      ackModal.show();
    });
  }

  if (ackModalEl) {
    ackModalEl.addEventListener('shown.bs.modal', function () {
      try { resizeCanvas(); createPad(); } catch (e) { console.warn(e); }
      if (ackHidden && ackHidden.value && signaturePad) {
        try { signaturePad.fromDataURL(ackHidden.value); } catch (e) {}
      }
    });
  }

  const clearBtn = document.getElementById('ackSigClear');
  const saveBtn = document.getElementById('ackSigSave');
  if (clearBtn) clearBtn.addEventListener('click', function () { if (signaturePad) signaturePad.clear(); });
  if (saveBtn) {
    saveBtn.addEventListener('click', function () {
      if (!signaturePad) return;
      if (signaturePad.isEmpty()) {
        if (ackHidden) ackHidden.value = '';
        if (ackSigPreview) { ackSigPreview.style.display = 'none'; ackSigPreview.src = ''; }
        if (ackPlaceholder) ackPlaceholder.style.display = 'inline';
        ackModal.hide();
        return;
      }
      let dataURL = null;
      try { dataURL = signaturePad.toDataURL('image/png'); } catch (e) { console.error(e); }
      if (dataURL) {
        if (ackHidden) ackHidden.value = dataURL;
        if (ackSigPreview) { ackSigPreview.src = dataURL; ackSigPreview.style.display = 'block'; }
        if (ackPlaceholder) ackPlaceholder.style.display = 'none';
        if (ackSaveBtn) ackSaveBtn.disabled = false;
      }
      ackModal.hide();
    });
  }

  if (ackSaveBtn) {
    if (ratingFinalized) {
      const savedLabel = document.getElementById('ack_saved_label_global');
      if (savedLabel) savedLabel.style.display = 'inline-block';
      ackSaveBtn.textContent = 'Saved';
      ackSaveBtn.disabled = true;
      ackSaveBtn.classList.remove('btn-primary');
      ackSaveBtn.classList.add('btn-success');
    }

    const feedbackInputs = Array.from(document.querySelectorAll('input[name="feedback_rating"]'));
    const completedInput = document.getElementById('completed');
    function enableSave() { if (ackSaveBtn && !ratingFinalized) ackSaveBtn.disabled = false; }
    feedbackInputs.forEach((i) => i.addEventListener('change', enableSave));
    if (completedInput) completedInput.addEventListener('change', enableSave);

    ackSaveBtn.addEventListener('click', async function () {
      const requestIdEl = document.getElementById('service_request_id');
      const requestId = requestIdEl ? requestIdEl.value : '';
      const selected = document.querySelector('input[name="feedback_rating"]:checked');
      const feedbackVal = selected ? selected.value : '';
      const completedVal = (document.getElementById('completed') && document.getElementById('completed').checked) ? 1 : 0;
      const signatureData = ackHidden && ackHidden.value ? ackHidden.value : null;

      if (!requestId) {
        alert('Request id missing');
        return;
      }

      try {
        const res = await fetch('save_feedback.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ request_id: requestId, feedback_rating: feedbackVal, completed: completedVal, signature: signatureData })
        });

        const data = await res.json();
        if (!data.ok) throw new Error(data.msg || 'Save failed');

        feedbackInputs.forEach((i) => { i.disabled = true; });
        if (completedInput) completedInput.disabled = true;
        ackBox.dataset.saved = '1';
        ackBox.style.cursor = 'default';
        ackBox.style.pointerEvents = 'none';
        ackSaveBtn.textContent = 'Saved';
        ackSaveBtn.disabled = true;
        ackSaveBtn.classList.remove('btn-primary');
        ackSaveBtn.classList.add('btn-success');
        if (ackSavedLabel) ackSavedLabel.style.display = 'inline-block';

        const dtEl = document.getElementById('ack_datetime_display');
        if (dtEl) dtEl.textContent = formatDate12(new Date());

        if (data.path && ackSigPreview) {
          ackSigPreview.src = data.path;
          ackSigPreview.style.display = 'block';
          if (ackPlaceholder) ackPlaceholder.style.display = 'none';
        }
      } catch (e) {
        console.error(e);
        alert('Hindi na-save: ' + (e.message || e));
      }
    });
  }

  try {
    if (ackHidden && ackHidden.value) {
      if (ackSigPreview) { ackSigPreview.src = ackHidden.value; ackSigPreview.style.display = 'block'; }
      if (ackPlaceholder) ackPlaceholder.style.display = 'none';
      if (ackSaveBtn) ackSaveBtn.disabled = false;
      if (ackBox && ackBox.dataset.saved === '1') {
        if (ackSaveBtn) {
          ackSaveBtn.textContent = 'Saved';
          ackSaveBtn.disabled = true;
          ackSaveBtn.classList.remove('btn-primary');
          ackSaveBtn.classList.add('btn-success');
        }
        if (ackSavedLabel) ackSavedLabel.style.display = 'inline-block';
        ackBox.style.cursor = 'default';
        ackBox.style.pointerEvents = 'none';
      }
    }
  } catch (e) {}

  window.addEventListener('resize', function () {
    try { resizeCanvas(); createPad(); } catch (e) {}
  });
})();
