// Drag-to-scroll para categorias no desktop
(function(){
  var el = document.querySelector('.myd-content-filter__categories');
  if (!el) return;
  var isDown = false, startX, scrollLeft;
  el.addEventListener('mousedown', function(e){
    if(window.innerWidth < 769) return; // só desktop
    isDown = true;
    el.classList.add('dragging');
    startX = e.pageX - el.offsetLeft;
    scrollLeft = el.scrollLeft;
    e.preventDefault();
  });
  document.addEventListener('mouseup', function(){
    isDown = false;
    el.classList.remove('dragging');
  });
  document.addEventListener('mousemove', function(e){
    if(!isDown) return;
    var x = e.pageX - el.offsetLeft;
    var walk = (x - startX);
    el.scrollLeft = scrollLeft - walk;
  });
})();
