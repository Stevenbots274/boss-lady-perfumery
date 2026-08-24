(() => {
  const form = document.querySelector('#testimonialForm');
  if (!form) return;
  const fileInput = document.querySelector('#testimonialMedia');
  const status = document.querySelector('#uploadStatus');
  const progress = document.querySelector('#uploadProgress');
  const result = document.querySelector('#testimonialResult');
  const button = form.querySelector('button[type=submit]');
  let uploadedAsset = null;
  const setStatus = text => { status.textContent = text; };
  const setProgress = value => { progress.style.width = `${Math.max(0, Math.min(100, value))}%`; };
  const fail = text => { setStatus(text); fileInput.value = ''; uploadedAsset = null; setProgress(0); };
  const selectedMedia = () => fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
  const mediaType = file => file.type.startsWith('video/') ? 'video' : 'image';
  const validateFile = file => {
    if (!file || !['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/quicktime'].includes(file.type)) return 'Use a JPG, PNG, WEBP, MP4, or MOV file.';
    const type = mediaType(file);
    if (type === 'image' && file.size > 10 * 1024 * 1024) return 'Images must be 10 MB or smaller.';
    if (type === 'video' && file.size > 50 * 1024 * 1024) return 'Videos must be 50 MB or smaller.';
    return '';
  };
  const validateDuration = file => new Promise(resolve => {
    if (mediaType(file) !== 'video') return resolve('');
    const video = document.createElement('video');
    video.preload = 'metadata';
    video.onloadedmetadata = () => { URL.revokeObjectURL(video.src); resolve(video.duration > 0 && video.duration <= 60 ? '' : 'Videos must be 60 seconds or shorter.'); };
    video.onerror = () => resolve('That video could not be read.');
    video.src = URL.createObjectURL(file);
  });
  const upload = (file, auth) => new Promise((resolve, reject) => {
    const request = new XMLHttpRequest();
    request.open('POST', auth.upload_url);
    request.upload.onprogress = event => { if (event.lengthComputable) setProgress(event.loaded / event.total * 100); };
    request.onload = () => {
      let data = {};
      try { data = JSON.parse(request.responseText); } catch (_) {}
      if (request.status >= 200 && request.status < 300 && data.fileId) resolve({file_id: data.fileId, media_type: mediaType(file)});
      else reject(new Error('The media upload could not be completed.'));
    };
    request.onerror = () => reject(new Error('The media upload could not be completed.'));
    const body = new FormData();
    body.append('file', file);
    body.append('fileName', `testimonial-${Date.now()}.${file.name.split('.').pop().toLowerCase()}`);
    body.append('publicKey', auth.public_key);
    body.append('signature', auth.signature);
    body.append('expire', auth.expire);
    body.append('token', auth.token);
    body.append('folder', auth.folder);
    body.append('useUniqueFileName', 'true');
    request.send(body);
  });
  fileInput.addEventListener('change', async () => {
    uploadedAsset = null;
    setProgress(0);
    const file = selectedMedia();
    if (!file) return setStatus('');
    const fileError = validateFile(file);
    if (fileError) return fail(fileError);
    const durationError = await validateDuration(file);
    if (durationError) return fail(durationError);
    setStatus('Media ready. It will upload when you send the testimonial.');
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    button.disabled = true;
    result.textContent = '';
    try {
      const file = selectedMedia();
      if (file && !uploadedAsset) {
        setStatus('Preparing secure media upload...');
        const type = mediaType(file);
        const authResponse = await fetch(`/api/testimonials.php?action=upload-auth&order_id=${encodeURIComponent(form.dataset.orderId)}&media_type=${type}`, {headers: {Accept: 'application/json'}});
        const auth = await authResponse.json().catch(() => ({}));
        if (!authResponse.ok || !auth.ok) throw new Error(auth.error || 'Media upload is not available right now.');
        setStatus('Uploading media...');
        uploadedAsset = await upload(file, auth);
        setStatus('Media uploaded. Sending your testimonial...');
      }
      const response = await fetch('/api/testimonials.php', {method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json'}, body: JSON.stringify({action: 'submit', order_id: Number(form.dataset.orderId), rating: Number(form.rating.value), message: document.querySelector('#testimonialMessage').value.trim(), media: uploadedAsset})});
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.ok) throw new Error(data.error || 'The testimonial could not be sent.');
      form.hidden = true;
      result.className = 'testimonial-success';
      result.textContent = 'Thank you. Your testimonial is now awaiting review before publication.';
    } catch (error) {
      setStatus(error.message || 'Please try again.');
      button.disabled = false;
    }
  });
})();
