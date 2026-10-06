import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";
import "./lang"; // load translations before the first render
import "./styles/global.css";

ReactDOM.createRoot(document.getElementById("root")).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
