// Fonction pour initialiser le carrousel d'actualités
function initNewsCarousel(actualites) {
    const container = document.getElementById('actualites-container');
    if (!container) return;
    
    // Vider le conteneur
    container.innerHTML = '';
    
    // Créer la structure du carrousel
    const carouselHTML = `
        <div class="carousel-container">
            <div class="carousel-track">
                ${actualites.map((actualite, index) => `
                    <div class="carousel-item" data-index="${index}">
                        <div class="actualite-item">
                            <img src="${actualite.image_url || 'assets/images/default-news.jpg'}" alt="${actualite.titre || 'Actualité'}" class="actualite-image">
                            <div class="actualite-content">
                                <div class="actualite-date">${new Date(actualite.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}</div>
                                <h3 class="actualite-title">${actualite.titre || 'Sans titre'}</h3>
                                <p class="actualite-desc">${actualite.contenu || ''}</p>
                                <a href="#" class="actualite-link">Lire la suite</a>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
            <button class="carousel-arrow prev">❮</button>
            <button class="carousel-arrow next">❯</button>
            <div class="carousel-nav">
                ${actualites.map((_, index) => `
                    <div class="carousel-dot ${index === 0 ? 'active' : ''}" data-index="${index}"></div>
                `).join('')}
            </div>
        </div>
    `;
    
    container.innerHTML = carouselHTML;
    
    // Initialiser les variables du carrousel
    const track = container.querySelector('.carousel-track');
    const items = container.querySelectorAll('.carousel-item');
    const dots = container.querySelectorAll('.carousel-dot');
    const prevBtn = container.querySelector('.carousel-arrow.prev');
    const nextBtn = container.querySelector('.carousel-arrow.next');
    let currentIndex = 0;
    const itemsToShow = Math.min(3, items.length);
    const itemWidth = 100 / itemsToShow;
    
    // Mettre à jour la position du carrousel
    function updateCarousel() {
        track.style.transform = `translateX(-${currentIndex * itemWidth}%)`;
        
        // Mettre à jour les points de navigation
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
        
        // Gérer la visibilité des flèches
        prevBtn.style.display = currentIndex === 0 ? 'none' : 'flex';
        nextBtn.style.display = currentIndex >= items.length - itemsToShow ? 'none' : 'flex';
    }
    
    // Événements
    prevBtn.addEventListener('click', () => {
        if (currentIndex > 0) {
            currentIndex--;
            updateCarousel();
        }
    });
    
    nextBtn.addEventListener('click', () => {
        if (currentIndex < items.length - itemsToShow) {
            currentIndex++;
            updateCarousel();
        }
    });
    
    // Navigation par points
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            currentIndex = parseInt(dot.dataset.index);
            updateCarousel();
        });
    });
    
    // Navigation au clavier
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft' && currentIndex > 0) {
            currentIndex--;
            updateCarousel();
        } else if (e.key === 'ArrowRight' && currentIndex < items.length - itemsToShow) {
            currentIndex++;
            updateCarousel();
        }
    });
    
    // Initialisation
    updateCarousel();
    
    // Faire défiler automatiquement toutes les 5 secondes
    let autoScroll = setInterval(() => {
        if (currentIndex < items.length - itemsToShow) {
            currentIndex++;
        } else {
            currentIndex = 0;
        }
        updateCarousel();
    }, 5000);
    
    // Arrêter le défilement automatique au survol
    container.addEventListener('mouseenter', () => {
        clearInterval(autoScroll);
    });
    
    container.addEventListener('mouseleave', () => {
        autoScroll = setInterval(() => {
            if (currentIndex < items.length - itemsToShow) {
                currentIndex++;
            } else {
                currentIndex = 0;
            }
            updateCarousel();
        }, 5000);
    });
}

// Charger et afficher les actualités
document.addEventListener('DOMContentLoaded', function() {
    const actualitesContainer = document.getElementById('actualites-container');
    if (actualitesContainer) {
        try {
            // Charger les actualités depuis le script PHP
            fetch('admin/get_news.php') // Assurez-vous que le chemin est correct depuis index.html
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP ! statut: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.actualites && data.actualites.length > 0) {
                        // Trier par date décroissante (si 'date' est bien la date de création/publication)
                        // const sortedActualites = data.actualites.sort((a, b) => new Date(b.date) - new Date(a.date));
                        // initNewsCarousel(sortedActualites);
                        initNewsCarousel(data.actualites); // Le tri est déjà fait côté PHP
                    } else {
                        actualitesContainer.innerHTML = `
                            <div class="no-news" style="text-align: center; padding: 2rem;">
                                <i class="fas fa-newspaper" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p>${data.message || 'Aucune actualité à afficher pour le moment.'}</p>
                            </div>`;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération des actualités:', error);
                    actualitesContainer.innerHTML = `
                        <div class="error-message" style="color: #e74c3c; text-align: center; padding: 1rem; background: #fde8e8; border-radius: 4px; margin: 1rem 0;">
                            <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>
                            Impossible de charger les actualités. Veuillez réessayer plus tard.
                        </div>`;
                });
        } catch (e) {
            console.error('Erreur lors du chargement des actualités depuis localStorage:', e);
            actualitesContainer.innerHTML = `
                <div class="error-message" style="color: #e74c3c; text-align: center; padding: 1rem; background: #fde8e8; border-radius: 4px; margin: 1rem 0;">
                    <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>
                    Une erreur est survenue lors du chargement des actualités.
                </div>`;
        }
    }
});
