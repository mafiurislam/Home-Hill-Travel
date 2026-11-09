// Continuous Multi-Image Auto-Slider for Tour Package Cards
(function () {
    const cardStates = new Map();

    window.initCardSliders = function () {
        const sliders = document.querySelectorAll('[data-card-slider]');
        sliders.forEach((slider, idx) => {
            const cardId = slider.getAttribute('data-card-slider');
            if (!cardId || cardStates.has(cardId)) return;

            const total = parseInt(slider.getAttribute('data-total-slides') || '1', 10);
            if (total <= 1) return;

            cardStates.set(cardId, {
                current: 0,
                total: total,
                el: slider,
                isPaused: false,
                offset: (idx % 3) * 600 // Slight offset so cards don't all flip on exact same millisecond
            });

            // Pause on mouse hover (desktop)
            slider.addEventListener('mouseenter', () => {
                const st = cardStates.get(cardId);
                if (st) st.isPaused = true;
            });
            slider.addEventListener('mouseleave', () => {
                const st = cardStates.get(cardId);
                if (st) st.isPaused = false;
            });

            // Pause on touch (mobile)
            slider.addEventListener('touchstart', () => {
                const st = cardStates.get(cardId);
                if (st) st.isPaused = true;
            }, { passive: true });
            slider.addEventListener('touchend', () => {
                setTimeout(() => {
                    const st = cardStates.get(cardId);
                    if (st) st.isPaused = false;
                }, 2500);
            }, { passive: true });
        });
    };

    window.stepCardSlider = function (cardId, dir) {
        const st = cardStates.get(cardId);
        if (!st || st.total <= 1) return;
        const nextIndex = (st.current + dir + st.total) % st.total;
        window.setCardSlide(cardId, nextIndex);
    };

    window.setCardSlide = function (cardId, nextIndex) {
        const st = cardStates.get(cardId);
        if (!st) return;

        const slides = st.el.querySelectorAll('.slide-item');
        const dots = st.el.querySelectorAll('.slide-dot');

        slides.forEach((s, i) => {
            if (i === nextIndex) {
                s.classList.remove('opacity-0', 'z-0');
                s.classList.add('opacity-100', 'z-10');
            } else {
                s.classList.remove('opacity-100', 'z-10');
                s.classList.add('opacity-0', 'z-0');
            }
        });

        dots.forEach((d, i) => {
            if (i === nextIndex) {
                d.classList.remove('inactive', 'w-1.5', 'bg-white/60');
                d.classList.add('active', 'w-5', 'bg-white');
            } else {
                d.classList.remove('active', 'w-5', 'bg-white');
                d.classList.add('inactive', 'w-1.5', 'bg-white/60');
            }
        });

        st.current = nextIndex;
    };

    // Auto-sliding loop every 3.8 seconds
    setInterval(() => {
        cardStates.forEach((st, cardId) => {
            if (!st.isPaused && st.total > 1) {
                const next = (st.current + 1) % st.total;
                window.setCardSlide(cardId, next);
            }
        });
    }, 3800);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initCardSliders);
    } else {
        window.initCardSliders();
    }
})();
