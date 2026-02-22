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

## Erreur "Stripe not configured"

Si l'app affiche cette erreur, `STRIPE_SECRET_KEY` n'est pas défini ou est vide dans le `.env` du serveur.
