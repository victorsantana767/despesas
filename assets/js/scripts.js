document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
        });
    }

    // Mantém o submenu ativo aberto ao carregar a página
    // A lógica de adicionar a classe 'show' já está no PHP,
    // mas este JS pode ser útil para manipulações dinâmicas futuras.
    // Por enquanto, vamos garantir que os links ativos nos submenus
    // também marquem o link pai como ativo visualmente.
    const activeSubLink = document.querySelector('.sub-link.active');
    if (activeSubLink) {
        const parentCollapse = activeSubLink.closest('.collapse');
        const parentLink = document.querySelector(`a[href="#${parentCollapse.id}"]`);
        parentLink.classList.add('active');
    }
});