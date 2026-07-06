export function initTheme() {
    const root = document.documentElement;
    const stored = localStorage.getItem('theme');

    if (stored === 'dark') {
        root.classList.add('dark');
    } else if (stored === 'light') {
        root.classList.remove('dark');
    }
}

export function setTheme(mode) {
    const root = document.documentElement;
    const isDark = mode === 'dark';

    root.classList.toggle('dark', isDark);
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark: isDark } }));
}

export function isDarkTheme() {
    return document.documentElement.classList.contains('dark');
}

document.addEventListener('livewire:navigated', initTheme);
