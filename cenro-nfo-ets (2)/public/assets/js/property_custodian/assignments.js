function printAllQRCodes() {
      const printWindow = window.open('', '_blank');
      
      // Collect user rows from the assignments table to build printable data
      const rows = document.querySelectorAll('#assignmentsTable tbody tr');
      const userData = [];
      rows.forEach(r => {
        const idCell = r.cells[0];
        if (!idCell) return;
        // Skip empty/no-data rows
        const possibleText = idCell.textContent.trim();
        if (!possibleText) return;
        const qrImg = r.querySelector('img.qr-code-image');
        const qrSrc = qrImg ? qrImg.src : '';
        const name = r.cells[1] ? r.cells[1].innerText.trim() : '';
        const unit = r.cells[4] ? r.cells[4].innerText.trim() : '';
        userData.push({ name, unit, qrSrc });
      });

      if (userData.length === 0) {
        alert('No QR codes found to print.');
        printWindow.close();
        return;
      }
      
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>User QR Codes - CENRO NASIPIT</title>
          <style>
            @page { size: A4; margin: 10mm; }
            body {
              font-family: "Times New Roman", Times, serif;
              margin: 0;
              padding: 0; /* remove extra padding to fit 3 rows */
              background: white;
            }
            /* Two columns, three rows per page -> 6 cards per A4 */
            .qr-grid {
              display: grid;
              grid-template-columns: repeat(2, 1fr);
              grid-auto-rows: 86mm; /* reduced from 90mm */
              gap: 4mm 6mm; /* reduced row-gap and col-gap */
              margin: 0;
            }
            .qr-card {
              box-sizing: border-box;
              border: 1px solid #2c5530;
              padding: 5px; /* reduced from 6px */
              text-align: center;
              background: white;
              page-break-inside: avoid;
              width: 100%;
              height: 100%;
              display: flex;
              flex-direction: column;
              justify-content: flex-start;
              align-items: center;
            }
            .header {
              text-align: left;
              margin-bottom: 8px;
              display: flex;
              align-items: center;
              gap: 10px;
            }
            .denr-logo {
              width: 40px;
              height: 40px;
              object-fit: contain;
            }
            .header-text {
              flex: 1;
              text-align: left;
            }
            .header h3 {
              color: #000;
              margin: 0;
              font-size: 11px;
              font-weight: bold;
              font-family: "Times New Roman", Times, serif;
            }
            .header h4 {
              color: #000;
              margin: 2px 0 0 0;
              font-size: 10px;
              font-weight: normal;
              font-family: "Times New Roman", Times, serif;
            }
            .property-title {
              background: #2c5530;
              color: white;
              padding: 6px;
              margin: 8px 0 12px 0;
              font-weight: bold;
              font-size: 12px;
              letter-spacing: 1px;
              width: 100%;
              font-family: "Times New Roman", Times, serif;
            }
            .qr-code {
              margin: 8px 0;
            }
            .qr-code img {
              width: 36mm; /* sticker-appropriate */
              height: 36mm;
              border: 1px solid #ccc;
            }
            .user-info {
              margin-top: 8px;
            }
            .user-name {
              font-weight: bold;
              font-size: 13px;
              color: #2c5530;
              margin-bottom: 4px;
              text-transform: uppercase;
              font-family: "Times New Roman", Times, serif;
            }
            .unit-name {
              font-size: 10px;
              color: #666;
              font-style: italic;
              font-family: "Times New Roman", Times, serif;
            }
            @media print {
              body { margin: 0; padding: 0; }
              .qr-grid { gap: 4mm 6mm; }
              .qr-card { page-break-inside: avoid; }
            }
          </style>
        </head>
        <body>
          <div class="qr-grid">
      `);
      
      // Generate QR code cards
      userData.forEach((user, idx) => {
        printWindow.document.write(`
          <div class="qr-card">
            <div class="header">
              <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo" class="denr-logo">
              <div class="header-text">
                <div style="font-weight:bold;font-size:11px;">Department of Environment and Natural Resources</div>
                <div style="font-size:10px;margin-top:2px;">Community Environment and Natural Resources Office</div>
                <div style="font-size:10px;margin-top:2px;">CENRO Nasipit, Agusan del Norte</div>
              </div>
            </div>

            <div class="property-title">RP GOVERNMENT PROPERTY</div>

            <div class="qr-code">
              <img src="${user.qrSrc}" alt="QR Code">
            </div>

            <div class="user-info">
              <div class="user-name">${user.name}</div>
              <div class="unit-name">${user.unit}</div>
            </div>
          </div>
        `);

        // No explicit page-break element: rely on print pagination and adjusted sizes
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

function printAssignedDevices(userId) {
      if (!userId) return;

      // Find the table row for this user to extract the QR src, full name and office/unit
      const rows = document.querySelectorAll('#assignmentsTable tbody tr');
      let targetRow = null;
      rows.forEach(r => {
        const idCell = r.cells[0];
        if (idCell && idCell.textContent.trim() === String(userId)) targetRow = r;
      });

      if (!targetRow) {
        alert('User row not found.');
        return;
      }

      const qrImg = targetRow.querySelector('img.qr-code-image');
      const qrSrc = qrImg ? qrImg.src : '';
      const fullName = (targetRow.cells[1] && targetRow.cells[1].innerText) ? targetRow.cells[1].innerText.trim() : '';
      const office = (targetRow.cells[4] && targetRow.cells[4].innerText) ? targetRow.cells[4].innerText.trim() : '';

      const w = window.open('', '_blank');
      if (!w) { alert('Popup blocked. Please allow popups for this site to print.'); return; }

      const html = `<!doctype html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>QR Sticker - ${escapeHtml(fullName)}</title>
          <style>
            @page { size: A4 portrait; margin: 10mm; }
            body { font-family: "Times New Roman", Times, serif; margin: 0; padding: 0; background: #fff; color: #000; }

            /* Sticker container sized for small UPS label */
            .sticker-wrap { width: 70mm; height: 90mm; margin: 12mm auto; border: 2px solid #2c5530; padding: 4mm; box-sizing: border-box; }

            .sticker-header { display:flex; gap:6px; align-items:flex-start; }
            .sticker-logo img { width: 14mm; height: 14mm; object-fit:contain; }
            .sticker-text { flex:1; text-align:center; font-size:9px; line-height:1.05; }
            .sticker-text .line1 { font-weight:bold; font-size:10px; text-transform:uppercase; }
            .sticker-text .line2 { font-size:8px; }
            .property-title { background:#2c5530; color:#fff; padding:3px 6px; margin:6px 0; font-weight:bold; font-size:9px; letter-spacing:1px; text-align:center; }

            .qr-block { text-align:center; margin-top:4mm; }
            .qr-block img { width: 36mm; height: 36mm; object-fit:contain; border:1px solid #ddd; padding:2px; background:#fff; }

            .info { text-align:center; margin-top:4mm; }
            .info .name { font-weight:bold; font-size:9px; text-transform:uppercase; color:#2c5530; }
            .info .unit { font-size:8px; font-style:italic; color:#444; }

            @media print {
              body { margin: 0; padding: 0; }
              .sticker-wrap { margin: 0; }
            }
          </style>
        </head>
        <body>
          <div class="sticker-wrap">
            <div class="sticker-header">
              <div class="sticker-logo"><img src="../../../../public/assets/images/denr-logo.png" alt="DENR"></div>
              <div class="sticker-text">
                <div class="line1">Department of Environment and Natural Resources</div>
                <div class="line2">Community Environment and Natural Resources Office</div>
                <div class="line2">CENRO Nasipit, Agusan del Norte</div>
              </div>
            </div>

            <div class="property-title">RP GOVERNMENT PROPERTY</div>

            <div class="qr-block">
              <img src="${qrSrc}" alt="QR">
            </div>

            <div class="info">
              <div class="name">${escapeHtml(fullName)}</div>
              <div class="unit">${escapeHtml(office)}</div>
            </div>
          </div>
        </body>
        </html>`;

      w.document.open();
      w.document.write(html);
      w.document.close();

      // Give images a moment to load then print
      setTimeout(() => { try { w.focus(); w.print(); } catch (e) {} }, 600);
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
