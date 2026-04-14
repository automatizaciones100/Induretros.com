import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./src/pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/components/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/app/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    extend: {
      colors: {
        // Paleta exacta del sitio WordPress original
        primary: "#f08215",      // Naranja - botones, destacados, links
        secondary: "#fecc00",    // Amarillo - hover de botones, acentos
        dark: "#1A202C",         // Texto principal
        "dark-2": "#2D3748",     // Títulos
        "gray-mid": "#4A5568",   // Texto secundario
        "gray-light": "#718096", // Texto muted
        "bg-light": "#EDF2F7",   // Fondo secciones
        "bg-soft": "#F7FAFC",    // Fondo general del body
        green: "#13612e",
        blue: "#1159af",
        red: "#b82105",
      },
      fontFamily: {
        // Fuentes exactas del sitio original
        sans: ["Poppins", "sans-serif"],    // Cuerpo de texto
        heading: ["Oswald", "sans-serif"],  // Títulos y headings
      },
      container: {
        center: true,
        padding: {
          DEFAULT: "1rem",
          sm: "1.5rem",
          lg: "2rem",
        },
      },
    },
  },
  plugins: [],
};

export default config;
