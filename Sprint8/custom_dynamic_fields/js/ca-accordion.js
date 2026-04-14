(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.caAccordion = {
    attach: function (context) {
      once('ca-accordion', '.js-ca-accordion', context).forEach(function (accordion) {
        var items = accordion.querySelectorAll('.js-ca-accordion-item');

        items.forEach(function (item) {
          var trigger = item.querySelector('.js-ca-accordion-trigger');
          var body = item.querySelector('.js-ca-accordion-body');
          var iconPlus = item.querySelector('.js-ca-accordion-icon-plus');
          var iconMinus = item.querySelector('.js-ca-accordion-icon-minus');

          trigger.addEventListener('click', function () {
            var isOpen = item.getAttribute('data-open') === 'true';

            if (isOpen) {
              // Close this item
              item.setAttribute('data-open', 'false');
              trigger.setAttribute('aria-expanded', 'false');
              body.classList.add('hidden');
              iconPlus.classList.remove('hidden');
              iconMinus.classList.add('hidden');
            } else {
              // Close all other open items first
              items.forEach(function (otherItem) {
                if (otherItem !== item && otherItem.getAttribute('data-open') === 'true') {
                  var otherTrigger = otherItem.querySelector('.js-ca-accordion-trigger');
                  var otherBody = otherItem.querySelector('.js-ca-accordion-body');
                  var otherIconPlus = otherItem.querySelector('.js-ca-accordion-icon-plus');
                  var otherIconMinus = otherItem.querySelector('.js-ca-accordion-icon-minus');

                  otherItem.setAttribute('data-open', 'false');
                  otherTrigger.setAttribute('aria-expanded', 'false');
                  otherBody.classList.add('hidden');
                  otherIconPlus.classList.remove('hidden');
                  otherIconMinus.classList.add('hidden');
                }
              });

              // Open this item
              item.setAttribute('data-open', 'true');
              trigger.setAttribute('aria-expanded', 'true');
              body.classList.remove('hidden');
              iconPlus.classList.add('hidden');
              iconMinus.classList.remove('hidden');
            }
          });
        });
      });
    }
  };

})(Drupal, once);
