# Mon Site Web

Ceci est un site web moderne construit avec HTML, CSS et JavaScript, utilisant Supabase comme backend.

## Structure du projet

```
mon-site-web/
├── public/                  # Fichiers publics
│   ├── css/                 # Feuilles de style
│   │   └── styles.css
│   ├── js/                  # Fichiers JavaScript
│   │   └── app.js
│   └── assets/              # Images et médias
├── index.html               # Page d'accueil
└── README.md               # Ce fichier
```

## Configuration requise

- Navigateur web moderne (Chrome, Firefox, Safari, Edge)
- Compte Supabase (pour le backend)

## Installation

1. Cloner le dépôt :
   ```
   git clone https://github.com/votre-utilisateur/mon-site-web.git
   cd mon-site-web
   ```

2. Configurer Supabase :
   - Créez un projet sur [Supabase](https://supabase.com/)
   - Mettez à jour les informations de configuration dans `public/js/app.js`
     ```javascript
     const supabaseUrl = 'VOTRE_URL_SUPABASE';
     const supabaseKey = 'VOTRE_CLE_SUPABASE';
     ```

3. Ouvrez `index.html` dans votre navigateur ou servez-le avec un serveur local.

## Fonctionnalités

- Interface utilisateur moderne et réactive
- Intégration avec Supabase pour l'authentification et la base de données
- Formulaire de contact
- Gestion d'état d'authentification

## Personnalisation

- Modifiez les couleurs dans `public/css/styles.css`
- Ajoutez vos propres styles et fonctionnalités dans les fichiers correspondants
- Personnalisez les requêtes Supabase selon vos besoins

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus d'informations.
