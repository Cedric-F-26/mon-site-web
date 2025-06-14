// Configuration Supabase
const SUPABASE_URL = 'https://svvupcjjqyyehxbxlkro.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN2dnVwY2pqcXl5ZWh4Ynhsa3JvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDk0Njg3NjcsImV4cCI6MjA2NTA0NDc2N30.wSK-HT0_UCNIVsfrcHQ1OBOlgdbKR4uMICDtbwg6ivY';

// Vérifier que le SDK est chargé
if (typeof supabase === 'undefined') {
    document.write('<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"><\/script>');
    document.write(`
        <script>
            // Attendre que le SDK soit chargé
            document.addEventListener('DOMContentLoaded', function() {
                window.supabase = supabase.createClient('${SUPABASE_URL}', '${SUPABASE_ANON_KEY}', {
                    auth: { autoRefreshToken: true, persistSession: true, detectSessionInUrl: true }
                });
            });
        <\/script>
    `);
} else {
    // Si le SDK est déjà chargé, créer le client immédiatement
    window.supabase = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY, {
        auth: { autoRefreshToken: true, persistSession: true, detectSessionInUrl: true }
    });
}
