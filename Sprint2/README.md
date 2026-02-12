# 🚀 Sprint HTML, CSS & JavaScript - VOID Maroc

> Projet d'intégration web moderne développé dans le cadre du sprint HTML/CSS/JS avec focus sur les bonnes pratiques, la performance et l'accessibilité.

![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=flat&logo=tailwind-css&logoColor=white)


## 🎯 À propos

Ce projet est une landing page moderne pour l'agence digitale **VOID Maroc**, spécialisée dans la transformation digitale, React, Drupal et les applications mobiles. Le site met en avant les services de l'agence avec une interface responsive et accessible.

## ✨ Fonctionnalités

### 🎨 Interactions JavaScript

- **Slider/Carousel automatique** : Navigation avec flèches, points indicateurs, swipe mobile et contrôle clavier
- **Dark Mode** : Basculement entre thème clair et sombre avec sauvegarde de préférence
- **Accordion FAQ** : Sections dépliables avec animations fluides
- **Compteurs animés** : Animation des statistiques au scroll (Intersection Observer)
- **Formulaire de contact** : Validation côté client avec messages d'erreur en temps réel
- **Load More (Articles)** : Chargement dynamique de contenu additionnel

### 📱 Responsive Design

- Design mobile-first
- Breakpoints adaptés pour mobile, tablette et desktop
- Images optimisées avec lazy loading
- Touch gestures pour mobile (swipe)

### ♿ Accessibilité

- Attributs ARIA appropriés (`aria-label`, `aria-pressed`, `aria-expanded`)
- Navigation au clavier (flèches pour le slider)
- Contraste respectant les normes WCAG
- Textes alternatifs sur toutes les images
- Structure sémantique HTML5

## 🛠 Technologies utilisées

- **HTML5** : Structure sémantique et moderne
- **CSS3** : Animations et transitions
- **Tailwind CSS** : Framework CSS utility-first
- **JavaScript Vanilla** : Interactions sans dépendances externes
- **Font Awesome / SVG** : Icônes vectorielles

## 📚 Concepts appliqués

### HTML

✅ Structure sémantique (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`)  
✅ Balises meta complètes (SEO, Open Graph, Twitter Cards)  
✅ Formulaires avec validation HTML5  
✅ Attributs accessibilité (ARIA)

### CSS / Tailwind

✅ Design responsive (mobile-first)  
✅ Thème sombre avec `dark:` classes  
✅ Animations et transitions  
✅ Grid et Flexbox pour layouts  
✅ Optimisation avec utility classes

### JavaScript

✅ ES6+ (const, let, arrow functions, template literals)  
✅ DOM Manipulation  
✅ Event Listeners (click, keyboard, touch, scroll)  
✅ LocalStorage pour persistance  
✅ Intersection Observer API  
✅ Validation de formulaire avec regex  
✅ Touch events pour mobile

### Performance

✅ Lazy loading des images (`loading="lazy"`)  
✅ Preconnect pour ressources externes  
✅ Defer/async pour scripts  
✅ Optimisation des animations (requestAnimationFrame)  
✅ Minification potentielle pour production

### SEO

✅ Balises meta complètes  
✅ Open Graph pour réseaux sociaux  
✅ Twitter Cards  
✅ Canonical URL  
✅ Robots meta  
✅ Structure de contenu hiérarchique (h1-h6)  
✅ Alt text sur images

## 📁 Structure du projet

```
void-maroc/
│
├── index.html          # Page principale
├── script.js           # Logique JavaScript
├── README.md           # Documentation
│
├── images/             # Assets visuels
│   ├── og-image.jpg
│   ├── twitter-image.jpg
│   ├── slide-*.webp
│   └── photo-*.avif
│
└── (Tailwind CDN)      # Framework CSS chargé via CDN
```


## 🧪 Tests de performance

### Outils utilisés

- **Google Lighthouse** (DevTools)
- **PageSpeed Insights**
- **WebPageTest**
- **GTmetrix**

### Métriques ciblées

- **Performance** : > 90/100
- **Accessibility** : > 95/100
- **Best Practices** : > 90/100
- **SEO** : > 95/100


## ♿ Accessibilité

### Conformité WCAG 2.1

- ✅ Niveau AA atteint
- ✅ Navigation au clavier
- ✅ Lecteurs d'écran compatibles
- ✅ Contraste des couleurs validé
- ✅ Focus visible sur éléments interactifs

### Tests effectués

- Keyboard navigation
- Screen reader (NVDA / JAWS)
- Color contrast checker
- WAVE accessibility tool

## 🔍 SEO

### Optimisations

- ✅ Balises meta complètes
- ✅ Schema.org markup potentiel
- ✅ Sitemap.xml (à générer)
- ✅ Robots.txt (à configurer)
- ✅ URLs sémantiques
- ✅ Contenu structuré (H1-H6)
- ✅ Images optimisées avec alt


## 📝 Checklist Sprint

- [x] Structure HTML sémantique
- [x] Balises et métadonnées SEO
- [x] Formulaire avec validation
- [x] Bonnes pratiques HTML
- [x] CSS / Tailwind CSS responsive
- [x] Dark mode toggle
- [x] JavaScript natif (ES6+)
- [x] Slider automatique avec contrôles
- [x] Accordion FAQ
- [x] Load More dynamique
- [x] Lazy loading images
- [x] Preconnect / Preload
- [x] Accessibilité (ARIA, keyboard)
- [x] Tests responsive (mobile, tablet, desktop)
- [x] Tests de performance
- [x] Déploiement Vercel
- [x] Repository Git

## 🎓 Concepts appris

### HTML

- Structuration sémantique efficace
- Optimisation SEO avec meta tags
- Open Graph et Twitter Cards
- Accessibilité avec ARIA

### CSS / Tailwind

- Utility-first CSS workflow
- Dark mode avec Tailwind
- Responsive design patterns
- Animations et transitions

### JavaScript

- Manipulation DOM moderne
- Event delegation
- Intersection Observer
- LocalStorage
- Touch events
- Validation formulaires

### Performance

- Lazy loading stratégique
- Preconnect pour ressources externes
- RequestAnimationFrame pour animations
- Optimisation des images (WebP, AVIF)

### Accessibilité

- Attributs ARIA appropriés
- Navigation clavier
- Compatibilité lecteurs d'écran
- Contraste des couleurs

## 👨‍💻 Auteur

Sadiki Abdelkarim