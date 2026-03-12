# Upload des fichiers média (admin)

Si vous voyez **"The media file failed to upload"** ou que l’upload échoue :

## 0. Vérifier les limites PHP

Ouvrez : **https://votre-api.com/admin/upload-limits** (remplacez par l’URL de votre API). Vous y verrez `upload_max_filesize` et `post_max_size`. Il faut au moins **100M** pour les deux.

## 1. Lien de stockage Laravel

Les fichiers sont enregistrés dans `storage/app/public/`. Pour qu’ils soient accessibles en HTTP, créez le lien symbolique :

```bash
cd api
php artisan storage:link
```

Cela crée `public/storage` → `storage/app/public`. Sans ce lien, les fichiers sont enregistrés mais l’URL ne fonctionne pas.

## 2. Limites PHP

L’admin autorise des fichiers jusqu’à **100 Mo**. Vérifiez dans `php.ini` (ou dans la config de votre hébergeur) :

- `upload_max_filesize` = 100M (ou plus)
- `post_max_size` = 100M (ou plus)

**WAMP** : WAMP → PHP → php.ini → mettez `upload_max_filesize = 100M` et `post_max_size = 100M` → enregistrez → Redémarrer tous les services.

## 3. Formulaire

Le formulaire doit avoir `enctype="multipart/form-data"`. Sinon le fichier n’est pas envoyé.

## 4. Permissions

Le dossier `storage/app/public` (et sous-dossiers) doit être accessible en écriture par le serveur web :

- **Linux** : `chmod -R 775 storage bootstrap/cache` et propriétaire correct (www-data ou apache).
- **Windows (WAMP)** : en général pas de blocage si vous lancez en tant qu’admin.

## 5. Messages d’erreur

En cas d’échec, l’admin affiche maintenant un message plus explicite (ex. erreur d’enregistrement ou de permissions). Vérifiez aussi les logs Laravel : `storage/logs/laravel.log`.
