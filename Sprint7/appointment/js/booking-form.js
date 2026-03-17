(function (Drupal, once) {
  Drupal.behaviors.appointmentCalendarPick = {
    attach: function (context) {
      function findDateFromElement(target) {
        var el = target;
        for (var i = 0; i < 8 && el; i++) {
          // If the click is on a container cell, try finding a date inside it.
          if (el.querySelector) {
            var inner =
              el.querySelector('[data-date]') ||
              el.querySelector('time[datetime]') ||
              el.querySelector('a[href]');
            if (inner) {
              var innerDate = findDateFromElement(inner);
              if (innerDate) return innerDate;
            }
          }

          // Preferred: explicit data-date="YYYY-MM-DD".
          if (el.getAttribute && el.getAttribute('data-date')) {
            return el.getAttribute('data-date');
          }

          // Common: <time datetime="YYYY-MM-DD">.
          if (el.tagName === 'TIME' && el.getAttribute('datetime')) {
            return el.getAttribute('datetime').slice(0, 10);
          }

          // Sometimes we have aria-label or title containing an ISO date.
          if (el.getAttribute) {
            var aria = el.getAttribute('aria-label');
            if (aria) {
              var mA = aria.match(/(\d{4}-\d{2}-\d{2})/);
              if (mA) return mA[1];
            }
            var title = el.getAttribute('title');
            if (title) {
              var mT = title.match(/(\d{4}-\d{2}-\d{2})/);
              if (mT) return mT[1];
            }
          }

          // Sometimes the date is in an id like calendar-day-YYYY-MM-DD.
          if (el.id) {
            var m = el.id.match(/(\d{4}-\d{2}-\d{2})/);
            if (m) return m[1];
          }

          // Sometimes it's in a class name.
          if (el.className && typeof el.className === 'string') {
            var m2 = el.className.match(/(\d{4}-\d{2}-\d{2})/);
            if (m2) return m2[1];
          }

          // If it's a link, try extracting YYYY-MM-DD from its href.
          if (el.tagName === 'A' && el.getAttribute('href')) {
            var href = el.getAttribute('href');
            var m3 = href.match(/(\d{4}-\d{2}-\d{2})/);
            if (m3) return m3[1];
          }

          el = el.parentNode;
        }
        return null;
      }

      function normalizeDate(value) {
        if (!value) return null;

        // Already ISO.
        var iso = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (iso) return iso[0];

        // dd/mm/yyyy or dd-mm-yyyy.
        var dmy = value.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
        if (dmy) {
          var dd = String(dmy[1]).padStart(2, '0');
          var mm = String(dmy[2]).padStart(2, '0');
          var yyyy = dmy[3];
          return yyyy + '-' + mm + '-' + dd;
        }

        // Try to find ISO inside a longer string.
        var embedded = value.match(/(\d{4}-\d{2}-\d{2})/);
        if (embedded) return embedded[1];

        return null;
      }

      function setPickedDate(dateValue) {
        // Always target the booking form, not the view fragment.
        var bookingForm = (context.closest && context.closest('form')) ||
          document.querySelector('form[data-drupal-selector^="booking-form"]') ||
          document;

        var dateInput = bookingForm.querySelector('[name="steps_wrapper[date]"]');
        var selectedDate = bookingForm.querySelector('[data-selected-date="1"]');

        if (!dateValue) return;
        if (dateInput) dateInput.value = dateValue;
        if (selectedDate) selectedDate.textContent = dateValue;
      }

      // Delegate clicks within the embedded calendar view.
      // Drupal Views wrappers typically look like:
      // - .view-id-available-appointments
      // - .view-display-id-block_1 (or other display ids)
      once('appointmentCalendarPickDelegated', '.view-id-available-appointments, .view-available-appointments', context).forEach(function (viewEl) {
        viewEl.addEventListener('click', function (e) {
          var dateValue = normalizeDate(findDateFromElement(e.target));

          // Extra fallback: if no structured date found, try parsing common
          // attributes from the clicked element.
          if (!dateValue && e.target && e.target.getAttribute) {
            dateValue = normalizeDate(
              e.target.getAttribute('data-date') ||
              e.target.getAttribute('aria-label') ||
              e.target.getAttribute('title') ||
              (e.target.getAttribute('href') || '')
            );
          }

          if (!dateValue) return;

          // Visual selection highlight (optional).
          var prev = viewEl.querySelector('.is-picked-date');
          if (prev) prev.classList.remove('is-picked-date');
          var cell = e.target && e.target.closest ? e.target.closest('td, .calendar-day, .date-box') : null;
          if (cell) cell.classList.add('is-picked-date');

          setPickedDate(dateValue);
          // Avoid calendar navigation if the click was on/inside a link.
          var link = e.target && e.target.closest ? e.target.closest('a') : null;
          if (link) e.preventDefault();
        });
      });
    }
  };
})(Drupal, once);

