<script>
    (function () {
        var theme = null;
        try {
            theme = localStorage.getItem('theme');
        } catch (e) {}
        document.documentElement.classList.toggle('dark', theme === 'dark' || (theme === null && @js($defaultDark ?? false)));
    })();
</script>
