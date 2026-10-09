import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'


export default defineConfig({
  // react(): compiles JSX (without it: "React is not defined").
  // tailwindcss(): builds the admin panel's Tailwind CSS (src/styles/admin.css).
  plugins: [react(), tailwindcss()],
  // The backend only accepts login cookies and CORS from http://localhost:5173
  // (SANCTUM_STATEFUL_DOMAINS / FRONTEND_URL), so always use exactly that address.
  server: {
    host: 'localhost',
    port: 5173,
    strictPort: true,
  },
})
