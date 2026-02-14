// resources/js/app.js

// Bootstrap (Laravel Mix/ Vite helpers)
import './bootstrap';

// AlpineJS
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// SweetAlert2
import Swal from 'sweetalert2';
window.Swal = Swal; // Optional: make Swal globally accessible
