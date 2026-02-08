import './bootstrap';

// Alpine.js para interatividade
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Funções auxiliares
window.confirmDelete = function(formId, message = 'Tem certeza que deseja deletar?') {
    if (confirm(message)) {
        document.getElementById(formId).submit();
    }
};

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.auto-dismiss');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// Toggle sidebar em mobile
window.toggleSidebar = function() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('-translate-x-full');
};

// Formatação de notas
window.formatNota = function(input) {
    let value = input.value.replace(',', '.');
    value = parseFloat(value);
    
    if (isNaN(value) || value < 0) {
        input.value = '';
    } else if (value > 20) {
        input.value = '20.00';
    } else {
        input.value = value.toFixed(2);
    }
};

// Preview de imagem antes do upload
window.previewImage = function(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-image').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
};

// Confirmação de ações
window.confirmAction = function(message, callback) {
    if (confirm(message)) {
        callback();
    }
};

// Print
window.printContent = function() {
    window.print();
};

console.log('Sistema de Notas Escolares carregado! 🎓');