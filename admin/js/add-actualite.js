document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const form = document.getElementById('add-actualite-form');
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    const dropZoneContent = dropZone.querySelector('.drop-zone-content');
    const loadingIndicator = document.getElementById('loading');
    const messageContainer = document.getElementById('message');
    
    // Gestion du glisser-déposer
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        dropZone.classList.add('highlight');
    }
    
    function unhighlight() {
        dropZone.classList.remove('highlight');
    }
    
    // Gestion du dépôt de fichier
    dropZone.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }
    
    // Gestion de la sélection de fichier via le bouton
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });
    
    // Traitement des fichiers
    function handleFiles(files) {
        if (files.length === 0) return;
        
        const file = files[0];
        
        // Vérification du type de fichier
        if (!file.type.match('image.*')) {
            showMessage('Veuillez sélectionner une image valide (JPEG, PNG, GIF, WebP)', 'error');
            return;
        }
        
        // Vérification de la taille du fichier (max 5 Mo)
        if (file.size > 5 * 1024 * 1024) {
            showMessage('L\'image ne doit pas dépasser 5 Mo', 'error');
            return;
        }
        
        // Affichage de la prévisualisation
        const reader = new FileReader();
        
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            imagePreview.style.display = 'block';
            dropZoneContent.style.display = 'none';
        };
        
        reader.readAsDataURL(file);
    }
    
    // Gestion de la soumission du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validation des champs
        const formData = new FormData(form);
        const titre = formData.get('titre');
        const date = formData.get('date');
        const contenu = formData.get('contenu');
        const image = fileInput.files[0];
        
        if (!titre || !date || !contenu || !image) {
            showMessage('Veuillez remplir tous les champs et sélectionner une image', 'error');
            return;
        }
        
        // Vérification de la date
        const selectedDate = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            showMessage('La date de publication ne peut pas être dans le passé', 'error');
            return;
        }
        
        // Soumission du formulaire
        submitForm(formData);
    });
    
    // Fonction pour soumettre le formulaire via AJAX
    function submitForm(formData) {
        // Afficher l'indicateur de chargement
        loadingIndicator.style.display = 'block';
        
        fetch('save-actualite.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showMessage('Actualité ajoutée avec succès !', 'success');
                // Réinitialiser le formulaire
                form.reset();
                imagePreview.style.display = 'none';
                dropZoneContent.style.display = 'block';
                fileInput.value = '';
                
                // Rediriger vers le tableau de bord après 2 secondes
                setTimeout(() => {
                    window.location.href = 'dashboard.php?tab=actualites';
                }, 2000);
            } else {
                throw new Error(data.message || 'Une erreur est survenue');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showMessage(error.message || 'Une erreur est survenue lors de l\'ajout de l\'actualité', 'error');
        })
        .finally(() => {
            // Masquer l'indicateur de chargement
            loadingIndicator.style.display = 'none';
        });
    }
    
    // Fonction pour afficher les messages
    function showMessage(message, type) {
        messageContainer.textContent = message;
        messageContainer.className = `alert alert-${type}`;
        messageContainer.style.display = 'block';
        
        // Masquer le message après 5 secondes
        setTimeout(() => {
            messageContainer.style.display = 'none';
        }, 5000);
    }
    
    // Réinitialiser le formulaire si l'utilisateur revient en arrière
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            form.reset();
            imagePreview.style.display = 'none';
            dropZoneContent.style.display = 'block';
            fileInput.value = '';
        }
    });
});
