import { defineConfig } from 'vite'
import path from 'path'
import fs from 'fs'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'

// Images live in ../../assets/images — served via dev middleware and copied on build
const imagesDir = path.resolve(process.cwd(), '../../assets/images')

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
    // Serve ../../assets/images at /images/ in dev
    {
      name: 'serve-local-images',
      configureServer(server) {
        server.middlewares.use('/images', (req, res, next) => {
          const filePath = path.join(imagesDir, req.url ?? '/')
          if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
            res.setHeader('Cache-Control', 'public, max-age=31536000, immutable')
            fs.createReadStream(filePath).pipe(res)
          } else {
            next()
          }
        })
      },
      // For prod build: copy images tree into dist/images/
      closeBundle() {
        if (!fs.existsSync(imagesDir)) return
        const distImages = path.resolve(process.cwd(), 'dist/images')
        const copyDir = (src: string, dest: string) => {
          fs.mkdirSync(dest, { recursive: true })
          for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
            const s = path.join(src, entry.name)
            const d = path.join(dest, entry.name)
            entry.isDirectory() ? copyDir(s, d) : fs.copyFileSync(s, d)
          }
        }
        copyDir(imagesDir, distImages)
      },
    },
  ],
  // public/ is served at / — fonts/Irancell/*.woff2 live in public/fonts/Irancell/
  resolve: {
    alias: { '@': path.resolve(process.cwd(), './src') },
  },
  build: {
    rollupOptions: {
      input: {
        main: path.resolve(process.cwd(), 'index.html'),
      },
    },
  },
})
