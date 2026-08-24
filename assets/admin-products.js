document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('#productForm');
  const input = document.querySelector('#product-video');
  const hidden = document.querySelector('#product-video-file-id');
  const status = document.querySelector('#productVideoStatus');
  if (!form || !input || !hidden || !status) return;
  let submitting = false;
  const file = () => input.files && input.files[0] ? input.files[0] : null;
  const typeError = value => {
    if (!value || !['video/mp4', 'video/quicktime'].includes(value.type)) return 'Choose an MP4 or MOV video.';
    if (value.size > 50 * 1024 * 1024) return 'Videos must be 50 MB or smaller.';
    return '';
  };
  const durationError = value => new Promise(resolve => {
    const video = document.createElement('video');
    video.preload = 'metadata';
    const url = URL.createObjectURL(value);
    video.onloadedmetadata = () => { URL.revokeObjectURL(url); resolve(video.duration > 0 && video.duration <= 60 ? '' : 'Videos must be 60 seconds or shorter.'); };
    video.onerror = () => { URL.revokeObjectURL(url); resolve('That video could not be read.'); };
    video.src = url;
  });
  const setBusy = text => {
    const button = form.querySelector('button[type=submit]');
    if (!button) return;
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.dataset.originalText = button.dataset.originalText || button.textContent;
    button.textContent = text;
  };
  const upload = (value, auth) => new Promise((resolve, reject) => {
    const request = new XMLHttpRequest();
    request.open('POST', auth.upload_url);
    request.upload.onprogress = event => { if (event.lengthComputable) status.textContent = `Uploading video... ${Math.round(event.loaded / event.total * 100)}%`; };
    request.onload = () => {
      let data = {};
      try { data = JSON.parse(request.responseText); } catch (_) {}
      if (request.status >= 200 && request.status < 300 && data.fileId) resolve(data.fileId);
      else reject(new Error('The video upload could not be completed.'));
    };
    request.onerror = () => reject(new Error('The video upload could not be completed.'));
    const body = new FormData();
    body.append('file', value);
    body.append('fileName', `product-video-${Date.now()}.${value.name.split('.').pop().toLowerCase()}`);
    body.append('publicKey', auth.public_key);
    body.append('signature', auth.signature);
    body.append('expire', auth.expire);
    body.append('token', auth.token);
    body.append('folder', auth.folder);
    body.append('useUniqueFileName', 'true');
    request.send(body);
  });
  input.addEventListener('change', async () => {
    hidden.value = '';
    const value = file();
    if (!value) return status.textContent = '';
    const error = typeError(value) || await durationError(value);
    status.textContent = error || 'Video ready. It will upload when you save the product.';
    if (error) input.value = '';
  });
  form.addEventListener('submit', async event => {
    if (submitting) { event.preventDefault(); return; }
    submitting = true;
    setBusy('Saving product...');
    const value = file();
    if (!value) return;
    event.preventDefault();
    const error = typeError(value) || await durationError(value);
    if (error) {
      status.textContent = error;
      submitting = false;
      input.value = '';
      const button = form.querySelector('button[type=submit]');
      button.disabled = false;
      button.removeAttribute('aria-busy');
      button.textContent = button.dataset.originalText || 'Save product';
      return;
    }
    try {
      status.textContent = 'Preparing secure video upload...';
      const authResponse = await fetch('/api/admin-product-media.php?action=upload-auth', {headers: {Accept: 'application/json'}});
      const auth = await authResponse.json().catch(() => ({}));
      if (!authResponse.ok || !auth.ok) throw new Error(auth.error || 'Video upload is not available right now.');
      hidden.value = await upload(value, auth);
      status.textContent = 'Video uploaded. Saving product...';
      HTMLFormElement.prototype.submit.call(form);
    } catch (uploadError) {
      status.textContent = uploadError.message || 'Please try again.';
      submitting = false;
      const button = form.querySelector('button[type=submit]');
      button.disabled = false;
      button.removeAttribute('aria-busy');
      button.textContent = button.dataset.originalText || 'Save product';
    }
  });
});
