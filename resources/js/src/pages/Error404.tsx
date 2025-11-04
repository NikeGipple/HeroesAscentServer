import React from "react";
import { Link } from "react-router-dom";

export default function Error404() {
  return (
    <div style={{ textAlign: "center", padding: "2rem" }}>
      <h2>404 - Pagina non trovata 😢</h2>
      <Link to="/">Torna alla home</Link>
    </div>
  );
}
