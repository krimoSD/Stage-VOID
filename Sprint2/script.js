// DOM ELEMENTS CENTRALIZATION
const elements = {
    theme: {
        toggle: document.getElementById('theme-toggle'),
        darkIcon: document.getElementById('theme-toggle-dark-icon'),
        lightIcon: document.getElementById('theme-toggle-light-icon')
    },
    search: {
        toggle: document.getElementById('search-toggle'),
        overlay: document.getElementById('search-overlay'),
        close: document.getElementById('search-close'),
        input: document.getElementById('search-input')
    },
    slider: {
        container: document.getElementById('slidesContainer'),
        dots: document.querySelectorAll('.dot'),
        prevBtn: document.getElementById('prevBtn'),
        nextBtn: document.getElementById('nextBtn'),
        section: document.querySelector('#hero')
    },
    posts: {
        loadMoreBtn: document.getElementById('loadMorePosts'),
        column1: document.querySelector('[data-articles-column="1"]'),
        column2: document.querySelector('[data-articles-column="2"]')
    },
    form: document.getElementById('contactForm'),
    counters: document.querySelectorAll('.counter'),
    accordionItems: document.querySelectorAll('.accordion-item')
};

// THEME MANAGER
const ThemeManager = {
    init() {
        if (!elements.theme.toggle) return;

        this.setInitialTheme();
        this.attachEventListeners();
    },

    setInitialTheme() {
        const theme = localStorage.getItem('color-theme') || 'light';
        const isDark = theme === 'dark';

        this.applyTheme(isDark);
        localStorage.setItem('color-theme', theme);
    },

    applyTheme(isDark) {
        const { toggle, darkIcon, lightIcon } = elements.theme;

        if (isDark) {
            document.documentElement.classList.add('dark');
            lightIcon?.classList.remove('hidden');
            darkIcon?.classList.add('hidden');
            toggle?.setAttribute('aria-pressed', 'true');
        } else {
            document.documentElement.classList.remove('dark');
            darkIcon?.classList.remove('hidden');
            lightIcon?.classList.add('hidden');
            toggle?.setAttribute('aria-pressed', 'false');
        }
    },

    toggleTheme() {
        const isDark = document.documentElement.classList.contains('dark');
        this.applyTheme(!isDark);
        localStorage.setItem('color-theme', isDark ? 'light' : 'dark');
    },

    attachEventListeners() {
        elements.theme.toggle?.addEventListener('click', () => this.toggleTheme());
    }
};

// SEARCH OVERLAY
const SearchManager = {
    init() {
        if (!elements.search.overlay) return;

        this.attachEventListeners();
    },

    open() {
        const { overlay, input } = elements.search;
        
        overlay.classList.remove('opacity-0', 'invisible');
        overlay.classList.add('opacity-100', 'visible');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        setTimeout(() => input?.focus(), 300);
    },

    close() {
        const { overlay, input } = elements.search;
        
        overlay.classList.remove('opacity-100', 'visible');
        overlay.classList.add('opacity-0', 'invisible');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        
        if (input) input.value = '';
    },

    handleSearch(term) {
        if (!term) return;
        
        // Implémentation de la recherche ici
        console.log('Recherche pour:', term);
        alert(`Recherche pour: ${term}\n(Fonctionnalité à implémenter)`);
    },

    attachEventListeners() {
        const { toggle, overlay, close, input } = elements.search;

        toggle?.addEventListener('click', () => this.open());
        close?.addEventListener('click', () => this.close());

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && overlay?.classList.contains('visible')) {
                this.close();
            }
        });

        // Close on backdrop click
        overlay?.addEventListener('click', (e) => {
            if (e.target === overlay) this.close();
        });

        // Handle search input
        input?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                this.handleSearch(input.value.trim());
            }
        });

        // Handle suggestion clicks
        const suggestionButtons = overlay?.querySelectorAll('button[class*="bg-gray-100"]');
        suggestionButtons?.forEach(button => {
            button.addEventListener('click', () => {
                if (input) {
                    input.value = button.textContent.trim();
                    input.focus();
                }
            });
        });
    }
};

// SLIDER
const SliderManager = {
    currentSlide: 0,
    totalSlides: 0,
    autoplayInterval: null,
    isTransitioning: false,
    sliderHasFocus: false,
    touchStartX: 0,
    touchEndX: 0,

    init() {
        if (!elements.slider.container || !elements.slider.dots.length) return;

        this.totalSlides = elements.slider.dots.length;
        this.attachEventListeners();
        this.startAutoplay();
    },

    goToSlide(index) {
        if (this.isTransitioning) return;

        this.isTransitioning = true;
        this.currentSlide = ((index % this.totalSlides) + this.totalSlides) % this.totalSlides;
        
        elements.slider.container.style.transform = `translateX(-${this.currentSlide * 100}%)`;
        this.updateDots();

        setTimeout(() => {
            this.isTransitioning = false;
        }, 500);
    },

    updateDots() {
        elements.slider.dots.forEach((dot, index) => {
            if (index === this.currentSlide) {
                dot.classList.remove('bg-white/70', 'dark:bg-gray-300/70');
                dot.classList.add('bg-red-500', 'dark:bg-red-600', 'scale-125');
                dot.setAttribute('aria-pressed', 'true');
            } else {
                dot.classList.remove('bg-red-500', 'dark:bg-red-600', 'scale-125');
                dot.classList.add('bg-white/70', 'dark:bg-gray-300/70');
                dot.setAttribute('aria-pressed', 'false');
            }
        });
    },

    nextSlide() {
        this.goToSlide(this.currentSlide + 1);
        this.resetAutoplay();
    },

    prevSlide() {
        this.goToSlide(this.currentSlide - 1);
        this.resetAutoplay();
    },

    startAutoplay() {
        this.autoplayInterval = setInterval(() => this.nextSlide(), 5000);
    },

    stopAutoplay() {
        clearInterval(this.autoplayInterval);
    },

    resetAutoplay() {
        this.stopAutoplay();
        this.startAutoplay();
    },

    handleSwipe() {
        if (this.touchEndX < this.touchStartX - 50) this.nextSlide();
        if (this.touchEndX > this.touchStartX + 50) this.prevSlide();
    },

    attachEventListeners() {
        const { dots, prevBtn, nextBtn, section, container } = elements.slider;

        // Dot navigation
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                this.goToSlide(index);
                this.resetAutoplay();
            });
        });

        // Button navigation
        nextBtn?.addEventListener('click', () => this.nextSlide());
        prevBtn?.addEventListener('click', () => this.prevSlide());

        // Autoplay pause on hover
        section?.addEventListener('mouseenter', () => {
            this.sliderHasFocus = true;
            this.stopAutoplay();
        });
        section?.addEventListener('mouseleave', () => {
            this.sliderHasFocus = false;
            this.startAutoplay();
        });

        // Keyboard navigation (only when focused)
        document.addEventListener('keydown', (e) => {
            if (!this.sliderHasFocus) return;
            
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                this.prevSlide();
            }
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                this.nextSlide();
            }
        });

        // Touch/swipe support
        container?.addEventListener('touchstart', (e) => {
            this.touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        container?.addEventListener('touchend', (e) => {
            this.touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe();
        });
    }
};

// COUNTER ANIMATION
const CounterManager = {
    init() {
        if (!elements.counters.length) return;

        const observerOptions = {
            threshold: 0.7,
            rootMargin: '0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                    entry.target.classList.add('counted');
                    this.animateCounter(entry.target);
                }
            });
        }, observerOptions);

        elements.counters.forEach(counter => observer.observe(counter));
    },

    animateCounter(element) {
        const target = parseInt(element.getAttribute('data-target'));
        if (isNaN(target)) return;

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
};

// ACCORDION
const AccordionManager = {
    init() {
        if (!elements.accordionItems.length) return;

        elements.accordionItems.forEach(item => {
            const header = item.querySelector('.accordion-header');
            const content = item.querySelector('.accordion-content');
            const icon = item.querySelector('.accordion-icon');

            if (!header || !content || !icon) return;

            // Set initial state
            header.setAttribute('aria-expanded', 'false');
            content.style.maxHeight = '0px';

            header.addEventListener('click', () => {
                this.toggleItem(item, header, content, icon);
            });
        });
    },

    toggleItem(currentItem, header, content, icon) {
        const isOpen = content.style.maxHeight && content.style.maxHeight !== '0px';

        // Close all items
        elements.accordionItems.forEach(item => {
            const otherContent = item.querySelector('.accordion-content');
            const otherIcon = item.querySelector('.accordion-icon');
            const otherHeader = item.querySelector('.accordion-header');

            if (otherContent) otherContent.style.maxHeight = '0px';
            if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
            if (otherHeader) otherHeader.setAttribute('aria-expanded', 'false');
        });

        // Open current item if it was closed
        if (!isOpen) {
            content.style.maxHeight = content.scrollHeight + 'px';
            icon.style.transform = 'rotate(180deg)';
            header.setAttribute('aria-expanded', 'true');
        }
    }
};

// POSTS
const PostsManager = {
    postsLoaded: false,

    // Données centralisées (à externaliser dans un fichier JSON en production)
    extraPosts: [
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
    ],

    init() {
        if (!elements.posts.loadMoreBtn) return;

        elements.posts.loadMoreBtn.addEventListener('click', () => this.loadMore());
    },

    createArticleCard(post) {
        // Validation des données
        if (!post || !post.title || !post.image) {
            console.warn('Article invalide :', post);
            return null;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'flex flex-col sm:flex-row gap-4 sm:gap-6 mb-6 sm:mb-8';

        // Utilisation de textContent pour la sécurité
        const img = document.createElement('img');
        img.src = post.image;
        img.alt = post.alt || '';
        img.loading = 'lazy';
        img.decoding = 'async';
        img.className = 'w-full sm:w-48 h-auto sm:h-auto bg-gray-200 dark:bg-gray-700 flex-shrink-0 transition-colors duration-300';

        const contentDiv = document.createElement('div');
        contentDiv.className = 'flex-1';

        const title = document.createElement('h3');
        title.className = 'text-lg sm:text-xl font-serif mb-2 text-gray-900 dark:text-white';
        title.textContent = post.title;

        const date = document.createElement('p');
        date.className = 'text-xs sm:text-sm text-gray-500 dark:text-gray-400';
        date.textContent = post.date;

        contentDiv.appendChild(title);
        contentDiv.appendChild(date);
        wrapper.appendChild(img);
        wrapper.appendChild(contentDiv);

        return wrapper;
    },

    loadMore() {
        if (this.postsLoaded) return;

        const { column1, column2, loadMoreBtn } = elements.posts;
        if (!column1 || !column2) return;

        this.extraPosts.forEach((post) => {
            const card = this.createArticleCard(post);
            if (!card) return;

            if (post.column === 1) {
                column1.appendChild(card);
            } else if (post.column === 2) {
                column2.appendChild(card);
            }
        });

        this.postsLoaded = true;
        loadMoreBtn.setAttribute('aria-expanded', 'true');
        loadMoreBtn.setAttribute('disabled', 'true');
        loadMoreBtn.classList.add('opacity-60', 'cursor-not-allowed');
        loadMoreBtn.textContent = 'Tous les articles sont affichés';
    }
};

// FORM VALIDATION
const FormManager = {
    fields: {
        name: {
            el: null,
            required: true,
            regex: /^[a-zA-ZÀ-ÿ\s]{3,}$/,
            msg: "Nom invalide (minimum 3 lettres)"
        },
        email: {
            el: null,
            required: true,
            regex: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,
            msg: "Adresse email invalide"
        },
        phone: {
            el: null,
            required: true,
            regex: /^(?:\+212|0)([5-7]\d{8})$/,
            msg: "Numéro invalide (ex: 0612345678)"
        },
        subject: {
            el: null,
            required: true,
            min: 3,
            msg: "Objet trop court (min 3 caractères)"
        },
        message: {
            el: null,
            required: true,
            min: 10,
            msg: "Message trop court (min 10 caractères)"
        }
    },

    init() {
        const form = document.querySelector("form");
        if (!form) return;

        this.form = form;

        this.fields.name.el = document.getElementById("name");
        this.fields.email.el = document.getElementById("email");
        this.fields.phone.el = document.getElementById("num_tel");
        this.fields.subject.el = document.getElementById("subject");
        this.fields.message.el = document.getElementById("message");

        // Submit
        this.form.addEventListener("submit", (e) => this.handleSubmit(e));

        // Real-time validation
        Object.values(this.fields).forEach(field => {
            field.el?.addEventListener("blur", () => {
                this.validateField(field);
            });
        });
    },

    handleSubmit(e) {
        e.preventDefault();
        this.clearErrors();

        let isValid = true;

        for (const key in this.fields) {
            const field = this.fields[key];
            if (!this.validateField(field)) {
                isValid = false;
            }
        }

        if (isValid) {
            this.form.submit();
        }
    },

    validateField(field) {
        if (!field.el) return true;

        const value = field.el.value.trim();

        // Required check
        if (field.required && value === "") {
            this.showError(field.el, "Ce champ est obligatoire");
            return false;
        }

        // Regex check
        if (field.regex && !field.regex.test(value)) {
            this.showError(field.el, field.msg);
            return false;
        }

        // Length check
        if (field.min && value.length < field.min) {
            this.showError(field.el, field.msg);
            return false;
        }

        this.clearFieldError(field.el);
        return true;
    },

    showError(input, message) {
        this.clearFieldError(input);

        input.classList.add("border-red-500");
        input.setAttribute("aria-invalid", "true");

        const error = document.createElement("p");
        error.className = "form-error text-red-500 text-xs mt-1";
        error.id = `${input.id}-error`;
        error.textContent = message;

        input.setAttribute("aria-describedby", error.id);
        input.parentElement.appendChild(error);
    },

    clearFieldError(input) {
        input.classList.remove("border-red-500");
        input.removeAttribute("aria-invalid");
        input.removeAttribute("aria-describedby");

        const error = input.parentElement.querySelector(".form-error");
        if (error) error.remove();
    },

    clearErrors() {
        this.form.querySelectorAll(".form-error").forEach(el => el.remove());
        this.form.querySelectorAll("input, textarea").forEach(el => {
            el.classList.remove("border-red-500");
            el.removeAttribute("aria-invalid");
            el.removeAttribute("aria-describedby");
        });
    }
};

// INITIALIZATION
document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
    SearchManager.init();
    SliderManager.init();
    CounterManager.init();
    AccordionManager.init();
    PostsManager.init();
    FormManager.init();
});
