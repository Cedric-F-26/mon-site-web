import requests
import sys

def check_site(url):
    try:
        response = requests.get(url, timeout=10)
        print(f"✓ La page est accessible")
        print(f"Code de statut: {response.status_code}")
        print(f"Taille de la réponse: {len(response.content)} octets")
        print(f"Type de contenu: {response.headers.get('content-type', 'inconnu')}")
        
        # Vérifier les redirections
        if response.history:
            print("\nRedirections détectées:")
            for resp in response.history:
                print(f"- {resp.status_code} {resp.url} → {resp.headers.get('Location')}")
        
        return True
        
    except requests.exceptions.RequestException as e:
        print(f"✗ Erreur lors de la connexion à {url}")
        print(f"Détails de l'erreur: {str(e)}")
        return False

if __name__ == "__main__":
    url = "http://localhost:8000/login.php"
    print(f"Vérification de l'accessibilité de {url}...\n")
    
    success = check_site(url)
    
    if not success:
        print("\nConseils de dépannage:")
        print("1. Vérifiez que le serveur PHP est en cours d'exécution")
        print("2. Vérifiez que le port 8000 n'est pas utilisé par un autre service")
        print("3. Vérifiez les journaux d'erreurs PHP pour plus de détails")
        
    sys.exit(0 if success else 1)
