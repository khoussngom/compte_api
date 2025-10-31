# Documentation de l'API — Compte API

Ce dépôt expose une documentation OpenAPI (Swagger) statique générée dans `public/docs/openapi.json`.

Visualiser la documentation
- Ouvrez dans le navigateur : http://localhost:8000/docs/
  - la page `public/docs/index.html` charge `public/docs/openapi.json` via Swagger UI.

Mettre à jour la documentation OpenAPI
- Si vous maintenez des annotations @OA dans le code, vous pouvez tenter de régénérer `public/docs/openapi.json` avec `zircote/swagger-php` (si installé) :

```bash
# depuis la racine du projet
./vendor/bin/openapi -o public/docs/openapi.json app app/Docs/Swagger --format json --bootstrap vendor/autoload.php
```

- Dans ce projet nous conservons une version manuelle/curatée de `public/docs/openapi.json`. Pour des modifications rapides, éditez directement ce fichier JSON puis rechargez la page `/docs/`.

Bonnes pratiques pour régénérer automatiquement
- Lancez la commande ci‑dessus avec `--debug` pour voir quels fichiers sont analysés.
- Assurez-vous que les fichiers contenant les annotations sont autoloadables (namespaces valides) et que `vendor/autoload.php` est passé en `--bootstrap`.

Sécurité et accès
- Si l'API est protégée par des tokens, Swagger UI affichera les schémas de sécurité si présents dans `openapi.json`. Pour tester des endpoints protégés, fournissez un header `Authorization: Bearer <token>` dans l'interface Swagger.

----

# Exécuter les migrations sans supprimer les données

Souvent on veut "refaire" les migrations (pour mettre à jour le schéma) sans perdre les données en production. Attention : certaines modifications de schéma sont destructrices (changement de type, suppression de colonne, etc.). Voici des conseils sûrs :

1. Sauvegarde
   - Toujours faire une sauvegarde avant toute opération : dump SQL ou snapshot de la base.
   - Exemple Postgres (neon/remote) :
     - Utilisez l'outil du provider (ex: Neon/Atlas) ou `pg_dump` si accessible.

2. Ne PAS utiliser `migrate:fresh` ou `migrate:refresh` en production
   - Ces commandes suppriment et recréent les tables (données perdues).

3. Procédure recommandée
   - Créez une nouvelle migration qui contient uniquement les changements nécessaires (ALTER TABLE, ADD COLUMN, CREATE INDEX...).
     ```bash
     php artisan make:migration add_field_x_to_comptes_table --table=comptes
     ```
   - Éditez la migration pour écrire des opérations non destructrices (ajout de colonnes nullable, index, etc.).
   - Exécutez les migrations :
     ```bash
     php artisan migrate
     ```

4. Si vous devez synchroniser anciennes migrations (réorganisation)
   - Ne réécrivez pas l'historique des migrations déjà appliquées. Si vous voulez regrouper/optimiser, ajoutez de nouvelles migrations qui compensent et conservent les données.

5. Pour développement local (si vous acceptez perdre les données de dev)
   - `php artisan migrate:fresh --seed`

6. Automatisation / Vérification
   - Ajoutez des migrations petites, testez-les en staging, et exécutez `php artisan migrate:status` pour vérifier l'état.

----

Si vous voulez, je peux :
- ajouter un test d'intégration (PHPUnit) qui valide la création d'une transaction et la présence du document dans Mongo (si `MONGO_URI` est défini),
- ou parcourir le schéma actuel et proposer des migrations non-destructives pour harmoniser les tables.

Indiquez la ou les actions que vous souhaitez que j'exécute ensuite.
