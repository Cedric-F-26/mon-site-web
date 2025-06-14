# Dossier de journaux (logs)

Ce dossier contient les journaux d'application. Par défaut, il est configuré pour être suivi par Git tout en ignorant les fichiers de log réels.

## Structure des fichiers de log

- `application.log` - Journal principal de l'application
- `security.log` - Événements de sécurité
- `error.log` - Erreurs de l'application
- `access.log` - Journal des accès

## Configuration

Les fichiers de log sont configurés pour être ignorés par Git. Pour ajouter un nouveau type de fichier de log à suivre, modifiez le fichier `.gitignore` à la racine du projet.

## Rotation des logs

Les fichiers de log sont automatiquement gérés par le système de journalisation avec les paramètres suivants :
- Taille maximale par fichier : 10 Mo
- Nombre maximum de fichiers de rotation : 30
- Niveau de journalisation par défaut : INFO

## Nettoyage

Pour nettoyer manuellement les fichiers de log :

```bash
# Vider les fichiers de log actuels
truncate -s 0 logs/*.log

# Supprimer les anciens fichiers de log
find logs/ -name "*.log.*" -type f -delete
```

## Surveillance

Pour surveiller les logs en temps réel :

```bash
# Suivre les nouveaux messages
tail -f logs/application.log

# Filtrer par niveau de sévérité
grep "\[ERROR\]" logs/application.log

# Afficher les erreurs des dernières 24 heures
find logs/ -name "*.log*" -type f -mtime -1 -exec grep -l "\[ERROR\]" {} \; | xargs cat | grep "\[ERROR\]"
```

## Sécurité

- Les fichiers de log ne doivent pas contenir d'informations sensibles
- Les mots de passe et jetons doivent être masqués dans les logs
- L'accès aux fichiers de log doit être restreint aux administrateurs

## Dépannage

Si les fichiers de log ne sont pas créés :
1. Vérifiez les permissions du dossier `logs/`
2. Vérifiez la configuration dans `config/logger.php`
3. Vérifiez l'espace disque disponible
4. Vérifiez les erreurs PHP dans le journal des erreurs du serveur web
