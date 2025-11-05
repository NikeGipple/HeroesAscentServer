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
          backgroundColor: "black",
          backgroundImage: "url('/images/heroes-bg.jpg')",
          backgroundRepeat: "no-repeat",
          backgroundPosition: "center center",
          backgroundSize: "contain", // l’immagine resta intera
          filter: "contrast(1.1) saturate(1.1) brightness(1)",
          transformOrigin: "center center",
        }}
        animate={{
          scale: [1, 1.04, 1], // piccolo respiro avanti e indietro
          filter: [
            "contrast(1.1) saturate(1.1) brightness(1)",
            "contrast(1.2) saturate(1.3) brightness(1.08)",
            "contrast(1.1) saturate(1.1) brightness(1)",
          ],
        }}
        transition={{
          duration: 40,
          repeat: Infinity,
          ease: "easeInOut",
        }}
      ></motion.div>



    <motion.div
      className="position-absolute top-0 start-0 w-100 h-100"
      style={{
        zIndex: 25,
        backgroundImage: "url('/images/particles.png')",
        backgroundRepeat: "repeat",
        backgroundSize: "100px 100px", // dimensione tile
        mixBlendMode: "color-dodge",
        opacity: 0.10,
        pointerEvents: "none",
      }}
      animate={{
        backgroundPositionX: ["0%", "30%", "60%", "30%", "0%"],
        backgroundPositionY: ["0%", "20%", "10%", "30%", "0%"],
      }}
      transition={{
        duration: 90,        // ciclo molto lento e naturale
        repeat: Infinity,
        ease: "easeInOut",
      }}
    ></motion.div>



      {/* === Glow radiale === */}
      <div
        className="position-absolute top-0 start-0 w-100 h-100"
        style={{
          zIndex: 10,
          background:
            "radial-gradient(circle at center, rgba(255,255,255,0.08) 0%, rgba(0,0,0,0.7) 80%)",
          pointerEvents: "none",
        }}
      ></div>


      {/* === Nebbia animata (strato basso) === */}
      <motion.div
        className="position-absolute start-0 w-100"
        style={{
          bottom: 0,                // resta ancorata in basso
          height: "50%",            // solo la parte più bassa dello schermo
          zIndex: 20,
          backgroundImage: "url('/images/fog-texture.png')",
          backgroundRepeat: "repeat-x",
          backgroundSize: "auto 100%",
          mixBlendMode: "lighten",
          opacity: 0.15,            // leggermente più visibile ma non invadente
          pointerEvents: "none",
        }}
        animate={{
          backgroundPositionX: ["0%", "100%"], // movimento orizzontale continuo
        }}
        transition={{
          duration: 80,             // lento e costante
          repeat: Infinity,
          ease: "linear",
        }}
      ></motion.div>


      {/* Strato 2 — nebbia sullo sfondo, più leggera e lenta */}
      <motion.div
        className="position-absolute start-0 w-100"
        style={{
          bottom: 0,
          height: "50%",
          zIndex: 20,
          backgroundImage: "url('/images/fog-texture.png')",
          backgroundRepeat: "repeat-x",
          backgroundSize: "auto 100%",
          mixBlendMode: "lighten",
          opacity: 0.1, // più tenue
          pointerEvents: "none",
        }}
        animate={{
          backgroundPositionX: ["100%", "0%"], // direzione opposta
        }}
        transition={{
          duration: 120, // più lento → senso di profondità
          repeat: Infinity,
          ease: "linear",
        }}
      ></motion.div>

      
      {/* === Contenuto centrale === */}
      <motion.div
        className="position-relative text-center px-3"
        style={{
          zIndex: 30,
          transform: "translateY(-30%)",
        }}
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ duration: 1.5 }}
      >
        <motion.h1
          className="fw-bold mb-3 display-3"
          style={{
            fontFamily: "'Cinzel', serif",
            fontWeight: 700,
            letterSpacing: "0.2rem",
            textShadow: "0 0 30px rgba(255,200,100,0.8)",
            background: "linear-gradient(90deg, #ffd27a 0%, #fff2cc 100%)",
            WebkitBackgroundClip: "text",
            WebkitTextFillColor: "transparent",
            userSelect: "none", // 👈 impedisce la selezione
          }}
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 2, ease: "easeOut" }}
        >
          HEROES ASCENT
        </motion.h1>

        <motion.p
          style={{
            fontFamily: "'Cinzel', serif",
            fontSize: "1.25rem",
            fontWeight: 400,
            color: "rgba(255,255,255,0.85)",
            textShadow: "0 0 15px rgba(255,200,100,0.4)",
            userSelect: "none", // 👈 anche qui
          }}
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 1, duration: 1.5 }}
        >
          La tua ascesa comincia qui
        </motion.p>
      </motion.div>

      {/* === Freccia in basso === */}
      <motion.div
        className="position-absolute bottom-0 mb-4 text-warning cursor-pointer"
        style={{ zIndex: 30 }}
        animate={{ y: [0, 10, 0] }}
        transition={{ duration: 2, repeat: Infinity }}
        // onClick={() => (window.location.href = "/regolamento")}
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
