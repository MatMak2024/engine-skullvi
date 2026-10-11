# Données

SQLite. UUID partout (`HasUuids`), sauf `candidates`.

`candidates.user_id` est la clé primaire. Ce n'est pas un `id` séparé. `applications.candidate_id` pointe vers `candidates.user_id`. Un modèle `Candidate` sans cette clé insère un identifiant nul.

## Ordre utile

`users` → `roles` → `permissions` → `permission_role` → `role_user` → `programs` → `courses` → `categories` → `skills` → pivots de compétences → `candidates` → `offers` → `applications` → `evaluations` → `evaluation_scores` → `review_notes`.

Autres tables métier : `saved_openings`, `candidate_diplomas`, `candidate_trainings`, `application_documents`, `notifications`.

## Tables qui portent la décision

**users.** Compte. Prénom, nom, e-mail, téléphone, adresse. Pas de rôle. `candidate_removed_at` retire définitivement le droit de postuler. Le compte utilisateur reste.

**candidates.** Profil professionnel, 1–1 avec l'utilisateur. Téléphone, pays, ville, niveau, domaine, années, projets, liens, disponibilité, intérêts, autres compétences, CV, `suspended_at`. Nom et e-mail restent dans `users`.

**applications.** Une cible parmi programme, cours, offre. Niveau, expérience, projets, liens, disponibilité, motivation, `cv_path`, `status` (défaut `received`), `submitted_at`, `examination_authorized_at`.

**evaluations.** Une passe de score. `rubric_version`, `total_score` 0–100, `completeness_rate` 0–100, `suggested_status`, `flags` JSON, `is_current`. Index `(application_id, is_current)` et `total_score`.

**evaluation_scores.** Une ligne par critère : `skills`, `experience`, `education`, `proofs`, `motivation`, `availability`, `location`. Points, maximum, explication. Unique `(evaluation_id, criterion)`.

**review_notes.** Note d'un utilisateur sur une candidature. C'est la trace humaine, distincte du score.

## Compétences

`skills` n'a pas de clé étrangère vers une catégorie. Le lien est `category_skill`. Une compétence `is_open = false` ne peut plus être ajoutée à un cours, un programme ou une offre.

Pivots :

| Table | Clé | Sens |
| --- | --- | --- |
| `program_skill` | `(program_id, skill_id)` | `is_required` : exigée ou bonus. Sert si le programme n'a pas encore de cours avec prérequis |
| `course_skill` | `(course_id, skill_id)` | Idem |
| `offer_skill` | `(offer_id, skill_id)` | Idem |
| `application_skill` | `(application_id, skill_id)` | Niveau déclaré : `beginner`, `intermediate`, `advanced` |
| `candidate_skill` | `(candidate_id, skill_id)` | Niveau du profil, recopié comme point de départ du formulaire |

## Statuts

Colonne string, enum PHP.

`received`, `incomplete`, `ineligible`, `priority`, `to_review`, `low_fit`, `accepted`, `rejected`.

`applications.status` est la décision en cours. `evaluations.suggested_status` est la proposition du système. Les deux ne sont pas synchronisés. Accepter un dossier ne réécrit pas le score.

## Absent du modèle

Genre, âge, origine. Ni colonne, ni point de score.
