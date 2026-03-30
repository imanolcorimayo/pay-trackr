/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.php",
    "./router.php",
    "./includes/**/*.php",
    "./pages/**/*.php",
    "./assets/js/**/*.js",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      },
      colors: {
        dark:    '#292524',
        accent:  '#D97706',
        light:   '#FFFBF5',
        muted:   '#8C857D',
        border:  '#E8E2DA',
        danger:  '#B91C1C',
        success: '#15803D',
      },
    },
  },
  plugins: [],
}
