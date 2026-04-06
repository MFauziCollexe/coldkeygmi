import * as faceapi from 'face-api.js';

export const FACE_MATCH_MAX_DISTANCE = 0.5;

let modelsPromise = null;

function tinyDetectorOptions() {
  return new faceapi.TinyFaceDetectorOptions({
    inputSize: 320,
    scoreThreshold: 0.5,
  });
}

export async function ensureFaceRecognitionModelsLoaded() {
  if (!modelsPromise) {
    modelsPromise = Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri('/face-api-models'),
      faceapi.nets.faceLandmark68TinyNet.loadFromUri('/face-api-models'),
      faceapi.nets.faceRecognitionNet.loadFromUri('/face-api-models'),
    ]);
  }

  return modelsPromise;
}

export function fileToDataUrl(file) {
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

function blobToFile(blob, originalName = 'face-reference.jpg') {
  const fallbackName = String(originalName || 'face-reference.jpg')
    .replace(/\.[^.]+$/, '')
    .concat('.jpg');

  return new File([blob], fallbackName, {
    type: 'image/jpeg',
    lastModified: Date.now(),
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

export async function compressFaceReferenceFile(file, options = {}) {
  const {
    maxDimension = 1280,
    maxBytes = 900 * 1024,
    initialQuality = 0.9,
    minimumQuality = 0.55,
  } = options;

  const sourceDataUrl = await fileToDataUrl(file);
  const image = await dataUrlToImage(sourceDataUrl);

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

  context.drawImage(image, 0, 0, targetWidth, targetHeight);

  let quality = initialQuality;
  let blob = await canvasToBlob(canvas, quality);

  while (blob.size > maxBytes && quality > minimumQuality) {
    quality = Math.max(minimumQuality, quality - 0.1);
    blob = await canvasToBlob(canvas, quality);
  }

  const compressedFile = blobToFile(blob, file.name);
  const dataUrl = await fileToDataUrl(compressedFile);

  return {
    file: compressedFile,
    dataUrl,
    originalSize: file.size,
    compressedSize: compressedFile.size,
  };
}

export async function extractFaceDescriptorFromImage(imageSource) {
  await ensureFaceRecognitionModelsLoaded();

  const image = await faceapi.fetchImage(imageSource);
  const detection = await faceapi
    .detectSingleFace(image, tinyDetectorOptions())
    .withFaceLandmarks(true)
    .withFaceDescriptor();

  if (!detection) {
    throw new Error('Wajah tidak terdeteksi. Gunakan foto dengan satu wajah yang terlihat jelas.');
  }

  return Array.from(detection.descriptor || []);
}

export function computeDescriptorDistance(referenceDescriptor, liveDescriptor) {
  if (!Array.isArray(referenceDescriptor) || !Array.isArray(liveDescriptor) || referenceDescriptor.length !== 128 || liveDescriptor.length !== 128) {
    return null;
  }

  let sum = 0;
  for (let index = 0; index < referenceDescriptor.length; index += 1) {
    const delta = Number(referenceDescriptor[index] || 0) - Number(liveDescriptor[index] || 0);
    sum += delta * delta;
  }

  return Math.sqrt(sum);
}

export function computeMatchScore(distance, maxDistance = FACE_MATCH_MAX_DISTANCE) {
  if (distance == null || Number.isNaN(Number(distance))) {
    return 0;
  }

  const normalized = Math.max(0, 1 - (Number(distance) / Number(maxDistance || FACE_MATCH_MAX_DISTANCE)));
  return Math.round(normalized * 100);
}

function pointDistance(pointA, pointB) {
  const dx = Number(pointA.x || 0) - Number(pointB.x || 0);
  const dy = Number(pointA.y || 0) - Number(pointB.y || 0);
  return Math.sqrt((dx * dx) + (dy * dy));
}

function computeEyeAspectRatio(eyePoints) {
  if (!Array.isArray(eyePoints) || eyePoints.length < 6) {
    return null;
  }

  const verticalA = pointDistance(eyePoints[1], eyePoints[5]);
  const verticalB = pointDistance(eyePoints[2], eyePoints[4]);
  const horizontal = pointDistance(eyePoints[0], eyePoints[3]);

  if (!horizontal) {
    return null;
  }

  return (verticalA + verticalB) / (2 * horizontal);
}

export async function detectBlinkMetricsFromVideo(videoElement) {
  await ensureFaceRecognitionModelsLoaded();

  const detection = await faceapi
    .detectSingleFace(videoElement, tinyDetectorOptions())
    .withFaceLandmarks(true);

  if (!detection?.landmarks) {
    return null;
  }

  const leftEar = computeEyeAspectRatio(detection.landmarks.getLeftEye());
  const rightEar = computeEyeAspectRatio(detection.landmarks.getRightEye());
  const earValues = [leftEar, rightEar].filter((value) => typeof value === 'number');

  if (!earValues.length) {
    return null;
  }

  const averageEar = earValues.reduce((sum, value) => sum + value, 0) / earValues.length;

  return {
    ear: averageEar,
  };
}
