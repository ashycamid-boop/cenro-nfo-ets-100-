(function(){
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

      function resizeCanvas() {
        if (!ackSigCanvas) return;
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = ackSigCanvas.getBoundingClientRect();
        const w = rect.width || 400;
        const h = rect.height || 200;
        ackSigCanvas.width = w * ratio;
        ackSigCanvas.height = h * ratio;
        const ctx = ackSigCanvas.getContext('2d');
        ctx.setTransform(1,0,0,1,0,0);
        ctx.scale(ratio, ratio);
      }

      function createPad() {
        if (!ackSigCanvas) return;
        if (signaturePad) try { signaturePad.off && signaturePad.off(); } catch(e){}
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
        hour = hour % 12; hour = hour ? hour : 12; // convert 0 -> 12
        const hourPad = String(hour).padStart(2, '0');
        // Format: MM/DD/YYYY hh:mm:ss AM/PM
        return `${month}/${day}/${year} ${hourPad}:${minute}:${second} ${ampm}`;
      }

      if (ackBox) {
        ackBox.addEventListener('click', function(){
          // Prevent editing if this signature box is inside a read-only area
          if (ackBox.closest && ackBox.closest('.no-edit-below')) return;
          // if already finalized, don't allow editing
          if (ackBox.dataset.saved === '1') return;
          if (!ackModal) return;
          // prepare canvas
          try { resizeCanvas(); createPad(); } catch (e) { console.warn(e); }
          // if existing data, restore
          if (ackHidden && ackHidden.value) {
            try { signaturePad.fromDataURL(ackHidden.value); } catch (e) { /* ignore */ }
          } else if (signaturePad) {
            signaturePad.clear();
          }
          ackModal.show();
        });
      }

      if (ackModalEl) {
        ackModalEl.addEventListener('shown.bs.modal', function(){
          try { resizeCanvas(); createPad(); } catch (e) { console.warn(e); }
          if (ackHidden && ackHidden.value && signaturePad) {
            try { signaturePad.fromDataURL(ackHidden.value); } catch (e) {}
          }
        });
      }

      const clearBtn = document.getElementById('ackSigClear');
      const saveBtn = document.getElementById('ackSigSave');
      if (clearBtn) clearBtn.addEventListener('click', function(){ if (signaturePad) signaturePad.clear(); });
        if (saveBtn) saveBtn.addEventListener('click', function(){
        if (!signaturePad) return;
        if (signaturePad.isEmpty()) {
          // clear preview and hidden
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
          // enable the global Save button so user can finalize
          if (ackSaveBtn) { ackSaveBtn.disabled = false; }
        }
        ackModal.hide();
      });

      // Save/finalize button handler - mark signature as saved and prevent edits
      if (ackSaveBtn) {
        ackSaveBtn.addEventListener('click', async function(){
          if (!ackHidden || !ackHidden.value) { alert('Please draw a signature first.'); return; }

          const requestId = document.getElementById("service_request_id") ? document.getElementById("service_request_id").value : "";

          try {
            const res = await fetch('save_ack_signature.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ request_id: requestId, signature: ackHidden.value })
            });

            const data = await res.json();
            if (!data.ok) throw new Error(data.msg || 'Save failed');

            // success UI
            ackBox.dataset.saved = '1';
            ackBox.style.cursor = 'default';
            ackBox.style.pointerEvents = 'none';
            ackSaveBtn.textContent = 'Saved';
            ackSaveBtn.disabled = true;
            ackSaveBtn.classList.remove('btn-primary');
            ackSaveBtn.classList.add('btn-success');
            if (ackSavedLabel) ackSavedLabel.style.display = 'inline-block';

            // set datetime display
            const dtEl = document.getElementById('ack_datetime_display');
            if (dtEl) dtEl.textContent = formatDate12(new Date());

            // if server returned a path, set ackSigPreview src to it
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

      // If page already has a signature value (loaded from DB), show preview and Save button state
      try {
        if (ackHidden && ackHidden.value) {
          if (ackSigPreview) { ackSigPreview.src = ackHidden.value; ackSigPreview.style.display = 'block'; }
          if (ackPlaceholder) ackPlaceholder.style.display = 'none';
          if (ackSaveBtn) { ackSaveBtn.disabled = false; }
          // if server-side data indicates finalized, mark saved (optional)
          if (ackBox.dataset.saved === '1') {
            if (ackSaveBtn) { ackSaveBtn.textContent = 'Saved'; ackSaveBtn.disabled = true; ackSaveBtn.classList.remove('btn-primary'); ackSaveBtn.classList.add('btn-success'); }
            if (ackSavedLabel) ackSavedLabel.style.display = 'inline-block';
            ackBox.style.cursor = 'default';
            ackBox.style.pointerEvents = 'none';
          }
        }
      } catch (e) { /* ignore */ }

      window.addEventListener('resize', function(){ try { resizeCanvas(); createPad(); } catch(e){} });
    })();
