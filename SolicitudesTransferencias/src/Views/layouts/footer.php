</main>

<footer class="app-footer">
    <p>Solicitudes de Transferencias v1.0 | &copy; <?php echo date('Y'); ?> - XL Extra Large. Todos los derechos reservados.</p>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>

<script>
// Toast notifications
function showToast(message, type = 'success', sticky = false) {
    const container = document.getElementById('toast-container');
    const icons = {
        success: '<i class="fas fa-check-circle" style="color:var(--success);"></i>',
        error: '<i class="fas fa-exclamation-circle" style="color:var(--danger);"></i>',
        warning: '<i class="fas fa-exclamation-triangle" style="color:var(--warning);"></i>'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast-modern toast-${type}`;
    // Si es sticky, le agregamos un borde más grueso para que se note
    if (sticky) toast.style.borderLeftWidth = '6px';

    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.success}</span>
        <span class="toast-message">${message}</span>
        <span class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></span>
    `;
    container.appendChild(toast);
    
    // Solo auto-eliminar si NO es sticky
    if (!sticky) {
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(40px)';
                toast.style.transition = 'all .3s ease';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }
}
</script>

<?php
if (isset($pageScripts) && !empty($pageScripts)) {
    echo $pageScripts;
}
?>

</body>
</html>
