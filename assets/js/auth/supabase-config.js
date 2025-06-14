// Configuration Supabase - Ne charger que sur les pages d'authentification
const SUPABASE_URL = 'https://svvupcjjqyyehxbxlkro.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN2dnVwY2pqcXl5ZWh4Ynhsa3JvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDk0Njg3NjcsImV4cCI6MjA2NTA0NDc2N30.wSK-HT0_UCNIVsfrcHQ1OBOlgdbKR4uMICDtbwg6ivY';

// Vérifier que le SDK est chargé
if (typeof supabase === 'undefined') {
    // Charger le SDK de manière asynchrone
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2';
    script.async = true;
    script.onload = function() {
        // Initialiser Supabase une fois le SDK chargé
        initSupabase();
    };
    document.head.appendChild(script);
} else {
    // Si le SDK est déjà chargé, initialiser immédiatement
    initSupabase();
}

function initSupabase() {
    // Créer le client Supabase
    window.supabase = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY, {
        auth: { 
            autoRefreshToken: true, 
            persistSession: true, 
            detectSessionInUrl: true 
        }
    });
    
    console.log('Supabase initialisé avec succès');
}

// Exporter l'instance Supabase si nécessaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { supabase: window.supabase };
}
