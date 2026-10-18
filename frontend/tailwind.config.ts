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
        tchad: {
          blue: "#002664",
          yellow: "#FECB00",
          red: "#C60C30",
          sky: "#0085CA",
        },
      },
    },
  },
  plugins: [],
};
export default config;
