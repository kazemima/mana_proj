document.addEventListener('DOMContentLoaded', function() {

    // ========== Theme Toggle (Dark/Light Mode) ==========
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

    function setTheme(theme) {
        html.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    // Load saved theme or default to light
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const current = html.getAttribute('data-theme');
            setTheme(current === 'dark' ? 'light' : 'dark');
        });
    }

    // ========== Hero Slider ==========
    const slides = document.querySelectorAll('.hero-slider .slide');
    const dots = document.querySelectorAll('.hero-slider .dot');
    const prevBtn = document.querySelector('.slider-prev');
    const nextBtn = document.querySelector('.slider-next');
    let currentSlide = 0;
    let slideInterval;

    function showSlide(index) {
        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));
        currentSlide = (index + slides.length) % slides.length;
        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
    }

    function nextSlide() { showSlide(currentSlide + 1); }
    function prevSlide() { showSlide(currentSlide - 1); }

    if (slides.length > 1) {
        slideInterval = setInterval(nextSlide, 5000);
        if (nextBtn) nextBtn.addEventListener('click', () => { clearInterval(slideInterval); nextSlide(); slideInterval = setInterval(nextSlide, 5000); });
        if (prevBtn) prevBtn.addEventListener('click', () => { clearInterval(slideInterval); prevSlide(); slideInterval = setInterval(nextSlide, 5000); });
        dots.forEach(dot => {
            dot.addEventListener('click', () => { clearInterval(slideInterval); showSlide(parseInt(dot.dataset.index)); slideInterval = setInterval(nextSlide, 5000); });
        });
    }

    // ========== Testimonials Slider ==========
    const track = document.getElementById('testimonialsTrack');
    const testPrev = document.querySelector('.testimonial-prev');
    const testNext = document.querySelector('.testimonial-next');
    if (track) {
        const cards = track.querySelectorAll('.testimonial-card');
        let currentTest = 0;

        function showTestimonial(index) {
            currentTest = (index + cards.length) % cards.length;
            track.style.transform = `translateX(${currentTest * 100}%)`;
        }

        if (testNext) testNext.addEventListener('click', () => showTestimonial(currentTest + 1));
        if (testPrev) testPrev.addEventListener('click', () => showTestimonial(currentTest - 1));
        setInterval(() => showTestimonial(currentTest + 1), 6000);
    }

    // ========== Search Overlay ==========
    const searchToggle = document.querySelector('.search-toggle');
    const searchOverlay = document.getElementById('searchOverlay');
    const searchClose = document.querySelector('.search-close');

    if (searchToggle && searchOverlay) {
        searchToggle.addEventListener('click', () => { searchOverlay.classList.add('active'); searchOverlay.querySelector('input').focus(); });
        if (searchClose) searchClose.addEventListener('click', () => searchOverlay.classList.remove('active'));
        searchOverlay.addEventListener('click', (e) => { if (e.target === searchOverlay) searchOverlay.classList.remove('active'); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') searchOverlay.classList.remove('active'); });
    }

    // ========== Mobile Menu ==========
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileOverlay = document.getElementById('mobileMenuOverlay');
    const mobileClose = document.querySelector('.mobile-menu-close');

    function openMobileMenu() { mobileMenu.classList.add('active'); mobileOverlay.classList.add('active'); document.body.style.overflow = 'hidden'; }
    function closeMobileMenu() { mobileMenu.classList.remove('active'); mobileOverlay.classList.remove('active'); document.body.style.overflow = ''; }

    if (mobileToggle) mobileToggle.addEventListener('click', openMobileMenu);
    if (mobileClose) mobileClose.addEventListener('click', closeMobileMenu);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileMenu);

    // Mobile submenu toggle
    document.querySelectorAll('.mobile-nav .has-submenu > a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            this.parentElement.classList.toggle('open');
        });
    });

    // ========== Back to Top ==========
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', () => {
            backToTop.classList.toggle('visible', window.scrollY > 300);
        });
    }

    // ========== Counter Animation ==========
    const counters = document.querySelectorAll('.counter-value');
    let counterAnimated = false;

    function animateCounters() {
        counters.forEach(counter => {
            const target = parseInt(counter.dataset.target);
            const duration = 2000;
            const start = 0;
            const startTime = performance.now();

            function updateCounter(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOut = 1 - Math.pow(1 - progress, 3);
                counter.textContent = Math.floor(start + (target - start) * easeOut);
                if (progress < 1) requestAnimationFrame(updateCounter);
            }
            requestAnimationFrame(updateCounter);
        });
    }

    if (counters.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !counterAnimated) {
                    counterAnimated = true;
                    animateCounters();
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(c => observer.observe(c));
    }

    // ========== Sticky Header Shadow ==========
    const header = document.getElementById('header');
    if (header) {
        window.addEventListener('scroll', () => {
            header.style.boxShadow = window.scrollY > 10 ? '0 2px 20px rgba(0,0,0,0.12)' : '0 2px 20px rgba(0,0,0,0.08)';
        });
    }

    // ========== Service Card Hover Effect ==========
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
});
