import "./bootstrap";
import { createInertiaApp } from "@inertiajs/vue3";
import { createApp, h } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

let globalLoadingCount = 0;
let isHandlingSessionExpiry = false;

function showSessionExpiredNotice() {
    if (isHandlingSessionExpiry) {
        return;
    }

    isHandlingSessionExpiry = true;
    globalLoadingCount = 0;
    closeGlobalLoading();

    Swal.fire({
        icon: "warning",
        title: "Sesi request kadaluarsa",
        text: "Aksi belum berhasil diproses. Silakan refresh halaman lalu coba lagi.",
        confirmButtonText: "OK",
    }).finally(() => {
        isHandlingSessionExpiry = false;
    });
}

function isGlobalLoadingPopup() {
    const popup = Swal.getPopup();
    return Boolean(popup && popup.getAttribute("data-global-loading") === "1");
}

function openGlobalLoading() {
    if (isGlobalLoadingPopup()) {
        return;
    }

    Swal.fire({
        title: "Loading...",
        width: 320,
        // text: "Sedang memproses data",
        allowEscapeKey: false,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            const popup = Swal.getPopup();
            if (popup) {
                popup.setAttribute("data-global-loading", "1");
            }
            Swal.showLoading();
        },
    });
}

function closeGlobalLoading() {
    if (globalLoadingCount > 0) {
        return;
    }

    if (isGlobalLoadingPopup()) {
        Swal.close();
    }
}

function beginGlobalLoading() {
    globalLoadingCount += 1;
    openGlobalLoading();
}

function endGlobalLoading() {
    globalLoadingCount = Math.max(0, globalLoadingCount - 1);
    closeGlobalLoading();
}

axios.interceptors.request.use(
    (config) => {
        if (!config?.headers?.["X-Skip-Global-Loading"]) {
            beginGlobalLoading();
        }
        return config;
    },
    (error) => {
        endGlobalLoading();
        return Promise.reject(error);
    },
);

axios.interceptors.response.use(
    (response) => {
        endGlobalLoading();
        return response;
    },
    (error) => {
        endGlobalLoading();

        if (error?.response?.status === 419) {
            showSessionExpiredNotice();
        }

        return Promise.reject(error);
    },
);

document.addEventListener("inertia:start", beginGlobalLoading);
document.addEventListener("inertia:finish", endGlobalLoading);
document.addEventListener("inertia:error", endGlobalLoading);
document.addEventListener("inertia:invalid", endGlobalLoading);
document.addEventListener("inertia:exception", endGlobalLoading);
document.addEventListener("inertia:start", () => {
    const el = document.getElementById("app");
    if (el) {
        el.setAttribute("data-page", "");
    }
});
document.addEventListener("inertia:success", (event) => {
    const el = document.getElementById("app");
    if (el && event?.detail?.page) {
        el.setAttribute("data-page", JSON.stringify(event.detail.page));
    }
});
document.addEventListener("inertia:error", (event) => {
    if (event?.detail?.response?.status === 419) {
        showSessionExpiredNotice();
    }
});
document.addEventListener("inertia:invalid", (event) => {
    if (event?.detail?.response?.status === 419) {
        event.preventDefault?.();
        showSessionExpiredNotice();
    }
});
document.addEventListener("inertia:exception", (event) => {
    if (event?.detail?.response?.status === 419) {
        event.preventDefault?.();
        showSessionExpiredNotice();
    }
});

const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });

const appName = import.meta.env.VITE_APP_NAME || "ColdKey";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const directKey = `./Pages/${name}.vue`;
        const directPage = pages[directKey];

        if (directPage) {
            return directPage.default || directPage;
        }

        const fallbackKey = Object.keys(pages).find((key) => key.toLowerCase() === directKey.toLowerCase());
        if (fallbackKey) {
            const fallbackPage = pages[fallbackKey];
            return fallbackPage.default || fallbackPage;
        }

        throw new Error(`Inertia page not found: ${name}`);
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: "#4F46E5",
    },
});
