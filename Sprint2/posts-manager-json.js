// ===========================
// POSTS MANAGER (avec JSON externe)
// ===========================
const PostsManager = {
    postsLoaded: false,
    extraPosts: [],

    async init() {
        if (!elements.posts.loadMoreBtn) return;

        // Charger les données depuis le fichier JSON
        await this.loadPostsData();
        
        elements.posts.loadMoreBtn.addEventListener('click', () => this.loadMore());
    },

    async loadPostsData() {
        try {
            const response = await fetch('posts.json');
            if (!response.ok) {
                throw new Error('Impossible de charger les posts');
            }
            const data = await response.json();
            this.extraPosts = data.extraPosts || [];
        } catch (error) {
            console.error('Erreur lors du chargement des posts:', error);
            this.extraPosts = [];
        }
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
        
        // Gestion d'erreur de chargement d'image
        img.onerror = function() {
            this.src = 'images/placeholder.png'; // Image de fallback
            console.warn('Impossible de charger l\'image:', post.image);
        };

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

        if (this.extraPosts.length === 0) {
            console.warn('Aucun post supplémentaire à charger');
            loadMoreBtn.textContent = 'Aucun article supplémentaire disponible';
            loadMoreBtn.setAttribute('disabled', 'true');
            loadMoreBtn.classList.add('opacity-60', 'cursor-not-allowed');
            return;
        }

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

// Mise à jour de l'initialisation pour gérer l'async
document.addEventListener('DOMContentLoaded', async () => {
    ThemeManager.init();
    SearchManager.init();
    SliderManager.init();
    CounterManager.init();
    AccordionManager.init();
    await PostsManager.init(); // Attendre le chargement des posts
    FormManager.init();
});
