import React from "react";
import { Outlet, Link } from "react-router-dom";

export default function App() {
  return (
    <div style={{ textAlign: "center", padding: "2rem" }}>
      {/* Dove verranno renderizzate le sottopagine */}
      <Outlet />
    </div>
  );
}
