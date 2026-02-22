# Démarrer l'API LASEV

## Méthode simple (recommandée)

1. **Double-cliquez** sur `demarrer-api.bat` dans le dossier `api`.
2. Une fenêtre s’ouvre : l’API tourne quand vous voyez  
   `Server running on [http://127.0.0.1:8000]`.
3. Ouvrez dans le navigateur : **http://127.0.0.1:8000/admin/login**

---

## Si l’API ne démarre pas

### 1. « php n’est pas reconnu » ou commande introuvable

- **Cause :** PHP n’est pas dans le PATH du terminal.
- **Solution :** Utilisez `demarrer-api.bat` (il utilise le PHP de WAMP).  
  Ou ajoutez PHP au PATH Windows :  
  `C:\wamp64\bin\php\php8.5.0` (adapter la version si besoin).

### 2. « Address already in use » ou port 8000 utilisé

- **Cause :** Un autre programme utilise déjà le port 8000.
- **Solution :**
  - Fermez l’autre fenêtre qui lance l’API, ou
  - Utilisez un autre port :  
    `php artisan serve --port=8001`  
    puis ouvrez **http://127.0.0.1:8001**

### 3. Erreur « Class not found » ou erreurs PHP

- **Cause :** Dépendances Composer manquantes ou cache cassé.
- **Solution :** Dans le dossier `api`, exécutez :
  ```bat
  c:\wamp64\bin\php\php8.5.0\php.exe composer install
  ```
  (Adapter le chemin PHP si votre version est différente.)

### 4. Erreur base de données (MySQL)

- **Cause :** MySQL non démarré ou `.env` incorrect.
- **Solution :**
  - Démarrez WAMP (icône verte).
  - Vérifiez dans `api\.env` :  
    `DB_DATABASE=lasev`, `DB_USERNAME=root`, `DB_PASSWORD=` (vide si pas de mot de passe).

### 5. Lancer à la main dans un terminal

Dans un terminal (PowerShell ou CMD), exécutez :

```bat
cd c:\wamp64\www\lasev\api
c:\wamp64\bin\php\php8.5.0\php.exe artisan serve
```

(Remplacez `php8.5.0` par votre version PHP sous WAMP si besoin.)

---

Une fois le message `Server running on [http://127.0.0.1:8000]` affiché, l’API est prête.
