# Talent Engine

Outil de réception et de classement des candidatures SKULLVI. Il collecte un dossier, le qualifie, lui attribue un score sur 100, classe les candidats et signale ceux à examiner en premier.

Le score aide. Il ne décide pas. Le statut d'une candidature est posé par un recruteur.

## Le problème

SKULLVI reçoit des candidatures pour des programmes, des cours et des offres. Sans outil, le tri repose sur la lecture dans l'ordre d'arrivée. Deux dossiers égaux en apparence ne le sont pas : l'un est complet, l'autre non ; l'un a des preuves vérifiables, l'autre non.

L'outil répond à une seule question : **qui lire en premier, et pourquoi**.

Trois notions sont séparées. Les mélanger fausse le classement.

| Notion | Question | Où ça vit |
| --- | --- | --- |
| Éligibilité | Le dossier peut-il être noté ? | `incomplete` ou `ineligible` |
| Adéquation | À quel point le profil colle à la cible ? | `total_score` de 0 à 100 |
| Fiabilité | Le dossier est-il renseigné ? | `completeness_rate` de 0 à 100 |

Un 75 sur un dossier plein n'a pas la même valeur qu'un 75 sur un dossier vide. Le taux de complétude est affiché à côté du score. Il n'entre pas dans le total.

## Acteurs

| Rôle | Fait | Ne fait pas |
| --- | --- | --- |
| Utilisateur | Crée un compte, consulte le catalogue, prépare un profil | N'examine personne |
| Candidat | Postule. Le rôle est ajouté au moment de la candidature, pas à l'inscription | Ne change pas un statut |
| Recruteur | Crée cours, programmes, offres et compétences. Autorise l'examen. Change le statut. Écrit une note | Ne gère pas les comptes |
| Super administrateur | Attribue les rôles. Consulte les dossiers | Ne change pas un statut et ne modifie pas le catalogue |

Il n'y a pas de colonne `users.role`. Les rôles sont dans `roles`, reliés par `role_user`, et les droits par `permission_role`.

## Périmètre

Dedans :

- catalogue public : cours, programmes, offres ;
- profil professionnel (coordonnées, parcours, diplômes, formations, compétences, CV) ;
- candidature vers un programme, un cours ou une offre, une seule fois par cible ;
- score serveur, historique des grilles, classement, décision humaine, notes ;
- notifications internes à la soumission et au changement de statut.

Dehors, volontairement :

- lecture du contenu d'un CV ou d'un dépôt GitHub ;
- âge, genre, origine : absents du modèle et du score ;
- la localisation compare le lieu de travail de la cible au lieu déclaré. Ce n'est pas un score d'origine ;
- e-mails transactionnels de recrutement, IA, import en masse, score configurable par programme.

## Lancer

PHP 8.3, Composer, Node.js. Base SQLite, aucun serveur de base à installer.

```bash
composer setup
php artisan migrate:fresh --seed
composer dev
```

`composer setup` copie `.env`, génère la clé, migre et construit le front. `composer dev` lance l'application et Vite. L'adresse par défaut est `http://localhost:8000`.

Mot de passe de tous les comptes de démo : `password`.

| Compte | Rôle |
| --- | --- |
| `owner@talent-engine.test` | Super administrateur |
| `admin@talent-engine.test` | Recruteur |
| `user@talent-engine.test` | Utilisateur |
| `amina@talent-engine.test` | Candidat (dossier de démo) |
| `luc@talent-engine.test` | Candidat |
| `sara@talent-engine.test` | Candidat |

Tests : `php artisan test`.

## Parcours en une minute

1. Le candidat envoie un dossier. Le statut humain reste `received`. Aucun score n'existe encore.
2. Le recruteur ouvre le dossier et autorise l'examen. Le score est calculé à ce moment-là.
3. Le classement place en tête le score le plus haut, puis les preuves, puis la candidature la plus ancienne.
4. Suggestion : `>= 70` prioritaire, `50–69` à examiner, `< 50` adéquation faible. Cible fermée : non éligible. Dossier incomplet : incomplet.
5. Le recruteur accepte, refuse ou laisse en revue, et peut écrire une note. La suggestion ne change pas.

Le détail est dans `docs/`.

| Document | Contenu |
| --- | --- |
| [docs/architecture.md](docs/architecture.md) | Décisions techniques et structure du code |
| [docs/donnees.md](docs/donnees.md) | Tables, clés, ce qui est stocké et ce qui ne l'est pas |
| [docs/scoring.md](docs/scoring.md) | Grille v2, règles, exemple 65/100 |
| [docs/parcours.md](docs/parcours.md) | Droits, écrans, erreurs prévues |

`CONTEXT.md` est le brouillon de départ (grille v1, colonne `users.role`). Il n'est plus la spec. La spec, c'est ce dépôt.
