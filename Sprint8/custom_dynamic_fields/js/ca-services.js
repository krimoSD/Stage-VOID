(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.capitalAzurServices = {
    attach(context) {
      once('ca-services-carousel', '[data-autoplay]', context).forEach((wrapper) => {
        const track      = wrapper.querySelector('.js-ca-services-track');
        const slides     = Array.from(wrapper.querySelectorAll('.js-ca-services-slide'));
        const cards      = Array.from(wrapper.querySelectorAll('.js-ca-services-card'));
        const prevBtn    = wrapper.querySelector('.js-ca-services-prev');
        const nextBtn    = wrapper.querySelector('.js-ca-services-next');

        if (!track || !slides.length) return;

        const totalSlides  = slides.length;
        const visibleItems = parseInt(wrapper.dataset.visible || '4', 10);
        const autoplay     = wrapper.dataset.autoplay === '1';
        const autoplaySpeed = parseInt(wrapper.dataset.autoplaySpeed || '4000', 10);

        let currentIndex = 0;
        let timer        = null;

        // ── Active card styles ──────────────────────────────────────────
        const ACTIVE_CLASSES   = ['ring-2', 'ring-[#2b84e8]', 'shadow-[0_8px_30px_-6px_rgba(43,132,232,0.25)]', '-translate-y-1'];
        const INACTIVE_CLASSES = ['ring-2', 'ring-[#2b84e8]', 'shadow-[0_8px_30px_-6px_rgba(43,132,232,0.25)]', '-translate-y-1'];

        const updateActive = () => {
          cards.forEach((card, i) => {
            const isActive = i === currentIndex;
            // Remove active from all
            card.classList.remove('ring-2', 'ring-[#2b84e8]', 'shadow-[0_8px_30px_-6px_rgba(43,132,232,0.25)]', '-translate-y-1');
            if (isActive) {
              card.classList.add('ring-2', 'ring-[#2b84e8]', 'shadow-[0_8px_30px_-6px_rgba(43,132,232,0.25)]', '-translate-y-1');
            }
          });
        };

        const maxOffset = Math.max(0, totalSlides - visibleItems);

        const goTo = (index) => {
          currentIndex = Math.max(0, Math.min(index, totalSlides - 1));
          // Limit track movement so we never scroll past last visible set
          const trackOffset = Math.min(currentIndex, maxOffset);
          const slideWidthPct = 100 / visibleItems;
          track.style.transform = `translateX(-${trackOffset * slideWidthPct}%)`;
          updateActive();
        };

        const next = () => goTo(currentIndex + 1 > totalSlides - 1 ? 0 : currentIndex + 1);
        const prev = () => goTo(currentIndex - 1 < 0 ? totalSlides - 1 : currentIndex - 1);

        const stopAutoplay  = () => { if (timer) { clearInterval(timer); timer = null; } };
        const startAutoplay = () => {
          if (autoplay && totalSlides > visibleItems) {
            stopAutoplay();
            timer = setInterval(next, autoplaySpeed > 0 ? autoplaySpeed : 4000);
          }
        };

        if (nextBtn) {
          nextBtn.addEventListener('click', () => { next(); startAutoplay(); });
        }
        if (prevBtn) {
          prevBtn.addEventListener('click', () => { prev(); startAutoplay(); });
        }

        // Click on a card to activate it
        cards.forEach((card, i) => {
          card.addEventListener('click', (e) => {
            // Only intercept if no real href
            const href = card.getAttribute('href');
            if (!href || href === '#') {
              e.preventDefault();
            }
            goTo(i);
            startAutoplay();
          });
        });

        // Initialise
        goTo(0);
        startAutoplay();
      });
    },
  };
})(Drupal, once);
