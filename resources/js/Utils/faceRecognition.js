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
