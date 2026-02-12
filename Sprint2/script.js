// Dark Mode
const themeToggleBtn = document.getElementById('theme-toggle');
const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

// Set light mode as default and sync ARIA state
if (!localStorage.getItem('color-theme')) {
    localStorage.setItem('color-theme', 'light');
    document.documentElement.classList.remove('dark');
    themeToggleDarkIcon.classList.remove('hidden');
    themeToggleLightIcon.classList.add('hidden');
    themeToggleBtn.setAttribute('aria-pressed', 'false');
} else if (localStorage.getItem('color-theme') === 'dark') {
    document.documentElement.classList.add('dark');
    themeToggleLightIcon.classList.remove('hidden');
    themeToggleDarkIcon.classList.add('hidden');
    themeToggleBtn.setAttribute('aria-pressed', 'true');
} else {
    document.documentElement.classList.remove('dark');
    themeToggleDarkIcon.classList.remove('hidden');
    themeToggleLightIcon.classList.add('hidden');
    themeToggleBtn.setAttribute('aria-pressed', 'false');
}

themeToggleBtn.addEventListener('click', function() {
    // Toggle icons
    themeToggleDarkIcon.classList.toggle('hidden');
    themeToggleLightIcon.classList.toggle('hidden');

    // Toggle dark mode
    const isDark = document.documentElement.classList.contains('dark');
    if (isDark) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('color-theme', 'light');
        themeToggleBtn.setAttribute('aria-pressed', 'false');
    } else {
        document.documentElement.classList.add('dark');
        localStorage.setItem('color-theme', 'dark');
        themeToggleBtn.setAttribute('aria-pressed', 'true');
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
            dot.setAttribute('aria-pressed', 'true');
        } else {
            dot.classList.remove('bg-red-500', 'dark:bg-red-600', 'scale-125');
            dot.classList.add('bg-white/70', 'dark:bg-gray-300/70');
            dot.setAttribute('aria-pressed', 'false');
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
}, { passive: true });

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

    // Ensure initial ARIA state
    header.setAttribute('aria-expanded', 'false');
    content.style.maxHeight = '0px';
    
    header.addEventListener('click', () => {
        const isOpen = content.style.maxHeight && content.style.maxHeight !== '0px';
        
        accordionItems.forEach(otherItem => {
            const otherContent = otherItem.querySelector('.accordion-content');
            const otherIcon = otherItem.querySelector('.accordion-icon');
            const otherHeader = otherItem.querySelector('.accordion-header');
            otherContent.style.maxHeight = '0px';
            otherIcon.style.transform = 'rotate(0deg)';
            otherHeader.setAttribute('aria-expanded', 'false');
        });
        
        if (!isOpen) {
            content.style.maxHeight = content.scrollHeight + 'px';
            icon.style.transform = 'rotate(180deg)';
            header.setAttribute('aria-expanded', 'true');
        } else {
            header.setAttribute('aria-expanded', 'false');
        }
    });
});



// Helper to create article cards for "View All Posts"
function createArticleCard(post) {
  const wrapper = document.createElement('div');
  wrapper.className = 'flex flex-col sm:flex-row gap-4 sm:gap-6 mb-6 sm:mb-8';

  wrapper.innerHTML = `
    <img
      src="${post.image}"
      alt="${post.alt}"
      loading="lazy"
      decoding="async"
      class="w-full sm:w-48 h-auto sm:h-auto bg-gray-200 dark:bg-gray-700 flex-shrink-0 transition-colors duration-300"
    />
    <div class="flex-1">
      <h3 class="text-lg sm:text-xl font-serif mb-2 text-gray-900 dark:text-white">
        ${post.title}
      </h3>
      <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
        ${post.date}
      </p>
    </div>
  `;

  return wrapper;
}

// Extra posts data (previously in posts.json)
const EXTRA_POSTS = [
  {
    id: 'chatgpt-apps-nextjs',
    column: 1,
    title: "Apps ChatGPT & Model Context Protocol : Développement avec Next.js",
    date: "February 11, 2026",
    image: "images/chatgpt-apps-void.png",
    alt: "Illustration d'applications ChatGPT et Next.js"
  },
  {
    id: 'shopify-ecommerce-weekend',
    column: 2,
    title: "Shopify : Guide E-commerce La plateforme qui génère $14.6B de ventes en un weekend",
    date: "February 11, 2026",
    image: "images/photo-1556742049-0cfed4f6a45d.avif",
    alt: "Photo illustrant le e-commerce Shopify"
  }
];

// "View All Posts" - load extra articles (no external JSON)
document.addEventListener('DOMContentLoaded', () => {
  const loadMoreBtn = document.getElementById('loadMorePosts');
  if (!loadMoreBtn) return;

  let postsLoaded = false;

  loadMoreBtn.addEventListener('click', () => {
    if (postsLoaded) return;

    const col1 = document.querySelector('[data-articles-column="1"]');
    const col2 = document.querySelector('[data-articles-column="2"]');
    if (!col1 || !col2) return;

    EXTRA_POSTS.forEach((post) => {
      const card = createArticleCard(post);
      if (post.column === 1) {
        col1.appendChild(card);
      } else if (post.column === 2) {
        col2.appendChild(card);
      }
    });

    postsLoaded = true;
    loadMoreBtn.setAttribute('aria-expanded', 'true');
    loadMoreBtn.setAttribute('disabled', 'true');
    loadMoreBtn.classList.add('opacity-60', 'cursor-not-allowed');
    loadMoreBtn.textContent = 'Tous les articles sont affichés';
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