// Configuration de TinyMCE pour l'éditeur de texte riche
function initTinyMCE() {
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '#contenu',
            height: 300,
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | help',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px }',
            language: 'fr_FR',
            language_url: 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.5/langs/fr_FR.min.js',
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save();
                });
            }
        });
    }
}

// Initialiser TinyMCE lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Charger TinyMCE depuis un CDN s'il n'est pas déjà chargé
    if (typeof tinymce === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.5/tinymce.min.js';
        script.integrity = 'sha512-9n6hJfYh1f+9Xy7gT0HgVHj4jRnFUPbHX4v8X4fJx81mY4Jq5ByRc/KjO5tvE7P0v5k4Dp6vW6fZKX9EudwBw==';
        script.crossOrigin = 'anonymous';
        script.referrerPolicy = 'no-referrer';
        script.onload = function() {
            initTinyMCE();
        };
        document.head.appendChild(script);
    } else {
        initTinyMCE();
    }
});
