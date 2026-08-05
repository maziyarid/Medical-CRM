import { defineConfig } from 'vite'
import path from 'path'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'

/**
 * Images now live in public/images/ — Vite copies them to dist/ automatically on build.
 * No custom middleware needed; dev server and preview both serve public/ at root.
 */
export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],
  // base: './' makes all asset paths relative — required for cPanel public_html deployment
  // where the site may be served from a subdirectory.
  // Change to '/' if deploying to the root of a dedicated domain.
  base: './',
  // public/ is served at / — fonts/Irancell/*.woff2 and images/... live in public/
  resolve: {
    alias: { '@': path.resolve(process.cwd(), './src') },
  },
  build: {
    // Inline small assets as base64 (< 4 KB)
    assetsInlineLimit: 4096,
    // Source maps for production debugging (omit in final release by setting false)
    sourcemap: false,
    rollupOptions: {
      input: {
        main: path.resolve(process.cwd(), 'index.html'),
      },
      output: {
        // Manual chunk splitting for optimal code splitting
        manualChunks(id) {
          // Vendor: heavy libraries get their own chunk
          if (id.includes('node_modules')) {
            if (id.includes('react-router')) return 'vendor-router'
            if (id.includes('@radix-ui'))    return 'vendor-radix'
            if (id.includes('motion'))       return 'vendor-motion'
            if (id.includes('dotlottie'))    return 'vendor-lottie'
            if (id.includes('lucide-react')) return 'vendor-lucide'
            if (id.includes('react') || id.includes('react-dom')) return 'vendor-react'
            return 'vendor'
          }
          // Feature chunks by route family
          if (id.includes('/pages/blog/'))     return 'chunk-blog'
          if (id.includes('/pages/services/')) return 'chunk-services'
          if (id.includes('/pages/legal/'))    return 'chunk-legal'
        },
      },
    },
  },
})
