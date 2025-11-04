import React from "react";
import ReactDOM from "react-dom/client";
import { createBrowserRouter, RouterProvider } from "react-router-dom";
import { Provider } from "react-redux";

import App from "@/App";
import { store } from "@/redux/store";
import Home from "@/pages/Home";
import Error404 from "@/pages/Error404";
import "../css/app.css";
import "bootstrap/dist/css/bootstrap.min.css";

/**
 * Configurazione del router
 */
const router = createBrowserRouter([
  {
    path: "/",
    element: <App />,
    errorElement: <Error404 />,
    children: [
      { path: "/", element: <Home /> },
    ],
  },
]);

/**
 * Inizializza il root React
 */
const container = document.getElementById("app");
if (!container) {
  throw new Error("Elemento con id 'app' non trovato nel DOM.");
}

const root = ReactDOM.createRoot(container as HTMLElement);

/**
 * Monta l'applicazione React
 */
root.render(
  <React.StrictMode>
    <Provider store={store}>
      <RouterProvider router={router} />
    </Provider>
  </React.StrictMode>
);
