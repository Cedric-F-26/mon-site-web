// Gestion du menu mobile et interactions
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const mobileMenuBtn = document.querySelector('.mobile-menu');
    const nav = document.querySelector('nav');
    const dropdowns = document.querySelectorAll('.dropdown');
    const navLinks = document.querySelectorAll('nav a');
    const header = document.querySelector('header');
    
    // Menu mobile
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            nav.classList.toggle('active');
            this.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
    }
    
    // Gestion des menus déroulants sur desktop
    dropdowns.forEach(dropdown => {
        const link = dropdown.querySelector('a');
        const content = dropdown.querySelector('.dropdown-content');
        
        // Au survol sur desktop
        dropdown.addEventListener('mouseenter', () => {
            if (window.innerWidth > 992) {
                content.style.display = 'block';
                content.style.opacity = '0';
                content.style.transform = 'translateY(10px)';
                
                // Animation d'apparition
                setTimeout(() => {
                    content.style.opacity = '1';
                    content.style.transform = 'translateY(0)';
                }, 10);
            }
        });
        
        // Quand la souris quitte le menu déroulant
        dropdown.addEventListener('mouseleave', () => {
            if (window.innerWidth > 992) {
                content.style.opacity = '0';
                content.style.transform = 'translateY(10px)';
                
                // Masquer après l'animation
                setTimeout(() => {
                    content.style.display = 'none';
                }, 200);
            }
        });
        
        // Sur mobile, gestion du clic pour afficher/masquer
        if (link) {
            link.addEventListener('click', (e) => {
                if (window.innerWidth <= 992) {
                    e.preventDefault();
                    const isActive = content.style.display === 'block';
                    
                    // Fermer tous les autres menus déroulants
                    document.querySelectorAll('.dropdown-content').forEach(item => {
                        if (item !== content) {
                            item.style.display = 'none';
                        }
                    });
                    
                    // Basculer l'état du menu actuel
                    content.style.display = isActive ? 'none' : 'block';
                    
                    // Animation
                    if (!isActive) {
                        content.style.opacity = '0';
                        content.style.transform = 'translateY(10px)';
                        
                        setTimeout(() => {
                            content.style.opacity = '1';
                            content.style.transform = 'translateY(0)';
                        }, 10);
                    }
                }
            });
        }
    });
    
    // Fermer le menu mobile lors du clic sur un lien
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            // Ne pas fermer si c'est un lien de menu déroulant
            if (!link.parentElement.classList.contains('dropdown') || window.innerWidth <= 992) {
                nav.classList.remove('active');
                mobileMenuBtn.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
            
            // Désactiver le comportement par défaut pour les liens de menu déroulant sur mobile
            if (link.parentElement.classList.contains('dropdown') && window.innerWidth <= 992) {
                e.preventDefault();
            }
        });
    });
    
    // Fermer le menu en cliquant à l'extérieur
    document.addEventListener('click', (e) => {
        if (!nav.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
            nav.classList.remove('active');
            mobileMenuBtn.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
    });
    
    // Gestion du header fixe avec effet de défilement
    let lastScroll = 0;
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        // Masquer/afficher le header au défilement
        if (currentScroll <= 0) {
            header.classList.remove('scroll-up');
            return;
        }
        
        if (currentScroll > lastScroll && !header.classList.contains('scroll-down')) {
            // Défilement vers le bas
            header.classList.remove('scroll-up');
            header.classList.add('scroll-down');
        } else if (currentScroll < lastScroll && header.classList.contains('scroll-down')) {
            // Défilement vers le haut
            header.classList.remove('scroll-down');
            header.classList.add('scroll-up');
        }
        
        lastScroll = currentScroll;
    });
    
    // Animation au chargement de la page
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.fade-in, .slide-up, .slide-left, .slide-right');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.3;
            
            if (elementPosition < screenPosition) {
                element.classList.add('visible');
            }
        });
    };
    
    // Détecter la largeur de l'écran et ajuster le menu en conséquence
    const handleResize = () => {
        if (window.innerWidth > 992) {
            nav.style.display = '';
            document.body.classList.remove('menu-open');
        } else {
            if (!nav.classList.contains('active')) {
                nav.style.display = 'none';
            } else {
                nav.style.display = 'block';
            }
        }
    };
    
    // Événements
    window.addEventListener('scroll', animateOnScroll);
    window.addEventListener('resize', handleResize);
    
    // Initialisation
    animateOnScroll();
    handleResize();
});
