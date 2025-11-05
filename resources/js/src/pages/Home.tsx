import React, { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";

import { useNavigate } from "react-router-dom";



export default function Home() {

  const navigate = useNavigate();
  const [showRules, setShowRules] = useState(false);

  return (
    <>
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
          backgroundSize: "contain", 
          filter: "contrast(1.1) saturate(1.1) brightness(1)",
          transformOrigin: "center center",
        }}
        animate={{
          scale: [1, 1.04, 1],
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
        backgroundSize: "100px 100px",
        mixBlendMode: "color-dodge",
        opacity: 0.10,
        pointerEvents: "none",
      }}
      animate={{
        backgroundPositionX: ["0%", "30%", "60%", "30%", "0%"],
        backgroundPositionY: ["0%", "20%", "10%", "30%", "0%"],
      }}
      transition={{
        duration: 90,
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
          bottom: 0,
          height: "50%",
          zIndex: 20,
          backgroundImage: "url('/images/fog-texture.png')",
          backgroundRepeat: "repeat-x",
          backgroundSize: "auto 100%",
          mixBlendMode: "lighten",
          opacity: 0.15,            
          pointerEvents: "none",
        }}
        animate={{
          backgroundPositionX: ["0%", "100%"], 
        }}
        transition={{
          duration: 80,
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
          opacity: 0.1, 
          pointerEvents: "none",
        }}
        animate={{
          backgroundPositionX: ["100%", "0%"], 
        }}
        transition={{
          duration: 120,
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
            userSelect: "none", 
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
            userSelect: "none",
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
        onClick={() => setShowRules(true)} // 👈 apre la modale
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
          <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
      </motion.div>

    </section>

    {/* === Modale Regole === */}
      <AnimatePresence>
        {showRules && (
          <motion.div
            className="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center"
            style={{
              backgroundColor: "rgba(0, 0, 0, 0.75)",
              backdropFilter: "blur(8px)",
              zIndex: 999,
            }}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.4 }}
            onClick={() => setShowRules(false)}
          >
            <motion.div
              onClick={(e) => e.stopPropagation()}
              className="p-4 text-light rounded-4 shadow-lg"
              style={{
                width: "80%",
                maxWidth: "800px",
                maxHeight: "80vh",
                overflowY: "auto",
                background: "rgba(0,0,0,0.7)",
                border: "1px solid rgba(255,255,255,0.2)",
              }}
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              transition={{ duration: 0.3 }}
            >
              <div className="d-flex justify-content-between align-items-center mb-3">
                <h2
                  className="fw-bold text-warning mb-0"
                  style={{ fontFamily: "'Cinzel', serif" }}
                >
                  HEROES ASCENT — RULES
                </h2>
                <button
                  className="btn btn-outline-warning btn-sm"
                  onClick={() => setShowRules(false)}
                >
                  ✕
                </button>
              </div>

              <p>
                Welcome to the <b>2nd Edition of Heroes Ascent – Guild Wars 2 Contest!</b>
                Level from 0 to 80 following these rules:
              </p>

              <ul>
                <li>Use a <b>new account</b> under 2500 AP.</li>
                <li>Only open world maps, no cities or story.</li>
                <li><b>No boosters</b>, food, or consumables.</li>
                <li>No healing skill (slot 6).</li>
                <li>If you go downed — delete the character.</li>
              </ul>

              <p className="mt-3">
                ⏱ Event from <b>Nov 4 → Nov 18, 2024</b>
                <br />🎁 30 gold participation reward — plus 700 gold in extra prizes!
              </p>

              <div className="text-center mt-4">
                <a
                  href="https://tinyurl.com/heroesascent"
                  className="btn btn-warning"
                  target="_blank"
                  rel="noreferrer"
                >
                  View Complete Rules
                </a>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
    );
}