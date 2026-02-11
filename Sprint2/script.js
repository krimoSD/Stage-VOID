// Dark Mode
const themeToggleBtn = document.getElementById('theme-toggle');
const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

// Set light mode as default
if (!localStorage.getItem('color-theme')) {
    localStorage.setItem('color-theme', 'light');
    document.documentElement.classList.remove('dark');
    themeToggleDarkIcon.classList.remove('hidden');
    themeToggleLightIcon.classList.add('hidden');
} else if (localStorage.getItem('color-theme') === 'dark') {
    document.documentElement.classList.add('dark');
    themeToggleLightIcon.classList.remove('hidden');
    themeToggleDarkIcon.classList.add('hidden');
} else {
    document.documentElement.classList.remove('dark');
    themeToggleDarkIcon.classList.remove('hidden');
    themeToggleLightIcon.classList.add('hidden');
}

themeToggleBtn.addEventListener('click', function() {
    // Toggle icons
    themeToggleDarkIcon.classList.toggle('hidden');
    themeToggleLightIcon.classList.toggle('hidden');

    // Toggle dark mode
    if (document.documentElement.classList.contains('dark')) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('color-theme', 'light');
    } else {
        document.documentElement.classList.add('dark');
        localStorage.setItem('color-theme', 'dark');
    }
});

// Slider functionality
const slidesContainer = document.getElementById('slidesContainer');
const dots = document.querySelectorAll('.dot');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const totalSlides = dots.length;
let currentSlide = 0;
let autoplayInterval;

function goToSlide(index) {
    currentSlide = index;
    slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
    updateDots();
    resetAutoplay();
}

function updateDots() {
    dots.forEach((dot, index) => {
        if (index === currentSlide) {
            dot.classList.remove('bg-white/70', 'dark:bg-gray-300/70');
            dot.classList.add('bg-red-500', 'dark:bg-red-600', 'scale-125');
        } else {
            dot.classList.remove('bg-red-500', 'dark:bg-red-600', 'scale-125');
            dot.classList.add('bg-white/70', 'dark:bg-gray-300/70');
        }
    });
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % totalSlides;
    goToSlide(currentSlide);
}

function prevSlide() {
    currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
    goToSlide(currentSlide);
}

function startAutoplay() {
    autoplayInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
}

function stopAutoplay() {
    clearInterval(autoplayInterval);
}

function resetAutoplay() {
    stopAutoplay();
    startAutoplay();
}

dots.forEach((dot, index) => {
    dot.addEventListener('click', () => goToSlide(index));
});

nextBtn.addEventListener('click', nextSlide);
prevBtn.addEventListener('click', prevSlide);

const sliderSection = document.querySelector('section');
sliderSection.addEventListener('mouseenter', stopAutoplay);
sliderSection.addEventListener('mouseleave', startAutoplay);

document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') prevSlide();
    if (e.key === 'ArrowRight') nextSlide();
});

let touchStartX = 0;
let touchEndX = 0;

slidesContainer.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
});

slidesContainer.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
});

function handleSwipe() {
    if (touchEndX < touchStartX - 50) nextSlide(); // Swipe left
    if (touchEndX > touchStartX + 50) prevSlide(); // Swipe right
}

// Start autoplay when page loads
startAutoplay();

// counter animation
function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-target'));
    const duration = 2000;
    const increment = target / (duration / 16);
    let current = 0;

    const updateCounter = () => {
        current += increment;
        
        if (current < target) {
            element.textContent = Math.floor(current);
            requestAnimationFrame(updateCounter);
        } else {
            element.textContent = target;
        }
    };

    updateCounter();
}

// Intersection Observer to trigger animation when elements are in viewport
function setupCounterObserver() {
    const counters = document.querySelectorAll('.counter');
    
    const observerOptions = {
        threshold: 0.7,
        rootMargin: '0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                entry.target.classList.add('counted');
                animateCounter(entry.target);
            }
        });
    }, observerOptions);

    counters.forEach(counter => {
        observer.observe(counter);
    });
}



// Accordion functionality
const accordionItems = document.querySelectorAll('.accordion-item');

accordionItems.forEach(item => {
    const header = item.querySelector('.accordion-header');
    const content = item.querySelector('.accordion-content');
    const icon = item.querySelector('.accordion-icon');
    
    header.addEventListener('click', () => {
        const isOpen = content.style.maxHeight && content.style.maxHeight !== '0px';
        
        accordionItems.forEach(otherItem => {
            const otherContent = otherItem.querySelector('.accordion-content');
            const otherIcon = otherItem.querySelector('.accordion-icon');
            otherContent.style.maxHeight = '0px';
            otherIcon.style.transform = 'rotate(0deg)';
        });
        
        if (!isOpen) {
            content.style.maxHeight = content.scrollHeight + 'px';
            icon.style.transform = 'rotate(180deg)';
        }
    });
});



// Form Validation
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("contactForm");

  const fields = {
    name: {
      el: document.getElementById("name"),
      regex: /^[a-zA-ZÀ-ÿ\s]{3,}$/,
      msg: "Nom invalide (min 3 lettres)"
    },
    email: {
      el: document.getElementById("email"),
      regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
      msg: "Email invalide"
    },
    phone: {
      el: document.getElementById("num_tel"),
      regex: /^(?:\+212|0)([5-7]\d{8})$/,
      msg: "Numéro invalide (ex: 0612345678)"
    },
    subject: {
      el: document.getElementById("subject"),
      min: 3,
      msg: "Objet trop court"
    },
    message: {
      el: document.getElementById("message"),
      min: 10,
      msg: "Message trop court (min 10 caractères)"
    }
  };

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    clearErrors();

    let isValid = true;

    for (const key in fields) {
      const field = fields[key];
      const value = field.el.value.trim();

      if (
        (field.regex && !field.regex.test(value)) ||
        (field.min && value.length < field.min)
      ) {
        showError(field.el, field.msg);
        isValid = false;
      }
    }

    if (isValid) {
      alert("✅ Formulaire valide !");
      form.submit();
    }
  });

  function showError(input, message) {
    input.classList.add("border-red-500");

    const error = document.createElement("p");
    error.className = "form-error text-red-500 text-xs mt-1";
    error.innerText = message;

    input.parentElement.appendChild(error);
  }

  function clearErrors() {
    form.querySelectorAll(".form-error").forEach(el => el.remove());
    form.querySelectorAll("input, textarea").forEach(el =>
      el.classList.remove("border-red-500")
    );
  }
});



document.addEventListener('DOMContentLoaded', setupCounterObserver);