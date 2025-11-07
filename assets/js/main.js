// Simple slider for homepage (no library)
document.addEventListener('DOMContentLoaded', function(){
  const containers = document.querySelectorAll('.slider');
  containers.forEach(container=>{
    const slides = container.querySelector('.slides');
    const total = slides.children.length;
    let idx = 0;
    const next = container.querySelector('.next');
    const prev = container.querySelector('.prev');
    function update(){ slides.style.transform = 'translateX(' + (-idx*100) + '%)'; }
    if(next) next.addEventListener('click', ()=>{ idx = (idx+1)%total; update(); });
    if(prev) prev.addEventListener('click', ()=>{ idx = (idx-1+total)%total; update(); });
    // auto-play
    setInterval(()=>{ idx = (idx+1)%total; update(); }, 6000);
  });
});