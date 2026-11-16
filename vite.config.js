import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    // Strip console/debugger statements from output (and from pre-bundled deps)
    // so browser console stays clean across modules.
    esbuild: {
        drop: ["console", "debugger"],
    },
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
    optimizeDeps: {
        esbuildOptions: {
            drop: ["console", "debugger"],
        },
    },
    // TAMBAHKAN BLOK BUILD INI UNTUK MENGATASI CHUNK BESAR
    build: {
        chunkSizeWarningLimit: 600, // Menaikkan limit warning jika diperlukan
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Memisahkan library dari node_modules agar tidak menumpuk di app.js
                    if (id.includes("node_modules")) {
                        if (id.includes("jspdf")) {
                            return "jspdf"; // Memisahkan jspdf ke file sendiri karena ukurannya besar
                        }
                        return "vendor"; // Library node_modules lainnya dijadikan satu di vendor.js
                    }
                },
            },
        },
    },
});
