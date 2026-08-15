function fileToDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result || ''));
    reader.onerror = () => reject(new Error('Gagal membaca file foto.'));
    reader.readAsDataURL(file);
  });
}

function dataUrlToImage(dataUrl) {
  return new Promise((resolve, reject) => {
    const image = new Image();
    image.onload = () => resolve(image);
    image.onerror = () => reject(new Error('Foto gagal dibuka untuk kompresi.'));
    image.src = dataUrl;
  });
}

function canvasToBlob(canvas, quality) {
  return new Promise((resolve, reject) => {
    canvas.toBlob((blob) => {
      if (!blob) {
        reject(new Error('Foto gagal dikompresi.'));
        return;
      }
      resolve(blob);
    }, 'image/jpeg', quality);
  });
}

function toJpegFile(blob, originalName) {
  const baseName = String(originalName || 'foto').replace(/\.[^.]+$/, '');
  return new File([blob], `${baseName}.jpg`, {
    type: 'image/jpeg',
    lastModified: Date.now(),
  });
}

export async function compressImageToMax(file, options = {}) {
  const {
    maxBytes = 5 * 1024 * 1024,
    maxDimension = 1920,
    initialQuality = 0.85,
    minimumQuality = 0.5,
  } = options;

  if (!file || file.size <= maxBytes) {
    return file;
  }

  const dataUrl = await fileToDataUrl(file);
  const image = await dataUrlToImage(dataUrl);

  const width = Number(image.naturalWidth || image.width || 0);
  const height = Number(image.naturalHeight || image.height || 0);

  if (!width || !height) {
    throw new Error('Ukuran foto tidak valid.');
  }

  const scale = Math.min(1, maxDimension / Math.max(width, height));
  const targetWidth = Math.max(1, Math.round(width * scale));
  const targetHeight = Math.max(1, Math.round(height * scale));

  const canvas = document.createElement('canvas');
  canvas.width = targetWidth;
  canvas.height = targetHeight;

  const context = canvas.getContext('2d');
  if (!context) {
    throw new Error('Canvas browser tidak tersedia untuk memproses foto.');
  }

  context.imageSmoothingEnabled = true;
  context.imageSmoothingQuality = 'high';
  context.drawImage(image, 0, 0, targetWidth, targetHeight);

  let quality = initialQuality;
  let blob = await canvasToBlob(canvas, quality);

  while (blob.size > maxBytes && quality > minimumQuality) {
    quality = Math.max(minimumQuality, quality - 0.1);
    blob = await canvasToBlob(canvas, quality);
  }

  let factor = 0.9;
  while (blob.size > maxBytes && factor >= 0.4) {
    const resizedWidth = Math.max(1, Math.round(targetWidth * factor));
    const resizedHeight = Math.max(1, Math.round(targetHeight * factor));
    canvas.width = resizedWidth;
    canvas.height = resizedHeight;
    context.drawImage(image, 0, 0, resizedWidth, resizedHeight);
    blob = await canvasToBlob(canvas, quality);
    factor -= 0.1;
  }

  if (blob.size > maxBytes) {
    throw new Error('Foto terlalu besar untuk dikompres ke maksimal 5MB.');
  }

  return toJpegFile(blob, file.name);
}
