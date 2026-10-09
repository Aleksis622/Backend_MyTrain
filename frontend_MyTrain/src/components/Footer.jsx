import { useLocation } from "react-router-dom";

function Footer() {
  // Not shown in the admin panel, which has its own full-height layout.
  if (useLocation().pathname.startsWith("/admin")) return null;

  return (
    <footer className="footer">
      <p>MyTrain © 2026</p>
    </footer>
  );
}

export default Footer;
