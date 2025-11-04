import React from "react";
import { motion } from "framer-motion";

export default function Home() {
  return (
    <section className="relative h-screen w-full overflow-hidden bg-black text-white flex items-center justify-center">
      {/* === Sfondo (video o immagine) === */}
      <div className="absolute inset-0 -z-10">
        <video
          src="/videos/heroes-bg.jpg"
          autoPlay
          loop
          muted
          playsInline
          className="w-full h-full object-cover opacity-60"
        />
      </div>

      {/* === Glow radiale === */}
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(255,200,100,0.25)_0%,rgba(0,0,0,0.9)_80%)] pointer-events-none" />

      {/* === Nebbia animata === */}
      <motion.div
        className="absolute inset-0 bg-[url('/images/fog-texture.png')] bg-cover opacity-25 mix-blend-lighten pointer-events-none"
        animate={{
          backgroundPositionX: ["0%", "100%"],
          backgroundPositionY: ["0%", "100%"],
        }}
        transition={{ duration: 40, repeat: Infinity, ease: "linear" }}
      />

      {/* === Contenitore centrale (vero centro verticale e orizzontale) === */}
      <motion.div
        className="relative flex flex-col items-center justify-center text-center"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ duration: 1.5 }}
      >
        <motion.h1
          className="text-6xl md:text-7xl font-extrabold tracking-widest mb-3 drop-shadow-[0_0_30px_rgba(255,200,100,0.8)]"
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 2, ease: 'easeOut' }}
        >
          HEROES ASCENT
        </motion.h1>

        <motion.p
          className="text-lg md:text-xl text-gray-300 italic tracking-wide"
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 1, duration: 1.5 }}
        >
          L’ascesa comincia qui.
        </motion.p>
      </motion.div>

      {/* === Freccia in basso === */}
      <motion.div
        className="absolute bottom-10 text-yellow-400 cursor-pointer"
        animate={{ y: [0, 10, 0] }}
        transition={{ duration: 2, repeat: Infinity }}
        onClick={() => (window.location.href = '/regolamento')}
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          className="w-8 h-8"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          strokeWidth={2}
        >
          <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
      </motion.div>
    </section>
  );
}
