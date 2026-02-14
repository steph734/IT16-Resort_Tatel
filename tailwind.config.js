import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',   // Laravel Blade templates
    './resources/js/**/*.js',             // JavaScript files
  ],
  theme: {
    extend: {
      colors: {
        resort: {
          primary: '#284B53',      // Dark blue-green
          accent: '#5EC2D0',       // Teal/cyan
          beige: '#EBC595',        // Warm beige
          background: '#DCEBED',   // Light blue-gray
          'gray-text': '#7C8386',  // Light gray text
          'gray-dark': '#676B6D',  // Darker gray text
        },
      },
      fontFamily: {
        'crimson-pro': ['Crimson Pro', 'serif'],
        'crimson-text': ['Crimson Text', 'serif'],
        'poppins': ['Poppins', 'sans-serif'],
      },
      borderRadius: {
        resort: '20px',
        'resort-sm': '10px',
      },
    },
  },
  plugins: [forms],
};
