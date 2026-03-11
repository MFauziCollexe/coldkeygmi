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
});
