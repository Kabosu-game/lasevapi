# Configuration Stripe pour LASEV

## Obligatoire pour que les paiements fonctionnent

Sur le serveur de l'API (lasevapi.o-sterebois.fr), configurez les variables d'environnement suivantes dans `.env` :

```
STRIPE_SECRET_KEY=sk_live_xxxxxxxxxxxx
STRIPE_PUBLIC_KEY=pk_live_xxxxxxxxxxxx
```

## Où obtenir les clés Stripe

1. Connectez-vous à [Stripe Dashboard](https://dashboard.stripe.com/)
2. Allez dans **Développeurs** → **Clés API**
3. Copiez :
   - **Clé secrète** (sk_live_...) → `STRIPE_SECRET_KEY`
   - **Clé publishable** (pk_live_...) → `STRIPE_PUBLIC_KEY`

## Mode test vs production

- **Mode test** : utilisez `sk_test_...` et `pk_test_...` pour les tests
- **Mode production** : utilisez `sk_live_...` et `pk_live_...` pour les vrais paiements

## Vérification

Après configuration :

1. Redémarrez le serveur Laravel / PHP
2. Testez un paiement depuis l'app Flutter
3. Vérifiez les logs : `storage/logs/laravel.log`

## Erreur 500 / 503 sur create-stripe-payment-intent

Si l'app affiche une erreur lors du paiement :

1. **Vérifier le `.env` sur le serveur** (lasevapi.o-sterebois.fr) :
   ```bash
   STRIPE_SECRET_KEY=sk_live_...
   STRIPE_PUBLIC_KEY=pk_live_...
   ```

2. **Redémarrer PHP/Laravel** après modification du `.env`

3. **Vider le cache** :
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Vérifier les logs** : `storage/logs/laravel.log`

## CORS (erreur "blocked by CORS policy")

Si l'app Flutter Web (localhost ou autre origine) ne peut pas appeler l'API :

1. **Vider le cache** sur le serveur :
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. Les origines autorisées sont : `localhost`, `127.0.0.1` (tous ports), `*.o-sterebois.fr`
