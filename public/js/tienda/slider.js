document.addEventListener('DOMContentLoaded', () => {
    
    // MÓDULO AISLADO DEL SLIDER
    let slideIndex = 1;
    const slides = document.querySelectorAll(".slide");
    const dots = document.querySelectorAll(".slider-dot");
    const btnPrev = document.getElementById("btnPrevSlide");
    const btnNext = document.getElementById("btnNextSlide");

    if (slides.length > 0) {
        if (btnPrev) btnPrev.addEventListener('click', () => plusSlides(-1));
        if (btnNext) btnNext.addEventListener('click', () => plusSlides(1));

        dots.forEach(dot => {
            dot.addEventListener('click', (e) => {
                const targetSlide = parseInt(e.target.getAttribute('data-slide'));
                currentSlide(targetSlide);
            });
        });

        // Autoplay de banners cada 5 segundos
        setInterval(() => { plusSlides(1); }, 5000);
    }

    function plusSlides(n) { showSlides(slideIndex += n); }
    function currentSlide(n) { showSlides(slideIndex = n); }

    function showSlides(n) {
        if (n > slides.length) { slideIndex = 1; }    
        if (n < 1) { slideIndex = slides.length; }
        
        slides.forEach(slide => slide.classList.remove("slide-active"));  
        dots.forEach(dot => dot.classList.remove("dot-active"));
        
        if (slides[slideIndex - 1]) slides[slideIndex - 1].classList.add("slide-active");  
        if (dots[slideIndex - 1]) dots[slideIndex - 1].classList.add("dot-active");
    }
});