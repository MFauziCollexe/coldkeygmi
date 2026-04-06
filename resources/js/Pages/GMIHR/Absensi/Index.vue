<template>
  <AppLayout>
    <div class="min-h-full bg-slate-100 px-4 py-6 text-slate-700 md:px-6 md:py-8">
      <div class="mx-auto max-w-xl">
        <div class="mb-8 text-center">
          <h1 class="text-3xl font-bold text-slate-700 md:text-4xl">Halo, {{ greetingName }} <span aria-hidden="true">👋</span></h1>
          <p class="mt-2 text-base text-slate-500 md:text-lg">{{ todayLabel }}</p>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.08)] md:p-6">
          <div class="mb-4 border-b border-slate-200 pb-3">
            <div class="text-xl font-semibold text-slate-500">Status:</div>
          </div>

          <div class="border-b border-slate-200 pb-4 text-center">
            <div :class="statusClass" class="text-3xl font-bold md:text-4xl">
              {{ attendanceStatus }}
            </div>
          </div>

          <div class="mt-4 space-y-3 text-lg text-slate-500">
            <div class="flex items-start justify-between gap-4">
              <div>
                <div>Jam Masuk:</div>
                <div class="font-semibold text-slate-700">{{ attendance?.check_in_at || '-' }}</div>
              </div>
              <img
                v-if="attendance?.check_in_photo_url"
                :src="attendance.check_in_photo_url"
                alt="Foto absen masuk"
                class="h-16 w-16 rounded-xl border border-slate-200 object-cover"
              />
            </div>

            <div class="flex items-start justify-between gap-4">
              <div>
                <div>Jam Pulang:</div>
                <div class="font-semibold text-slate-700">{{ attendance?.check_out_at || '-' }}</div>
              </div>
              <img
                v-if="attendance?.check_out_photo_url"
                :src="attendance.check_out_photo_url"
                alt="Foto absen pulang"
                class="h-16 w-16 rounded-xl border border-slate-200 object-cover"
              />
            </div>

            <div v-if="attendance?.check_out_reason" class="flex items-start justify-between gap-4">
              <span>Alasan Pulang:</span>
              <span class="max-w-[70%] text-right font-semibold text-slate-700">{{ attendance.check_out_reason }}</span>
            </div>
          </div>
        </div>

        <button
          type="button"
          class="mt-6 w-full rounded-2xl px-5 py-5 text-2xl font-bold text-white shadow-[0_10px_24px_rgba(34,197,94,0.28)] transition disabled:cursor-not-allowed disabled:bg-slate-400 disabled:shadow-none"
          :class="buttonClass"
          :disabled="buttonDisabled"
          @click="openAttendanceCamera"
        >
          {{ buttonLabel }}
        </button>

        <div class="mt-8 border-t border-slate-300 pt-5 text-base text-slate-600 md:text-lg">
          <div
            v-if="showLocationPermissionNotice"
            class="mb-4 rounded-2xl border px-4 py-3 text-sm"
            :class="locationPermissionNoticeClass"
          >
            <div class="font-semibold">{{ locationPermissionTitle }}</div>
            <div class="mt-1">{{ locationPermissionHelp }}</div>
            <button
              v-if="canRequestLocationPermission"
              type="button"
              class="mt-3 inline-flex items-center rounded-xl bg-sky-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-sky-500"
              @click="requestLocationPermission"
            >
              Izinkan Lokasi
            </button>
          </div>

          <div class="flex items-start gap-3">
            <span class="text-xl">📍</span>
            <div>
              <div class="font-semibold">Lokasi: {{ currentLocationLabel }}</div>
              <div v-if="locationMessage" class="mt-1 text-sm text-slate-500">{{ locationMessage }}</div>
              <div v-if="nearestAreaSummary" class="mt-1 text-sm text-slate-500">{{ nearestAreaSummary }}</div>
              <div v-if="coordinateSummary" class="mt-1 text-xs text-slate-400">{{ coordinateSummary }}</div>
              <div v-if="!props.hasFaceReference" class="mt-2 text-sm text-amber-600">
                Foto referensi wajah employee belum diatur. Lengkapi dulu di master employee.
              </div>
              <button
                type="button"
                class="mt-3 inline-flex items-center rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-400 hover:text-slate-700"
                @click="requestLocationPermission"
              >
                {{ refreshLocationLabel }}
              </button>
            </div>
          </div>

          <div class="mt-3 flex items-start gap-3">
            <span class="text-xl">🕒</span>
            <div class="font-semibold">Shift: {{ shift.label }}</div>
          </div>

          <div v-if="shift.source" class="mt-2 text-sm text-slate-500">
            Sumber jadwal: {{ shift.source }}
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="cameraModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
      @click.self="closeCameraModal"
    >
      <div class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-4 shadow-2xl">
        <div class="mb-4 flex items-center justify-between gap-4">
          <div>
            <h3 class="text-lg font-semibold text-white">{{ cameraTitle }}</h3>
            <p class="text-sm text-slate-400">Gunakan kamera depan, lakukan blink sekali, lalu ambil selfie.</p>
          </div>
          <button
            type="button"
            class="rounded bg-slate-700 px-3 py-2 text-sm text-white hover:bg-slate-600"
            @click="closeCameraModal"
          >
            Tutup
          </button>
        </div>

        <div v-if="cameraError" class="mb-3 rounded border border-rose-700 bg-rose-950/40 px-3 py-2 text-sm text-rose-200">
          {{ cameraError }}
        </div>

        <div class="mb-3 rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-3 text-sm text-slate-200">
          <div class="font-semibold text-white">Liveness Check</div>
          <div class="mt-1">{{ livenessStatus }}</div>
        </div>

        <div v-if="cameraLoading" class="mb-3 text-sm text-slate-400">
          Menyiapkan kamera...
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-700 bg-black">
          <video
            v-if="!capturedPhotoData"
            ref="cameraVideoRef"
            autoplay
            playsinline
            muted
            class="min-h-[320px] w-full bg-black object-cover"
          ></video>
          <img
            v-else
            :src="capturedPhotoData"
            alt="Preview selfie absensi"
            class="min-h-[320px] w-full bg-black object-cover"
          />
        </div>

        <div v-if="capturedPhotoData" class="mt-4 rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-3 text-sm">
          <div class="flex items-center justify-between gap-3">
            <span class="text-slate-300">Skor Kecocokan Wajah</span>
            <span
              class="rounded-full px-3 py-1 font-semibold"
              :class="faceScoreClass"
            >
              {{ faceMatchScore != null ? `${faceMatchScore}%` : '-' }}
            </span>
          </div>
          <div v-if="faceMatchDistance != null" class="mt-2 text-xs text-slate-400">
            Distance: {{ faceMatchDistance.toFixed(4) }} / Max {{ Number(props.faceMatchMaxDistance || 0.5).toFixed(2) }}
          </div>
          <div class="mt-2 text-xs" :class="faceMatchPassed ? 'text-emerald-400' : 'text-rose-400'">
            {{ faceMatchPassed ? 'Wajah cocok dengan referensi employee.' : 'Wajah belum cukup cocok dengan referensi employee.' }}
          </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
          <button
            type="button"
            class="rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-600"
            @click="closeCameraModal"
          >
            Batal
          </button>
          <button
            v-if="capturedPhotoData"
            type="button"
            class="rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500"
            @click="retakePhoto"
          >
            Ulangi Foto
          </button>
          <button
            v-if="!capturedPhotoData"
            type="button"
            :disabled="cameraLoading || !livenessVerified"
            class="rounded bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-500 disabled:cursor-not-allowed disabled:bg-slate-600"
            @click="captureAttendancePhoto"
          >
            {{ livenessVerified ? 'Ambil Selfie' : 'Kedipkan Mata Dulu' }}
          </button>
          <button
            v-else
            type="button"
            :disabled="!faceMatchPassed"
            class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:bg-slate-600"
            @click="submitAttendance"
          >
            Gunakan Foto Ini
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  FACE_MATCH_MAX_DISTANCE,
  computeDescriptorDistance,
  computeMatchScore,
  detectBlinkMetricsFromVideo,
  extractFaceDescriptorFromImage,
  ensureFaceRecognitionModelsLoaded,
} from '@/Utils/faceRecognition';

const props = defineProps({
  greetingName: { type: String, required: true },
  todayLabel: { type: String, required: true },
  attendance: { type: Object, default: null },
  shift: { type: Object, required: true },
  areas: { type: Array, default: () => [] },
  canSubmitAttendance: { type: Boolean, default: true },
  hasFaceReference: { type: Boolean, default: false },
  faceReferencePhotoUrl: { type: String, default: null },
  faceReferenceDescriptor: { type: Array, default: () => [] },
  faceMatchMaxDistance: { type: Number, default: FACE_MATCH_MAX_DISTANCE },
});

const currentLatitude = ref(null);
const currentLongitude = ref(null);
const currentAccuracy = ref(null);
const geolocationReady = ref(false);
const geolocationDenied = ref(false);
const locationPermissionState = ref('prompt');
const locationMessage = ref('Sedang mengambil lokasi Anda...');
const matchedArea = ref(null);
const nearestArea = ref(null);
const nearestDistance = ref(null);

const cameraModalOpen = ref(false);
const cameraLoading = ref(false);
const cameraError = ref('');
const cameraVideoRef = ref(null);
const capturedPhotoData = ref('');
const liveFaceDescriptor = ref([]);
const faceMatchScore = ref(null);
const faceMatchDistance = ref(null);
const faceMatchPassed = ref(false);
const livenessStatus = ref('Posisikan wajah Anda ke kamera.');
const livenessVerified = ref(false);
const referenceFaceDescriptor = ref(Array.isArray(props.faceReferenceDescriptor) ? props.faceReferenceDescriptor : []);

let cameraStream = null;
let livenessInterval = null;
let livenessBusy = false;
let blinkOpenSeen = false;
let blinkClosedSeen = false;

const attendanceStatus = computed(() => props.attendance?.status || 'Belum Absen');
const isCheckOutMode = computed(() => Boolean(props.attendance?.check_in_at && !props.attendance?.check_out_at));

const statusClass = computed(() => {
  if (props.attendance?.check_in_at && props.attendance?.check_out_at) return 'text-emerald-600';
  if (props.attendance?.check_in_at) return 'text-amber-500';
  return 'text-rose-500';
});

const buttonLabel = computed(() => {
  if (!props.attendance?.check_in_at) return 'Absen Masuk';
  if (!props.attendance?.check_out_at) return 'Absen Pulang';
  return 'Absensi Selesai';
});

const buttonClass = computed(() => {
  if (props.attendance?.check_out_at) return 'bg-slate-400';
  if (props.attendance?.check_in_at) return 'bg-sky-600 hover:bg-sky-500';
  return 'bg-emerald-600 hover:bg-emerald-500';
});

const buttonDisabled = computed(() => {
  if (!props.canSubmitAttendance) return true;
  if (!props.hasFaceReference) return true;
  if (props.attendance?.check_out_at) return true;
  if (!props.areas.length) return true;
  if (!geolocationReady.value) return true;
  return !matchedArea.value;
});

const cameraTitle = computed(() => (isCheckOutMode.value ? 'Face Absen Pulang' : 'Face Absen Masuk'));

const currentLocationLabel = computed(() => {
  if (!props.areas.length) return 'Area kantor belum diatur';
  if (matchedArea.value) return matchedArea.value.name;
  if (geolocationDenied.value) return 'Akses lokasi ditolak';
  if (!geolocationReady.value) return 'Mengecek lokasi...';
  return 'Di luar area kantor';
});

const coordinateSummary = computed(() => {
  if (currentLatitude.value == null || currentLongitude.value == null) {
    return null;
  }

  return `Koordinat terbaca: ${currentLatitude.value.toFixed(6)}, ${currentLongitude.value.toFixed(6)}`;
});

const nearestAreaSummary = computed(() => {
  if (!nearestArea.value || nearestDistance.value == null) {
    return null;
  }

  return `Area terdekat: ${nearestArea.value.name} (${Math.round(nearestDistance.value)} m). Radius area: ${nearestArea.value.radius_meters} m.`;
});

const showLocationPermissionNotice = computed(() => ['prompt', 'denied'].includes(locationPermissionState.value));

const canRequestLocationPermission = computed(() => locationPermissionState.value !== 'denied');

const locationPermissionTitle = computed(() => {
  if (locationPermissionState.value === 'denied') {
    return 'Akses lokasi diblokir';
  }

  return 'Izin lokasi diperlukan';
});

const locationPermissionHelp = computed(() => {
  if (locationPermissionState.value === 'denied') {
    return 'Browser sudah menolak akses lokasi. Klik tombol di bawah untuk melihat langkah mengaktifkan lokasi, lalu coba lagi.';
  }

  return 'Izinkan akses lokasi agar sistem bisa memastikan Anda berada di area kantor yang diperbolehkan untuk absensi.';
});

const locationPermissionNoticeClass = computed(() => {
  if (locationPermissionState.value === 'denied') {
    return 'border-rose-200 bg-rose-50 text-rose-700';
  }

  return 'border-sky-200 bg-sky-50 text-sky-700';
});

const refreshLocationLabel = computed(() => {
  if (locationPermissionState.value === 'prompt') {
    return 'Izinkan Lokasi';
  }

  if (locationPermissionState.value === 'denied') {
    return 'Cek Lokasi Lagi';
  }

  return 'Refresh Lokasi';
});

const requiresCheckoutReason = computed(() => {
  if (!props.attendance?.check_in_at || props.attendance?.check_out_at) {
    return false;
  }

  if (props.shift?.is_holiday || props.shift?.is_off) {
    return true;
  }

  if (!props.shift?.end_time) {
    return false;
  }

  const now = new Date();
  const [endHour, endMinute] = String(props.shift.end_time).split(':').map(Number);
  const scheduledEnd = new Date();
  scheduledEnd.setHours(endHour || 0, endMinute || 0, 0, 0);

  return now < scheduledEnd;
});

const faceScoreClass = computed(() => {
  if (faceMatchPassed.value) {
    return 'bg-emerald-950/50 text-emerald-300';
  }

  return 'bg-rose-950/50 text-rose-300';
});

const hasUsableReferenceDescriptor = computed(() => Array.isArray(referenceFaceDescriptor.value) && referenceFaceDescriptor.value.length === 128);

function distanceInMeters(lat1, lon1, lat2, lon2) {
  const earthRadius = 6371000;
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLon = ((lon2 - lon1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLon / 2) *
      Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return earthRadius * c;
}

function resolveAccuracyGrace(accuracy) {
  if (accuracy == null || Number.isNaN(Number(accuracy)) || Number(accuracy) <= 0) {
    return 0;
  }

  return Math.min(Number(accuracy), 10000);
}

function resolveArea(latitude, longitude, accuracy = null) {
  return props.areas.find((area) => {
    const distance = distanceInMeters(latitude, longitude, Number(area.latitude), Number(area.longitude));
    const accuracyGrace = Math.min(resolveAccuracyGrace(accuracy), Math.max(Number(area.radius_meters), 10));
    return distance <= Number(area.radius_meters) + accuracyGrace;
  }) || null;
}

function resolveNearestArea(latitude, longitude) {
  if (!props.areas.length) {
    return { area: null, distance: null };
  }

  const candidates = props.areas.map((area) => ({
    area,
    distance: distanceInMeters(latitude, longitude, Number(area.latitude), Number(area.longitude)),
  }));

  candidates.sort((left, right) => left.distance - right.distance);

  return candidates[0] || { area: null, distance: null };
}

function updateGeoState(position) {
  currentLatitude.value = Number(position.coords.latitude);
  currentLongitude.value = Number(position.coords.longitude);
  currentAccuracy.value = position.coords.accuracy != null ? Number(position.coords.accuracy) : null;
  geolocationReady.value = true;
  geolocationDenied.value = false;

  const closest = resolveNearestArea(currentLatitude.value, currentLongitude.value);
  nearestArea.value = closest.area;
  nearestDistance.value = closest.distance;
  matchedArea.value = resolveArea(currentLatitude.value, currentLongitude.value, currentAccuracy.value);

  locationMessage.value = matchedArea.value
    ? `Anda berada di area kantor yang diizinkan${currentAccuracy.value ? `. Akurasi GPS sekitar ${Math.round(currentAccuracy.value)} m.` : '.'}`
    : `Anda harus berada di area kantor untuk melakukan absensi${currentAccuracy.value ? `. Akurasi GPS saat ini sekitar ${Math.round(currentAccuracy.value)} m.` : '.'}`;
}

async function syncLocationPermissionState() {
  if (!navigator.permissions?.query) {
    return;
  }

  try {
    const status = await navigator.permissions.query({ name: 'geolocation' });
    locationPermissionState.value = status.state;
    status.onchange = () => {
      locationPermissionState.value = status.state;
      if (status.state === 'granted') {
        requestLocation(false);
      }
    };
  } catch {
    // Browser belum mendukung permissions API untuk geolocation.
  }
}

function requestLocation(showPromptInfo = false) {
  if (!navigator.geolocation) {
    geolocationReady.value = false;
    locationMessage.value = 'Browser ini tidak mendukung geolocation.';
    return;
  }

  geolocationReady.value = false;
  geolocationDenied.value = false;
  locationMessage.value = 'Sedang mengambil lokasi Anda...';

  navigator.geolocation.getCurrentPosition(
    (position) => {
      locationPermissionState.value = 'granted';
      updateGeoState(position);
    },
    () => {
      geolocationReady.value = false;
      geolocationDenied.value = true;
      locationPermissionState.value = 'denied';
      matchedArea.value = null;
      nearestArea.value = null;
      nearestDistance.value = null;
      locationMessage.value = 'Izinkan akses lokasi agar bisa melakukan absensi.';

      if (showPromptInfo) {
        Swal.fire({
          icon: 'warning',
          title: 'Izin Lokasi Diperlukan',
          text: 'Izinkan akses lokasi di browser agar absensi bisa dilakukan.',
          confirmButtonText: 'Mengerti',
        });
      }
    },
    {
      enableHighAccuracy: true,
      maximumAge: 0,
      timeout: 15000,
    },
  );
}

async function requestLocationPermission() {
  if (locationPermissionState.value === 'denied') {
    const result = await Swal.fire({
      icon: 'warning',
      title: 'Akses Lokasi Diblokir',
      html: `
        <div style="text-align:left">
          <div style="margin-bottom:8px;">Aktifkan lokasi untuk halaman ini dengan salah satu cara berikut:</div>
          <ol style="padding-left:18px; margin:0; line-height:1.6">
            <li>Buka <b>Chrome &gt; Settings &gt; Site settings &gt; Location</b>, lalu pastikan lokasi diizinkan.</li>
            <li>Hapus blokir untuk <b>101.0.5.107:8000</b> jika situs ini ada di daftar blocked.</li>
            <li>Pastikan di Android, izin <b>Chrome &gt; Lokasi</b> sudah <b>Izinkan saat aplikasi digunakan</b>.</li>
          </ol>
          <div style="margin-top:10px;">Setelah diubah, tekan <b>Saya Sudah Izinkan</b> untuk cek ulang lokasi.</div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Saya Sudah Izinkan',
      cancelButtonText: 'Tutup',
    });

    if (result.isConfirmed) {
      locationPermissionState.value = 'prompt';
      requestLocation(false);
    }

    return;
  }

  requestLocation(true);
}

function resetFaceVerificationState() {
  liveFaceDescriptor.value = [];
  faceMatchScore.value = null;
  faceMatchDistance.value = null;
  faceMatchPassed.value = false;
}

function resetLivenessState() {
  livenessVerified.value = false;
  livenessStatus.value = 'Posisikan wajah Anda ke kamera.';
  blinkOpenSeen = false;
  blinkClosedSeen = false;
}

function stopLivenessMonitor() {
  if (livenessInterval) {
    clearInterval(livenessInterval);
    livenessInterval = null;
  }
}

function startLivenessMonitor() {
  stopLivenessMonitor();
  resetLivenessState();

  livenessInterval = setInterval(async () => {
    if (!cameraVideoRef.value || capturedPhotoData.value || livenessBusy) {
      return;
    }

    livenessBusy = true;

    try {
      const metrics = await detectBlinkMetricsFromVideo(cameraVideoRef.value);

      if (!metrics) {
        livenessStatus.value = 'Wajah belum terdeteksi. Arahkan wajah lurus ke kamera.';
        return;
      }

      const ear = Number(metrics.ear || 0);

      if (!blinkOpenSeen && ear > 0.23) {
        blinkOpenSeen = true;
        livenessStatus.value = 'Wajah terdeteksi. Silakan kedipkan mata satu kali.';
        return;
      }

      if (blinkOpenSeen && !blinkClosedSeen && ear < 0.18) {
        blinkClosedSeen = true;
        livenessStatus.value = 'Kedipan terdeteksi. Buka mata kembali...';
        return;
      }

      if (blinkOpenSeen && blinkClosedSeen && ear > 0.22) {
        livenessVerified.value = true;
        livenessStatus.value = 'Liveness terverifikasi. Silakan ambil selfie.';
        stopLivenessMonitor();
      }
    } catch {
      livenessStatus.value = 'Sedang memverifikasi kedipan...';
    } finally {
      livenessBusy = false;
    }
  }, 700);
}

async function openAttendanceCamera() {
  if (buttonDisabled.value) {
    return;
  }

  await ensureFaceRecognitionModelsLoaded();
  if (!hasUsableReferenceDescriptor.value && props.faceReferencePhotoUrl) {
    try {
      referenceFaceDescriptor.value = await extractFaceDescriptorFromImage(props.faceReferencePhotoUrl);
    } catch (error) {
      await Swal.fire({
        icon: 'error',
        title: 'Referensi Wajah Belum Siap',
        text: error?.message || 'Foto referensi wajah tidak bisa diproses. Silakan buka Employee lalu klik Update lagi.',
      });
      return;
    }
  }

  cameraError.value = '';
  cameraLoading.value = true;
  capturedPhotoData.value = '';
  resetFaceVerificationState();
  resetLivenessState();
  cameraModalOpen.value = true;

  await nextTick();
  await startCamera();
}

async function startCamera() {
  try {
    await stopCamera();

    if (!navigator.mediaDevices?.getUserMedia) {
      throw new Error('Browser ini tidak mendukung akses kamera.');
    }

    cameraStream = await navigator.mediaDevices.getUserMedia({
      audio: false,
      video: {
        facingMode: { ideal: 'user' },
      },
    });

    if (cameraVideoRef.value) {
      cameraVideoRef.value.srcObject = cameraStream;
      await cameraVideoRef.value.play();
      startLivenessMonitor();
    }

    cameraError.value = '';
  } catch (error) {
    cameraError.value = normalizeCameraError(error);
    await stopCamera();
  } finally {
    cameraLoading.value = false;
  }
}

async function captureAttendancePhoto() {
  if (!cameraVideoRef.value) {
    return;
  }

  if (!livenessVerified.value) {
    cameraError.value = 'Kedipkan mata dulu untuk liveness check.';
    return;
  }

  const video = cameraVideoRef.value;
  const width = video.videoWidth || 720;
  const height = video.videoHeight || 960;
  const canvas = document.createElement('canvas');

  canvas.width = width;
  canvas.height = height;

  const context = canvas.getContext('2d');
  if (!context) {
    cameraError.value = 'Foto gagal diproses.';
    return;
  }

  context.drawImage(video, 0, 0, width, height);
  const preview = canvas.toDataURL('image/jpeg', 0.9);

  try {
    const descriptor = await extractFaceDescriptorFromImage(preview);
    const distance = computeDescriptorDistance(referenceFaceDescriptor.value, descriptor);
    const score = computeMatchScore(distance, props.faceMatchMaxDistance);

    capturedPhotoData.value = preview;
    liveFaceDescriptor.value = descriptor;
    faceMatchDistance.value = distance;
    faceMatchScore.value = score;
    faceMatchPassed.value = distance != null && distance <= Number(props.faceMatchMaxDistance || FACE_MATCH_MAX_DISTANCE);

    if (!faceMatchPassed.value) {
      cameraError.value = `Skor kecocokan wajah ${score}%. Ulangi selfie dengan posisi wajah lebih jelas.`;
    } else {
      cameraError.value = '';
    }

    await stopCamera();
  } catch (error) {
    cameraError.value = error?.message || 'Wajah tidak dapat diproses untuk verifikasi.';
  }
}

async function retakePhoto() {
  capturedPhotoData.value = '';
  cameraLoading.value = true;
  cameraError.value = '';
  resetFaceVerificationState();
  await nextTick();
  await startCamera();
}

async function closeCameraModal() {
  cameraModalOpen.value = false;
  cameraLoading.value = false;
  cameraError.value = '';
  capturedPhotoData.value = '';
  resetFaceVerificationState();
  stopLivenessMonitor();
  resetLivenessState();
  await stopCamera();
}

async function stopCamera() {
  if (cameraVideoRef.value?.srcObject) {
    cameraVideoRef.value.srcObject = null;
  }

  if (cameraStream) {
    cameraStream.getTracks().forEach((track) => track.stop());
  }

  cameraStream = null;
}

function normalizeCameraError(error) {
  const message = String(error?.message || '').toLowerCase();

  if (message.includes('permission') || message.includes('denied')) {
    return 'Izin kamera ditolak. Izinkan akses kamera untuk selfie absensi.';
  }

  if (message.includes('notfound') || message.includes('device')) {
    return 'Kamera tidak ditemukan di perangkat ini.';
  }

  return error?.message || 'Kamera gagal dijalankan.';
}

async function submitAttendance() {
  if (buttonDisabled.value || !capturedPhotoData.value || !faceMatchPassed.value || liveFaceDescriptor.value.length !== 128) {
    return;
  }

  let checkOutReason = null;

  if (isCheckOutMode.value && requiresCheckoutReason.value) {
    const reasonPromptText = props.shift?.is_holiday
      ? 'Hari ini termasuk hari libur. Isi alasan untuk melanjutkan absen pulang.'
      : props.shift?.is_off
        ? 'Hari ini jadwal Anda OFF. Isi alasan untuk melanjutkan absen pulang.'
        : `Jam pulang shift Anda ${props.shift.end_time}. Isi alasan untuk melanjutkan absen pulang.`;

    const { isConfirmed, value } = await Swal.fire({
      title: 'Alasan Absen Pulang',
      text: reasonPromptText,
      input: 'textarea',
      inputPlaceholder: 'Tulis alasan absen pulang...',
      inputAttributes: {
        maxlength: 1000,
      },
      inputValidator: (value) => {
        if (!String(value || '').trim()) {
          return 'Alasan wajib diisi.';
        }

        return null;
      },
      showCancelButton: true,
      confirmButtonText: 'Lanjutkan',
      cancelButtonText: 'Batal',
    });

    if (!isConfirmed) {
      return;
    }

    checkOutReason = String(value || '').trim();
  }

  Swal.fire({
    title: 'Memproses Absensi...',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading(),
  });

  router.post(
    '/absensi/submit',
    {
      latitude: currentLatitude.value,
      longitude: currentLongitude.value,
      accuracy: currentAccuracy.value,
      check_out_reason: checkOutReason,
      photo_data: capturedPhotoData.value,
      face_descriptor: liveFaceDescriptor.value,
      reference_descriptor: hasUsableReferenceDescriptor.value ? referenceFaceDescriptor.value : [],
    },
    {
      preserveScroll: true,
      onFinish: async () => {
        Swal.close();
        await closeCameraModal();
      },
    },
  );
}

onMounted(() => {
  syncLocationPermissionState();
  requestLocation();
});

onBeforeUnmount(() => {
  stopLivenessMonitor();
  stopCamera();
});
</script>
