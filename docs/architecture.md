# Architecture

Monolithe Laravel. Les pages sont rendues par Inertia (`Inertia::render`), pas par une API REST séparée. Le front est React, TypeScript et Tailwind. Les tests sont Pest. Les identifiants sont des UUID, sauf le candidat.

## Pourquoi ce découpage

Le score ne doit pas pouvoir être recalculé dans le navigateur. Les poids sont dans `config/scoring.php`. Chaque critère est une classe. `ScoringService` les additionne, écrit une évaluation, et ne touche pas au statut humain.

Les contrôleurs valident par des Form Requests et délèguent aux services. Un contrôleur qui calcule un score est une erreur.

| Couche | Rôle | Exemple |
| --- | --- | --- |
| Form Request | Règles et messages français | `StoreApplicationRequest` |
| Service | Règle métier | `ApplicationService`, `ScoringService` |
| Critère | Un barème, testable seul | `SkillsCriterion` |
| Modèle | Relations et état | `Application`, `Candidate` |
| Page React | Affichage | `resources/js/pages` |

Les types TypeScript du métier sont dans `resources/js/types/talent.ts`. Ils décrivent ce que le serveur envoie. Ils ne recalculent rien.

## Le score est différé

À la soumission, la candidature est `received`. `currentEvaluation` est nul. Le recruteur autorise l'examen (`examination_authorized_at`). `ApplicationReviewService::authorizeExamination` appelle alors `ScoringService::score`.

Raison : un dossier non lu ne doit pas déjà porter un rang. L'autorisation est l'acte qui dit « ce dossier entre dans le classement ». Recalculer plus tard ne revient pas en arrière : `applications:rescore` crée une nouvelle évaluation `is_current = true` et passe l'ancienne à `false`. `rubric_version` (`v2`) est stockée sur chaque évaluation.

## Cible d'une candidature

Une candidature vise exactement une cible : `program_id`, `course_id` ou `offer_id`. Les deux autres restent nuls. L'unicité est `(candidat, cible)`.

`ScoringCatalog` unifie les trois cibles pour le score : nom, texte utilisé pour les mots-clés, compétences, ouverture, lieu de travail, documents demandés. Un programme sans compétences propres reprend celles de ses cours.

## Fichiers

CV et pièces jointes vont sur le disque `local`, hors de `public/`. Un CV de profil est copié vers la candidature : les deux fichiers sont distincts. Le téléchargement passe par une route authentifiée qui vérifie le chemin (`cvs/` ou `documents/`, pas de `..`).

## Ce qui a été refusé

- Pas d'API JSON parallèle. Une seconde surface de score finirait par diverger.
- Pas de colonne de rôle sur `users`. Un compte a plusieurs rôles ; le candidat n'existe qu'après une candidature.
- Pas de calcul de score dans React.
- Pas de donnée sensible dans le score. Le lieu sert à comparer un poste et une déclaration, pas à noter une origine.
