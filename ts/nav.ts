document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.getElementById('nav-toggle');
    const navPanel = document.getElementById('nav-panel');

    if (navToggle && navPanel) {
        navToggle.addEventListener('click', () => {
            const isOpen = navPanel.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (sidebarToggle && sidebar && overlay) {
        const closeSidebar = () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        };
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        });
        overlay.addEventListener('click', closeSidebar);
    }
});
