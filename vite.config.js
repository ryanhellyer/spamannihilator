import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { copyFileSync, cpSync, mkdirSync } from 'fs';
import { resolve } from 'path';

export default defineConfig({
    build: {
        ssr: false,
        rollupOptions: {
            input: ['resources/css/style.css'],
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/style.css'],
            refresh: true,
        }),
        tailwindcss(),
        {
            name: 'copy-style-css',
            closeBundle() {
                const source = resolve(__dirname, 'resources/css/style.css');
                const dest = resolve(__dirname, 'public/style.css');
                copyFileSync(source, dest);
                console.log('Copied resources/css/style.css to public/style.css');
            },
        },
        {
            name: 'copy-images',
            closeBundle() {
                const source = resolve(__dirname, 'resources/images');
                const dest = resolve(__dirname, 'public/images');
                cpSync(source, dest, { recursive: true });
                console.log('Copied resources/images/ to public/images/');
            },
        },
    ],
});
