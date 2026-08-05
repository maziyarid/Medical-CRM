import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";
// SCSS compiled by Vite's native Sass processor (must be .scss import, not @import inside .css)
import "./styles/scss/main.scss";
import "./styles/index.css";

ReactDOM.createRoot(document.getElementById("root")!).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
