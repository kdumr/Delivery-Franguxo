// Scrollbar overlay JS para mostrar a barra apenas durante interação
// Adicione a classe 'scroll-overlay' ao elemento desejado
(function(){
  var scrollContainers = document.querySelectorAll('.scroll-overlay');
  scrollContainers.forEach(function(container){
    var timeout;
    function showScrollbar() {
      container.classList.add('scrollbar-visible');
      clearTimeout(timeout);
      timeout = setTimeout(function(){
        container.classList.remove('scrollbar-visible');
      }, 1200);
    }
    function hideScrollbar() {
      container.classList.remove('scrollbar-visible');
    }
    container.addEventListener('mouseenter', showScrollbar);
    container.addEventListener('focusin', showScrollbar);
    container.addEventListener('scroll', showScrollbar);
    container.addEventListener('mouseleave', hideScrollbar);
    container.addEventListener('focusout', hideScrollbar);
  });
})();
