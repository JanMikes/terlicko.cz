import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.min.css';

// Twitter Bootstrap JS
import 'bootstrap';

import './styles/app.css';

import './bootstrap.js';

import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.css';

document.addEventListener('DOMContentLoaded', () => {
  const lightbox = GLightbox({ selector: '.glightbox' });
});
