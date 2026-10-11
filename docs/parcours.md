# Parcours, droits, erreurs

## Utilisateur et candidat

L'inscription crée un utilisateur de rôle `user`. Elle envoie un lien pour choisir le mot de passe. Poser ce mot de passe confirme l'e-mail. Le rôle `candidate` est attaché à la première candidature, pas avant.

Le profil professionnel peut être enregistré avant de postuler. Il préremplit le formulaire : coordonnées, parcours, compétences, et le CV si la case « utiliser le CV du profil » est cochée. Diplômes, formations et intérêts restent sur le profil et sont montrés au recruteur. La lettre de motivation n'est pas dans le profil : elle appartient à une candidature.

Mettre une offre, un cours ou un programme de côté crée une ligne `saved_openings`. Postuler retire cette ligne.

Un candidat suspendu ne postule plus et ne modifie plus son profil. Un compte retiré (`candidate_removed_at`) ne pourra plus jamais postuler. Le compte utilisateur reste actif.

## Recruteur

Il publie et suspend cours, programmes, offres et compétences. Il ne supprime pas une cible qui a déjà des candidatures, ni un programme qui a encore des cours.

Sur un dossier : autoriser l'examen, lire le score et ses lignes, changer le statut, ajouter une note. Le super administrateur voit les mêmes fiches. Il ne statue pas et ne modifie pas le catalogue. Il attribue les rôles.

## Droits

| Permission | Recruteur | Super administrateur |
| --- | --- | --- |
| `application:view` | oui | oui |
| `application:update-status` | oui | non |
| `review-note:create` | oui | non |
| `program:manage`, `skill:manage` | oui | non |
| `user:manage` | non | oui |

Un recruteur sans la permission reçoit 403. Un candidat qui poste un statut ou une note reçoit 403.

## Erreurs prévues

| Cas | Réponse |
| --- | --- |
| Champ invalide | 422, message français, le formulaire reste affiché |
| Deuxième candidature sur la même cible | Rejet de validation |
| Cible fermée | Pas de formulaire de candidature |
| Plus de 5 envois par minute et par compte | 429 (`throttle:applications`) |
| CV ou document hors du préfixe attendu, ou chemin avec `..` | 404 |
| Examen déjà autorisé | Pas de second score : la date existante est conservée |
| Changement de grille | Nouvelle évaluation courante, l'ancienne reste en historique |

Les statuts, niveaux et disponibilités sont des enums PHP stockés en string. Une valeur inconnue ne passe pas la Form Request.

## Notifications

Base de données, pas d'e-mail métier. À la soumission : le candidat et les comptes recruteur ou super administrateur. Au changement de statut : le candidat. Le renvoi de vérification d'e-mail ne part que si le compte a déjà un mot de passe. L'inscription, elle, n'envoie que le lien de mot de passe.

## Tests

`php artisan test`.

Les tests unitaires de `tests/Unit/Scoring` verrouillent la grille, dont l'exemple 65/100. Les tests de fonction couvrent la soumission, l'examen différé, le refus d'un doublon, les rôles, le CV, les notifications, la suspension et le retrait d'un candidat. Un test qui recalcule le score dans le navigateur n'a pas lieu d'être : le serveur est la seule source.
