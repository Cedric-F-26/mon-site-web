// Configuration de Supabase
const supabaseUrl = 'VOTRE_URL_SUPABASE';
const supabaseKey = 'VOTRE_CLE_SUPABASE';
const supabase = supabase.createClient(supabaseUrl, supabaseKey);

// Éléments du DOM
const loginBtn = document.getElementById('loginBtn');
const registerBtn = document.getElementById('registerBtn');
const logoutBtn = document.getElementById('logoutBtn');
const contactForm = document.getElementById('contactForm');
const newsletterForm = document.querySelector('.newsletter-form');
const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
const mainNav = document.querySelector('.main-nav');

// Gestion du menu mobile
if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', () => {
        mainNav.classList.toggle('active');
        mobileMenuBtn.innerHTML = mainNav.classList.contains('active') 
            ? '<i class="fas fa-times"></i>' 
            : '<i class="fas fa-bars"></i>';
    });
}

// Fermer le menu mobile lors du clic sur un lien
const navLinks = document.querySelectorAll('.main-nav a');
navLinks.forEach(link => {
    link.addEventListener('click', () => {
        mainNav.classList.remove('active');
        mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    });
});

// Gestion de l'authentification
async function handleAuth() {
    const { data: { session } } = await supabase.auth.getSession();
    updateAuthUI(session);

    // Écouter les changements d'état d'authentification
    const { data: { subscription } } = supabase.auth.onAuthStateChange((event, session) => {
        updateAuthUI(session);
    });

    // Nettoyer l'écouteur d'événements lors de la déconnexion
    return () => subscription.unsubscribe();
}

// Mettre à jour l'interface utilisateur en fonction de l'état d'authentification
function updateAuthUI(session) {
    if (session) {
        // Utilisateur connecté
        if (loginBtn) loginBtn.style.display = 'none';
        if (registerBtn) registerBtn.style.display = 'none';
        if (logoutBtn) {
            logoutBtn.style.display = 'inline-block';
            logoutBtn.addEventListener('click', handleLogout);
        }
    } else {
        // Utilisateur non connecté
        if (loginBtn) loginBtn.style.display = 'inline-block';
        if (registerBtn) registerBtn.style.display = 'inline-block';
        if (logoutBtn) logoutBtn.style.display = 'none';
    }
}

// Gestion de la déconnexion
async function handleLogout() {
    try {
        const { error } = await supabase.auth.signOut();
        if (error) throw error;
        // Rediriger vers la page d'accueil après la déconnexion
        window.location.href = '/';
    } catch (error) {
        console.error('Erreur lors de la déconnexion:', error.message);
        showNotification('Une erreur est survenue lors de la déconnexion', 'error');
    }
}

// Gestion du formulaire de contact
if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(contactForm);
        const formValues = Object.fromEntries(formData.entries());
        
        try {
            // Ici, vous pouvez envoyer les données à votre base de données Supabase
            // ou à un service d'email comme EmailJS
            console.log('Données du formulaire:', formValues);
            
            // Exemple d'envoi à une table Supabase (à décommenter et configurer)
            /*
            const { data, error } = await supabase
                .from('contacts')
                .insert([
                    { 
                        name: formValues.name,
                        email: formValues.email,
                        subject: formValues.subject,
                        message: formValues.message,
                        created_at: new Date()
                    }
                ]);
            
            if (error) throw error;
            */
            
            // Afficher un message de succès
            showNotification('Votre message a été envoyé avec succès !', 'success');
            contactForm.reset();
            
        } catch (error) {
            console.error('Erreur lors de l\'envoi du formulaire:', error);
            showNotification('Une erreur est survenue lors de l\'envoi du formulaire', 'error');
        }
    });
}

// Gestion de l'inscription à la newsletter
if (newsletterForm) {
    newsletterForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = newsletterForm.querySelector('input[type="email"]').value;
        
        if (!email) {
            showNotification('Veuillez entrer une adresse email valide', 'error');
            return;
        }
        
        try {
            // Ici, vous pouvez ajouter l'email à votre table newsletter dans Supabase
            // Exemple (à décommenter et configurer) :
            /*
            const { data, error } = await supabase
                .from('newsletter')
                .insert([{ email, subscribed_at: new Date() }]);
            
            if (error) throw error;
            */
            
            showNotification('Merci pour votre inscription à notre newsletter !', 'success');
            newsletterForm.reset();
            
        } catch (error) {
            console.error('Erreur lors de l\'inscription à la newsletter:', error);
            showNotification('Une erreur est survenue lors de l\'inscription', 'error');
        }
    });
}

// Afficher une notification
function showNotification(message, type = 'info') {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    // Ajouter la notification au corps du document
    document.body.appendChild(notification);
    
    // Supprimer la notification après 5 secondes
    setTimeout(() => {
        notification.classList.add('fade-out');
        notification.addEventListener('animationend', () => {
            notification.remove();
        });
    }, 5000);
}

// Initialiser l'application
function initApp() {
    handleAuth();
    
    // Ajouter des animations au défilement
    setupScrollAnimations();
}

// Configurer les animations au défilement
function setupScrollAnimations() {
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.service-card, .about-content, .contact-container');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.3;
            
            if (elementPosition < screenPosition) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Configurer les éléments pour l'animation
    const animatedElements = document.querySelectorAll('.service-card, .about-content, .contact-container');
    animatedElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
    });
    
    // Démarrer les animations
    window.addEventListener('scroll', animateOnScroll);
    // Vérifier les éléments visibles au chargement
    animateOnScroll();
}

// Démarrer l'application lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', initApp);
