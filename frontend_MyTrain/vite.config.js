import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  // The backend only accepts login cookies and CORS from http://localhost:5173
  // (SANCTUM_STATEFUL_DOMAINS / FRONTEND_URL), so always use exactly that address.
  server: {
    host: 'localhost',
    port: 5173,
    strictPort: true,
  },
})
