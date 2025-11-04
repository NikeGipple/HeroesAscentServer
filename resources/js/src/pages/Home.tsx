import React from "react";
import { motion } from "framer-motion";

export default function Home() {
  return (
    <section className="relative min-h-screen flex flex-col justify-center items-center overflow-hidden text-white">
      {/* Sfondo immagine + overlay */}
      <motion.div
        className="absolute inset-0 bg-[url('/images/heroes-bg.jpg')] bg-cover bg-center opacity-40"
        animate={{ scale: [1, 1.05, 1] }}
        transition={{ duration: 18, repeat: Infinity, ease: "easeInOut" }}
      />
      <div className="absolute inset-0 bg-gradient-to-b from-slate-900/80 via-slate-900/70 to-black"></div>

      {/* Contenuto */}
      <motion.div
        className="z-10 text-center max-w-3xl px-6"
        initial={{ opacity: 0, y: 40 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 1 }}
      >
        <motion.h1
          className="text-5xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-yellow-300 to-orange-500 bg-clip-text text-transparent drop-shadow-lg"
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3, duration: 1 }}
        >
          Heroes Ascent – 2ª Edizione
        </motion.h1>

        <motion.p
          className="text-lg md:text-xl text-gray-300 mb-8 leading-relaxed"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ delay: 0.6, duration: 1 }}
        >
          Dal <strong>4 al 18 novembre 2024</strong> la community{" "}
          <strong>L’Arco del Leone</strong> ti sfida:
          <br />
          porta un nuovo eroe dal livello <strong>0</strong> al{" "}
          <strong>80</strong> senza aiuti, cavalcature o booster. <br />
          Solo la tua bravura, il tuo coraggio e un pizzico di follia 👑
        </motion.p>

        <motion.button
          className="px-8 py-4 bg-yellow-400 text-black font-semibold rounded-2xl text-lg shadow-lg hover:bg-yellow-500 hover:scale-105 transition"
          whileHover={{ scale: 1.1 }}
          whileTap={{ scale: 0.95 }}
          onClick={() => (window.location.href = "/regolamento")}
        >
          Leggi il regolamento
        </motion.button>
      </motion.div>

      {/* Footer */}
      <motion.div
        className="absolute bottom-6 text-sm text-gray-400 z-10"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ delay: 1.5 }}
      >
        Organizzato dalla community italiana{" "}
        <strong>L'Arco del Leone</strong> 🦁
      </motion.div>
    </section>
  );
}
