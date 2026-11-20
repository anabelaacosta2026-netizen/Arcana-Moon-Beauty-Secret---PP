/**
 * ARCANA — Reusable Drag & Drop Image Uploader Module
 */

import { showArcanaModal } from './modal.js';

export function initDropzone({
  containerSelector,
  inputSelector,
  defaultImage = '',
  uploadUrl = 'api/upload.php',
  onUploadSuccess = null
}) {
  const container = typeof containerSelector === 'string'
    ? document.querySelector(containerSelector)
    : containerSelector;

  if (!container) return null;

  const targetInput = typeof inputSelector === 'string'
    ? document.querySelector(inputSelector)
    : inputSelector;

  // Build the DOM structure inside container
  container.classList.add('image-dropzone-wrapper');
  container.innerHTML = `
    <div class="image-dropzone" tabindex="0" role="button" aria-label="Subir o arrastrar imagen">
      <input type="file" class="image-dropzone__file-input" accept="image/png, image/jpeg, image/webp, image/gif, image/svg+xml" style="display: none;">
      
      <!-- Prompt state (no image selected yet) -->
      <div class="image-dropzone__prompt">
        <div class="image-dropzone__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
          </svg>
        </div>
        <p class="image-dropzone__text">
          <strong class="image-dropzone__highlight">Arrastra y suelta tu imagen aquí</strong> o <span class="image-dropzone__browse">selecciona desde tu PC</span>
        </p>
        <span class="image-dropzone__hint">JPG, PNG, WEBP, GIF hasta 6MB</span>
      </div>

      <!-- Preview state (image loaded) -->
      <div class="image-dropzone__preview" style="display: none;">
        <div class="image-dropzone__thumb-box">
          <img src="" alt="Vista previa de imagen" class="image-dropzone__thumb">
        </div>
        <div class="image-dropzone__details">
          <span class="image-dropzone__filename">imagen.jpg</span>
          <span class="image-dropzone__status">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Imagen lista
          </span>
          <div class="image-dropzone__actions">
            <button type="button" class="image-dropzone__btn-change">Cambiar</button>
            <button type="button" class="image-dropzone__btn-remove">Quitar</button>
          </div>
        </div>
      </div>

      <!-- Loading overlay state -->
      <div class="image-dropzone__loading" style="display: none;">
        <div class="image-dropzone__spinner"></div>
        <span class="image-dropzone__loading-text">Subiendo imagen...</span>
      </div>
    </div>
  `;

  const dropzone = container.querySelector('.image-dropzone');
  const fileInput = container.querySelector('.image-dropzone__file-input');
  const promptEl = container.querySelector('.image-dropzone__prompt');
  const previewEl = container.querySelector('.image-dropzone__preview');
  const thumbEl = container.querySelector('.image-dropzone__thumb');
  const filenameEl = container.querySelector('.image-dropzone__filename');
  const loadingEl = container.querySelector('.image-dropzone__loading');
  const btnChange = container.querySelector('.image-dropzone__btn-change');
  const btnRemove = container.querySelector('.image-dropzone__btn-remove');

  // Trigger file browse on click (unless clicking remove/change buttons)
  dropzone.addEventListener('click', (e) => {
    if (e.target.closest('.image-dropzone__btn-remove') || e.target.closest('.image-dropzone__btn-change')) {
      return;
    }
    fileInput.click();
  });

  // Keyboard accessibility
  dropzone.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      fileInput.click();
    }
  });

  // Change button click
  btnChange.addEventListener('click', (e) => {
    e.stopPropagation();
    fileInput.click();
  });

  // Remove button click
  btnRemove.addEventListener('click', (e) => {
    e.stopPropagation();
    resetDropzone();
  });

  // Drag and drop listeners
  ['dragenter', 'dragover'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropzone.classList.add('image-dropzone--dragover');
    });
  });

  ['dragleave', 'dragend'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropzone.classList.remove('image-dropzone--dragover');
    });
  });

  dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    e.stopPropagation();
    dropzone.classList.remove('image-dropzone--dragover');

    const files = e.dataTransfer ? e.dataTransfer.files : null;
    if (files && files.length > 0) {
      handleFile(files[0]);
    }
  });

  // File input change listener
  fileInput.addEventListener('change', () => {
    if (fileInput.files && fileInput.files.length > 0) {
      handleFile(fileInput.files[0]);
    }
  });

  // Handle selected or dropped file
  async function handleFile(file) {
    if (!file.type.startsWith('image/')) {
      showArcanaModal({
        title: 'Formato no válido',
        message: 'Por favor selecciona un archivo de imagen válido (JPG, PNG, WEBP, GIF, SVG).',
        type: 'warning'
      });
      return;
    }

    if (file.size > 6 * 1024 * 1024) {
      showArcanaModal({
        title: 'Archivo demasiado pesado',
        message: 'La imagen seleccionada supera el límite máximo permitido de 6 MB.',
        type: 'warning'
      });
      return;
    }

    // Instant local preview
    const localUrl = URL.createObjectURL(file);
    showPreview(localUrl, file.name);

    // Show upload loading state
    loadingEl.style.display = 'flex';

    try {
      const formData = new FormData();
      formData.append('image', file);

      const res = await fetch(uploadUrl, {
        method: 'POST',
        body: formData
      });

      const json = await res.json();
      loadingEl.style.display = 'none';

      if (json.success && json.data && json.data.url) {
        if (targetInput) {
          targetInput.value = json.data.url;
          // Trigger change event if listeners exist
          targetInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        showPreview(json.data.url, json.data.filename || file.name);
        if (typeof onUploadSuccess === 'function') {
          onUploadSuccess(json.data.url);
        }
      } else {
        // Fallback: convert file to Base64 so it can still be saved
        const reader = new FileReader();
        reader.onload = () => {
          if (targetInput) {
            targetInput.value = reader.result;
            targetInput.dispatchEvent(new Event('change', { bubbles: true }));
          }
        };
        reader.readAsDataURL(file);
      }
    } catch (err) {
      console.warn('Subida directa falló, aplicando fallback local:', err);
      loadingEl.style.display = 'none';
      const reader = new FileReader();
      reader.onload = () => {
        if (targetInput) {
          targetInput.value = reader.result;
          targetInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
      };
      reader.readAsDataURL(file);
    }
  }

  function showPreview(src, name = 'imagen') {
    thumbEl.src = src;
    filenameEl.textContent = name.length > 25 ? name.substring(0, 22) + '...' : name;
    promptEl.style.display = 'none';
    previewEl.style.display = 'flex';
  }

  function resetDropzone() {
    fileInput.value = '';
    thumbEl.src = '';
    promptEl.style.display = 'flex';
    previewEl.style.display = 'none';
    loadingEl.style.display = 'none';
    if (targetInput) {
      targetInput.value = '';
      targetInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  // Public method to set image programmatically (e.g. for EDIT modal)
  function setImage(url) {
    if (!url) {
      resetDropzone();
      return;
    }
    const cleanUrl = url.trim();
    if (cleanUrl) {
      const name = cleanUrl.split('/').pop() || 'imagen';
      showPreview(cleanUrl, name);
      if (targetInput) targetInput.value = cleanUrl;
    } else {
      resetDropzone();
    }
  }

  // If input already has a value, display it
  if (targetInput && targetInput.value) {
    setImage(targetInput.value);
  } else if (defaultImage) {
    setImage(defaultImage);
  }

  return {
    setImage,
    reset: resetDropzone,
    container,
    targetInput
  };
}
