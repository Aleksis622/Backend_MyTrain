import Echo from "laravel-echo";
import Pusher from "pusher-js";

// Echo's "reverb" broadcaster speaks the Pusher protocol and needs pusher-js on window.
window.Pusher = Pusher;

/**
 * WebSocket connection to Laravel Reverb (backend: php artisan reverb:start).
 */
export function createEcho() {
  const port = Number(import.meta.env.VITE_REVERB_PORT || 8080);

  return new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST || "localhost",
    wsPort: port,
    wssPort: port,
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === "https",
    enabledTransports: ["ws", "wss"],
  });
}
