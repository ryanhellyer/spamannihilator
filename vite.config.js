import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { copyFileSync, cpSync, mkdirSync } from 'fs';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
            name: 'copy-old-css',
            closeBundle() {
                const source = resolve(__dirname, 'resources/css/old.css');
                const dest = resolve(__dirname, 'public/css/old.css');
                // Ensure the css directory exists
                const destDir = resolve(__dirname, 'public/css');
                try {
                    mkdirSync(destDir, { recursive: true });
                } catch (e) {
                    // Directory might already exist, ignore error
                }
                copyFileSync(source, dest);
                console.log('Copied resources/css/old.css to public/css/old.css');
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
