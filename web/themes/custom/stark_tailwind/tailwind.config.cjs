/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.{twig,html}',
    './**/*.twig',
    './**/*.html',
    './**/*.php',
    '../../../modules/custom/**/*.{twig,php,html}',
    '../../../themes/custom/**/*.{twig,php,html}',
    '../../../core/**/*.twig'
  ],
  theme: {
    extend: {}
  },
  plugins: []
};
