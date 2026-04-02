import "./bootstrap";
import { createInertiaApp } from "@inertiajs/vue3";
import { createApp, h } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

let globalLoadingCount = 0;
let isHandlingSessionExpiry = false;

function redirectToLoginOnSessionExpired() {
    if (isHandlingSessionExpiry) {
        return;
    }

    isHandlingSessionExpiry = true;
    globalLoadingCount = 0;
    closeGlobalLoading();
    window.location.replace("/login");
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
            redirectToLoginOnSessionExpired();
        }

        return Promise.reject(error);
    },
);

document.addEventListener("inertia:start", beginGlobalLoading);
document.addEventListener("inertia:finish", endGlobalLoading);
document.addEventListener("inertia:error", endGlobalLoading);
document.addEventListener("inertia:invalid", endGlobalLoading);
document.addEventListener("inertia:exception", endGlobalLoading);
document.addEventListener("inertia:error", (event) => {
    if (event?.detail?.response?.status === 419) {
        redirectToLoginOnSessionExpired();
    }
});
document.addEventListener("inertia:invalid", (event) => {
    if (event?.detail?.response?.status === 419) {
        event.preventDefault?.();
        redirectToLoginOnSessionExpired();
    }
});
document.addEventListener("inertia:exception", (event) => {
    if (event?.detail?.response?.status === 419) {
        event.preventDefault?.();
        redirectToLoginOnSessionExpired();
    }
});

const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });

const appName = import.meta.env.VITE_APP_NAME || "ColdKey";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => pages[`./Pages/${name}.vue`],
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: "#4F46E5",
    },
});
