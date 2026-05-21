(function () {
  var modalEl = null;
  var styleEl = null;

  function ensureModal() {
    if (modalEl) return modalEl;

    if (!styleEl) {
      styleEl = document.createElement('style');
      styleEl.textContent = '' +
        '.profile-cropper-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.65);display:none;align-items:center;justify-content:center;z-index:2000;padding:16px;}' +
        '.profile-cropper-panel{background:#fff;border-radius:14px;max-width:680px;width:100%;padding:16px;box-shadow:0 12px 30px rgba(0,0,0,.35);}' +
        '.profile-cropper-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}' +
        '.profile-cropper-title{font-size:1rem;font-weight:700;margin:0;}' +
        '.profile-cropper-stage{position:relative;width:min(100%,360px);aspect-ratio:1/1;border-radius:12px;margin:0 auto;overflow:hidden;background:#f1f3f5;touch-action:none;}' +
        '.profile-cropper-stage img{position:absolute;z-index:1;display:block;user-select:none;-webkit-user-drag:none;max-width:none;}' +
        '.profile-cropper-overlay{position:absolute;inset:0;z-index:2;border-radius:50%;background:transparent;border:2px solid rgba(255,255,255,.95);box-shadow:0 0 0 9999px rgba(0,0,0,.28);pointer-events:none;}' +
        '.profile-cropper-controls{margin-top:12px;display:flex;align-items:center;gap:8px;}' +
        '.profile-cropper-controls input[type=range]{flex:1;}' +
        '.profile-cropper-actions{margin-top:14px;display:flex;justify-content:flex-end;gap:8px;}' +
        '.profile-cropper-note{font-size:.85rem;color:#6b7280;text-align:center;margin-top:8px;}';
      document.head.appendChild(styleEl);
    }

    modalEl = document.createElement('div');
    modalEl.className = 'profile-cropper-backdrop';
    modalEl.innerHTML = [
      '<div class="profile-cropper-panel" role="dialog" aria-modal="true">',
      '<div class="profile-cropper-header">',
      '<h3 class="profile-cropper-title">Adjust Profile Picture</h3>',
      '<button type="button" class="btn btn-sm btn-outline-secondary" data-cropper-close>Close</button>',
      '</div>',
      '<div class="profile-cropper-stage" data-cropper-stage>',
      '<img alt="Crop preview" data-cropper-image>',
      '<div class="profile-cropper-overlay"></div>',
      '</div>',
      '<div class="profile-cropper-controls">',
      '<span>Zoom</span>',
      '<input type="range" min="1" max="3" step="0.01" value="1" data-cropper-zoom>',
      '</div>',
      '<div class="profile-cropper-note">Drag the image to set position, then click Apply.</div>',
      '<div class="profile-cropper-actions">',
      '<button type="button" class="btn btn-secondary" data-cropper-cancel>Cancel</button>',
      '<button type="button" class="btn btn-primary" data-cropper-apply>Apply</button>',
      '</div>',
      '</div>'
    ].join('');

    document.body.appendChild(modalEl);
    return modalEl;
  }

  function Cropper(options) {
    this.options = options || {};
    this.fileInput = this.options.fileInput;
    this.hiddenInput = this.options.hiddenInput;
    this.previewTarget = this.options.previewTarget;
    this.autoSubmitForm = this.options.autoSubmitForm || null;
    this.onError = this.options.onError || function (msg) { window.alert(msg); };

    this.imageSrc = '';
    this.scale = 1;
    this.baseScale = 1;
    this.offsetX = 0;
    this.offsetY = 0;
    this.dragging = false;
    this.lastX = 0;
    this.lastY = 0;

    this.modal = ensureModal();
    this.stage = this.modal.querySelector('[data-cropper-stage]');
    this.image = this.modal.querySelector('[data-cropper-image]');
    this.zoom = this.modal.querySelector('[data-cropper-zoom]');

    this.bindEvents();
  }

  Cropper.prototype.bindEvents = function () {
    var self = this;

    this.modal.querySelector('[data-cropper-close]').addEventListener('click', function () { self.close(); });
    this.modal.querySelector('[data-cropper-cancel]').addEventListener('click', function () { self.close(); });
    this.modal.querySelector('[data-cropper-apply]').addEventListener('click', function () { self.apply(); });

    this.zoom.addEventListener('input', function () {
      self.scale = parseFloat(self.zoom.value) || 1;
      self.constrainOffsets();
      self.layoutImage();
    });

    this.stage.addEventListener('pointerdown', function (event) {
      self.dragging = true;
      self.lastX = event.clientX;
      self.lastY = event.clientY;
      self.stage.setPointerCapture(event.pointerId);
    });

    this.stage.addEventListener('pointermove', function (event) {
      if (!self.dragging) return;
      var dx = event.clientX - self.lastX;
      var dy = event.clientY - self.lastY;
      self.lastX = event.clientX;
      self.lastY = event.clientY;
      self.offsetX += dx;
      self.offsetY += dy;
      self.constrainOffsets();
      self.layoutImage();
    });

    this.stage.addEventListener('pointerup', function () {
      self.dragging = false;
    });

    this.stage.addEventListener('pointercancel', function () {
      self.dragging = false;
    });
  };

  Cropper.prototype.openFromInputEvent = function (event) {
    var file = event && event.target && event.target.files ? event.target.files[0] : null;
    if (!file) return;

    if (['image/jpeg', 'image/png'].indexOf(file.type) === -1) {
      this.onError('Only JPG and PNG images are allowed.');
      if (event.target) event.target.value = '';
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      this.onError('File is too large. Max 2MB.');
      if (event.target) event.target.value = '';
      return;
    }

    var self = this;
    var reader = new FileReader();
    reader.onload = function (loadEvent) {
      self.open(loadEvent.target.result);
    };
    reader.readAsDataURL(file);
  };

  Cropper.prototype.open = function (src) {
    var self = this;
    this.imageSrc = src;
    this.modal.style.display = 'flex';
    this.image.onload = function () {
      var cropW = self.stage.clientWidth;
      var cropH = self.stage.clientHeight;
      if (!cropW || !cropH || !self.image.naturalWidth || !self.image.naturalHeight) {
        self.onError('Unable to load image for cropping.');
        return;
      }
      self.baseScale = Math.max(cropW / self.image.naturalWidth, cropH / self.image.naturalHeight);
      self.scale = 1;
      self.zoom.value = '1';
      self.offsetX = 0;
      self.offsetY = 0;
      self.layoutImage();
    };
    this.image.onerror = function () {
      self.onError('Unable to read selected image.');
      self.close();
    };
    this.image.src = src;
  };

  Cropper.prototype.layoutImage = function () {
    var cropW = this.stage.clientWidth;
    var cropH = this.stage.clientHeight;
    var drawScale = this.baseScale * this.scale;
    var width = this.image.naturalWidth * drawScale;
    var height = this.image.naturalHeight * drawScale;
    var left = (cropW - width) / 2 + this.offsetX;
    var top = (cropH - height) / 2 + this.offsetY;

    this.image.style.width = width + 'px';
    this.image.style.height = height + 'px';
    this.image.style.left = left + 'px';
    this.image.style.top = top + 'px';
  };

  Cropper.prototype.constrainOffsets = function () {
    var cropW = this.stage.clientWidth;
    var cropH = this.stage.clientHeight;
    var drawScale = this.baseScale * this.scale;
    var width = this.image.naturalWidth * drawScale;
    var height = this.image.naturalHeight * drawScale;

    var maxX = Math.max((width - cropW) / 2, 0);
    var maxY = Math.max((height - cropH) / 2, 0);

    this.offsetX = Math.min(Math.max(this.offsetX, -maxX), maxX);
    this.offsetY = Math.min(Math.max(this.offsetY, -maxY), maxY);
  };

  Cropper.prototype.getCroppedDataUrl = function () {
    var cropW = this.stage.clientWidth;
    var cropH = this.stage.clientHeight;
    var drawScale = this.baseScale * this.scale;
    var width = this.image.naturalWidth * drawScale;
    var height = this.image.naturalHeight * drawScale;
    var left = (cropW - width) / 2 + this.offsetX;
    var top = (cropH - height) / 2 + this.offsetY;

    var sourceX = (0 - left) / drawScale;
    var sourceY = (0 - top) / drawScale;
    var sourceW = cropW / drawScale;
    var sourceH = cropH / drawScale;

    var canvas = document.createElement('canvas');
    var outputSize = 512;
    canvas.width = outputSize;
    canvas.height = outputSize;

    var ctx = canvas.getContext('2d');
    ctx.drawImage(this.image, sourceX, sourceY, sourceW, sourceH, 0, 0, outputSize, outputSize);

    return canvas.toDataURL('image/png');
  };

  Cropper.prototype.apply = function () {
    var dataUrl = this.getCroppedDataUrl();

    if (this.hiddenInput) {
      this.hiddenInput.value = dataUrl;
    }

    if (typeof this.previewTarget === 'function') {
      this.previewTarget(dataUrl);
    } else if (this.previewTarget && this.previewTarget.tagName === 'IMG') {
      this.previewTarget.src = dataUrl;
    }

    this.close();

    if (this.autoSubmitForm) {
      this.autoSubmitForm.submit();
    }
  };

  Cropper.prototype.close = function () {
    this.modal.style.display = 'none';
  };

  window.createProfileImageCropper = function (options) {
    return new Cropper(options || {});
  };
})();
