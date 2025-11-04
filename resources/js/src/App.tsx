import React from "react";
import { Outlet, Link } from "react-router-dom";

export default function App() {
  return (
    <div style={{ textAlign: "center", padding: "2rem" }}>
      <h1>Heroes Ascent</h1>
      <nav style={{ marginBottom: "1rem" }}>
        <Link to="/">Home</Link>
      </nav>
      {/* Dove verranno renderizzate le sottopagine */}
      <Outlet />
    </div>
  );
}
