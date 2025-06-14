# Sauvegarde - Module d'Authentification Supabase

Ce dossier contient une sauvegarde des fichiers liés au système d'authentification privée du site web Franchini, utilisant Supabase comme solution d'authentification.

## Fichiers inclus

### Pages
- `pages/connexion-prive.html` - Page de connexion sécurisée avec formulaire d'authentification
- `pages/update-password.html` - Page de réinitialisation du mot de passe

### Configuration
- `js/supabase-config.js` - Configuration du client Supabase avec les clés d'API

## Fonctionnalités

### Connexion sécurisée
- Authentification par email/mot de passe
- Gestion des erreurs et messages utilisateur
- Lien "Mot de passe oublié"
- Protection contre les attaques par force brute

### Réinitialisation de mot de passe
- Génération de lien sécurisé
- Validation du mot de passe
- Messages d'erreur/succès

## Configuration requise

- Compte Supabase avec l'authentification activée
- URL de redirection configurée dans les paramètres d'authentification Supabase
- SDK Supabase chargé via CDN

## Notes importantes

- Les clés d'API dans `supabase-config.js` sont des clés anonymes et sécurisées pour une utilisation côté client
- Assurez-vous que les URL de redirection dans Supabase correspondent à votre configuration de déploiement
- La sécurité des mots de passe est gérée par Supabase (hachage, sel, etc.)

## Auteur

Sauvegarde créée le 14/06/2025
