(() => {
  const form = document.querySelector('#testimonialForm');
  if (!form) return;
  const status = document.querySelector('#uploadStatus');
  const progress = document.querySelector('#uploadProgress');
  const result = document.querySelector('#testimonialResult');
  const button = form.querySelector('button[type=submit]');
  const legacyInput = document.querySelector('#testimonialMedia');
  if (legacyInput) {
    const imageLabel = document.querySelector('label[for="testimonialMedia"]');
    legacyInput.id = 'testimonialImage';
    legacyInput.accept = 'image/jpeg,image/png,image/webp';
    if (imageLabel) {
      imageLabel.htmlFor = 'testimonialImage';
      imageLabel.textContent = 'Add an image';
      const videoLabel = imageLabel.cloneNode(true);
      videoLabel.htmlFor = 'testimonialVideo';
      videoLabel.textContent = 'Add a short video';
      const videoInput = legacyInput.cloneNode(true);
      videoInput.id = 'testimonialVideo';
      videoInput.accept = 'video/mp4,video/quicktime';
      const picker = document.createElement('div');
      picker.className = 'media-pickers';
      const anchor = imageLabel || legacyInput;
      anchor.parentNode.insertBefore(picker, anchor);
      if (imageLabel) picker.appendChild(imageLabel);
      picker.appendChild(legacyInput);
      picker.appendChild(videoLabel);
      picker.appendChild(videoInput);
    }
    const help = form.querySelector('.media-help');
    if (help) help.textContent = 'At least one is required. Images up to 10 MB. MP4 or MOV videos up to 50 MB and 60 seconds.';
  }
  const inputs = {image: document.querySelector('#testimonialImage'), video: document.querySelector('#testimonialVideo')};
  const uploadedAssets = {image: null, video: null};
  let submitting = false;
  const setStatus = text => { status.textContent = text; };
  const setProgress = value => { progress.style.width = `${Math.max(0, Math.min(100, value))}%`; };
  const fail = (type, text) => { setStatus(text); inputs[type].value = ''; uploadedAssets[type] = null; setProgress(0); };
  const selectedMedia = type => inputs[type].files && inputs[type].files[0] ? inputs[type].files[0] : null;
  const validateFile = (file, type) => {
    const accepted = type === 'image' ? ['image/jpeg', 'image/png', 'image/webp'] : ['video/mp4', 'video/quicktime'];
    if (!file || !accepted.includes(file.type)) return type === 'image' ? 'Use a JPG, PNG, or WEBP image.' : 'Use an MP4 or MOV video.';
    if (type === 'image' && file.size > 10 * 1024 * 1024) return 'Images must be 10 MB or smaller.';
    if (type === 'video' && file.size > 50 * 1024 * 1024) return 'Videos must be 50 MB or smaller.';
    return '';
  };
  const validateDuration = (file, type) => new Promise(resolve => {
    if (type !== 'video') return resolve('');
    const video = document.createElement('video');
    video.preload = 'metadata';
    video.onloadedmetadata = () => { URL.revokeObjectURL(video.src); resolve(video.duration > 0 && video.duration <= 60 ? '' : 'Videos must be 60 seconds or shorter.'); };
    video.onerror = () => { URL.revokeObjectURL(video.src); resolve('That video could not be read.'); };
    video.src = URL.createObjectURL(file);
  });
  const upload = (file, type, auth) => new Promise((resolve, reject) => {
    const request = new XMLHttpRequest();
    request.open('POST', auth.upload_url);
    request.upload.onprogress = event => { if (event.lengthComputable) setProgress(event.loaded / event.total * 100); };
    request.onload = () => {
      let data = {};
      try { data = JSON.parse(request.responseText); } catch (_) {}
      if (request.status >= 200 && request.status < 300 && data.fileId) resolve({file_id: data.fileId, media_type: type});
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
  Object.entries(inputs).forEach(([type, input]) => input.addEventListener('change', async () => {
    uploadedAssets[type] = null;
    setProgress(0);
    const file = selectedMedia(type);
    if (!file) return setStatus('');
    const fileError = validateFile(file, type);
    if (fileError) return fail(type, fileError);
    const durationError = await validateDuration(file, type);
    if (durationError) return fail(type, durationError);
    setStatus(`${type === 'image' ? 'Image' : 'Video'} ready. It will upload when you send the testimonial.`);
  }));
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (submitting) return;
    submitting = true;
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.dataset.originalText = button.textContent;
    button.textContent = 'Sending securely...';
    result.textContent = '';
    try {
      const selected = Object.keys(inputs).map(type => ({type, file: selectedMedia(type)})).filter(item => item.file);
      if (!selected.length) throw new Error('Add at least one image or video before sending your testimonial.');
      const media = [];
      for (const item of selected) {
        const fileError = validateFile(item.file, item.type);
        if (fileError) throw new Error(fileError);
        const durationError = await validateDuration(item.file, item.type);
        if (durationError) throw new Error(durationError);
        if (!uploadedAssets[item.type]) {
          setStatus(`Preparing secure ${item.type} upload...`);
          const authResponse = await fetch(`/api/testimonials.php?action=upload-auth&order_id=${encodeURIComponent(form.dataset.orderId)}&media_type=${item.type}`, {headers: {Accept: 'application/json'}});
          const auth = await authResponse.json().catch(() => ({}));
          if (!authResponse.ok || !auth.ok) throw new Error(auth.error || 'Media upload is not available right now.');
          setStatus(`Uploading ${item.type}...`);
          uploadedAssets[item.type] = await upload(item.file, item.type, auth);
        }
        media.push(uploadedAssets[item.type]);
      }
      setStatus('Media uploaded. Sending your testimonial...');
      const response = await fetch('/api/testimonials.php', {method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json'}, body: JSON.stringify({action: 'submit', order_id: Number(form.dataset.orderId), rating: Number(form.rating.value), message: document.querySelector('#testimonialMessage').value.trim(), media})});
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.ok) throw new Error(data.error || 'The testimonial could not be sent.');
      form.hidden = true;
      result.className = 'testimonial-success';
      result.textContent = 'Thank you. Your testimonial is now awaiting review before publication.';
    } catch (error) {
      setStatus(error.message || 'Please try again.');
      submitting = false;
      button.disabled = false;
      button.removeAttribute('aria-busy');
      button.textContent = button.dataset.originalText || 'Send for review ↗';
    }
  });
})();
