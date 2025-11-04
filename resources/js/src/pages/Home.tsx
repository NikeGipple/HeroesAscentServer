import React from "react";
import { motion } from "framer-motion";



export default function Home() {
  return (
    <section
      className="position-relative vh-100 w-100 overflow-hidden text-white d-flex align-items-center justify-content-center bg-transparent"
    >
      {/* === Sfondo principale animato === */}
      <motion.div
        className="position-absolute top-0 start-0 w-100 h-100"
        style={{
          zIndex: 0,
          opacity: 0.8,
          backgroundImage: "url('/images/heroes-bg.jpg')",
          backgroundSize: "cover",
          backgroundPosition: "center",
        }}
        animate={{
          scale: [1, 1.05, 1],
          backgroundPositionX: ["50%", "55%", "50%"],
        }}
        transition={{ duration: 30, repeat: Infinity, ease: "easeInOut" }}
      ></motion.div>

      {/* === Glow radiale === */}
      <div
        className="position-absolute top-0 start-0 w-100 h-100"
        style={{
          zIndex: 10,
          background:
            "radial-gradient(circle at center, rgba(255,200,100,0.25) 0%, rgba(0,0,0,0.9) 80%)",
          pointerEvents: "none",
        }}
      ></div>

      {/* === Nebbia animata === */}
      <motion.div
        className="position-absolute top-0 start-0 w-100 h-100"
        style={{
          zIndex: 20,
          backgroundImage: "url('/images/fog-texture.png')",
          backgroundSize: "cover",
          mixBlendMode: "lighten",
          opacity: 0.25,
          pointerEvents: "none",
        }}
        animate={{
          backgroundPositionX: ["0%", "100%"],
          backgroundPositionY: ["0%", "100%"],
        }}
        transition={{ duration: 40, repeat: Infinity, ease: "linear" }}
      ></motion.div>

      {/* === Contenuto centrale === */}
      <motion.div
        className="position-relative text-center px-3"
        style={{ zIndex: 30 }}
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ duration: 1.5 }}
      >
        <motion.h1
          className="fw-bold mb-3 display-3"
          style={{
            letterSpacing: "0.2rem",
            textShadow: "0 0 30px rgba(255,200,100,0.8)",
          }}
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 2, ease: "easeOut" }}
        >
          HEROES ASCENT
        </motion.h1>

        <motion.p
          className="lead fst-italic text-light"
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 1, duration: 1.5 }}
        >
          L’ascesa comincia qui.
        </motion.p>
      </motion.div>

      {/* === Freccia in basso === */}
      <motion.div
        className="position-absolute bottom-0 mb-4 text-warning cursor-pointer"
        style={{ zIndex: 30 }}
        animate={{ y: [0, 10, 0] }}
        transition={{ duration: 2, repeat: Infinity }}
        onClick={() => (window.location.href = "/regolamento")}
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="24"
          height="24"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          strokeWidth="2"
        >
          <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
      </motion.div>
    </section>
  );
}
