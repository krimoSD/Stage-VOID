(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.capitalAzurSlider = {
    attach(context) {
      once('capital-azur-slider', '.js-ca-slider', context).forEach((slider) => {
        const slides = Array.from(slider.querySelectorAll('.js-ca-slide'));
        if (!slides.length) {
          return;
        }

        const prevButton = slider.querySelector('.js-ca-slider-prev');
        const nextButton = slider.querySelector('.js-ca-slider-next');
        const dots = Array.from(slider.querySelectorAll('.js-ca-slider-dot'));

        const autoplay = slider.dataset.autoplay !== '0';
        const autoplaySpeed = parseInt(slider.dataset.autoplaySpeed || '5000', 10);
        let currentIndex = 0;
        let timer = null;

        const update = () => {
          slides.forEach((slide, index) => {
            const active = index === currentIndex;
            slide.classList.toggle('opacity-100', active);
            slide.classList.toggle('opacity-0', !active);
            slide.classList.toggle('pointer-events-auto', active);
            slide.classList.toggle('pointer-events-none', !active);
            slide.classList.toggle('z-10', active);
            slide.classList.toggle('z-0', !active);
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
          });

          dots.forEach((dot, index) => {
            const active = index === currentIndex;
            dot.classList.toggle('bg-white/90', active);
            dot.classList.toggle('bg-white/40', !active);
            dot.setAttribute('aria-current', active ? 'true' : 'false');
          });
        };

        const goTo = (targetIndex) => {
          if (targetIndex < 0) {
            currentIndex = slides.length - 1;
          } else if (targetIndex >= slides.length) {
            currentIndex = 0;
          } else {
            currentIndex = targetIndex;
          }
          update();
        };

        const next = () => goTo(currentIndex + 1);
        const prev = () => goTo(currentIndex - 1);

        const stopAutoplay = () => {
          if (timer) {
            window.clearInterval(timer);
            timer = null;
          }
        };

        const startAutoplay = () => {
          if (autoplay && slides.length > 1) {
            stopAutoplay();
            timer = window.setInterval(next, autoplaySpeed > 0 ? autoplaySpeed : 5000);
          }
        };

        if (nextButton) {
          nextButton.addEventListener('click', () => {
            next();
            startAutoplay();
          });
        }
        if (prevButton) {
          prevButton.addEventListener('click', () => {
            prev();
            startAutoplay();
          });
        }
        dots.forEach((dot, index) => {
          dot.addEventListener('click', () => {
            goTo(index);
            startAutoplay();
          });
        });

        if (autoplay && slides.length > 1) {
          startAutoplay();
        }

        update();
      });
    },
  };
})(Drupal, once);
