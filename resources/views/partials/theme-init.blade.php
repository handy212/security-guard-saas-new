<script>
    (function () {
        var theme = localStorage.getItem('theme');
        if (theme === 'dark' || (theme === null && @js($defaultDark ?? false))) {
            document.documentElement.classList.add('dark');
        } else if (theme === 'light') {
            document.documentElement.classList.remove('dark');
        }
    })();
</script>
