<footer class="user-footer">

    <div>
        © <?php echo date('Y'); ?> User Portal
    </div>

    <div class="user-footer-status">

        <span class="user-status-dot"></span>

        System Operational

    </div>

</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const toggle = document.getElementById('userMenuToggle');
    const sidebar = document.getElementById('userSidebar');

    if (!toggle || !sidebar) {
        return;
    }

    toggle.addEventListener('click', function () {

        sidebar.classList.toggle('open');

    });

});
</script>