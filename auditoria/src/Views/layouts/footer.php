</main>

<footer class="footer bg-light text-center py-3 mt-auto">
    <div class="container">
        <p class="text-muted small mb-0">App Auditoría v1.0 | &copy; <?php echo date('Y'); ?> - XL Extra Large. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>

<?php
if (isset($pageScripts) && !empty($pageScripts)) {
    echo $pageScripts;
}
?>

</body>
</html>