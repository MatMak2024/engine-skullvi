# Talent Engine (SKULLVI / GevConsulting HCP)

> Brouillon de départ. La grille décrite ici est la v1, et le rôle y est encore une colonne de `users`. Le système livré est la v2 : voir `README.md` et `docs/`. Ne pas implémenter depuis ce fichier.

## 1. Objectif

Mini-challenge d'évaluation pratique du programme développeurs SKULLVI (GevConsulting – Human Capital Program). Concevoir une première solution fonctionnelle qui permet de :

recevoir les candidatures → collecter les informations essentielles → qualifier les profils → attribuer un score → classer les candidats → identifier les profils à examiner en priorité.

Ce qui est évalué : compréhension et structuration du problème, organisation des données, logique de qualification et de scoring, solution simple et fonctionnelle, anticipation des erreurs et tests, documentation et explication des choix.

Principe directeur : l'outil aide à décider, il ne décide pas. La décision finale reste humaine (le recruteur peut changer le statut et ajouter des notes).

## 2. Stack et choix techniques

Laravel + React + TypeScript + Tailwind CSS, en projet unique (monolithe) via Inertia.js. Pas d'API REST séparée : les contrôleurs renvoient des pages avec Inertia::render(...).
Base du projet : starter kit React officiel de Laravel (authentification intégrée), tests avec Pest.
Base de données : SQLite (zéro configuration pour les évaluateurs).
Identifiants : UUID partout (trait HasUuids), sauf Candidate (voir section 4).
Interface utilisateur et messages en français ; code, noms de tables, colonnes, classes et variables en anglais.

## 3. Règles de développement

Contrôleurs minces : validation dans des Form Requests, logique métier dans des Services / Actions.
Le scoring se calcule uniquement côté serveur. Le front affiche, il ne calcule jamais.
Les poids, seuils et la version de la grille vivent dans config/scoring.php, jamais codés en dur.
Chaque critère de scoring est une classe indépendante et testable seule, orchestrée par un ScoringService.
Valeurs fixes (statuts, niveaux, disponibilités) : enums PHP, stockées en colonnes string.
Types TypeScript partagés dans resources/js/types/ qui reflètent les modèles Laravel.
Aucune donnée sensible (genre, âge, origine) dans le modèle ni dans le calcul du score.
Validation toujours côté serveur (jamais uniquement côté React), messages d'erreur en français.
Petits commits fréquents, un par étape.

## 4. Schéma de base de données (UUID)

Ordre des migrations (une table qui en référence une autre vient après elle) :

1. users (kit, modifiée)
2. programs
3. skill_categories
4. skills
5. candidates
6. applications
7. evaluations
8. evaluation_scores
9. review_notes
10. program_skill (pivot)
11. application_skill (pivot)

### users (fournie par le kit, modifiée)

- id uuid PK (remplace l'id numérique ; sessions.user_id est aussi en foreignUuid)
- name, email (unique), email_verified_at, password, remember_token, timestamps
- role string, défaut candidate, indexé. Valeurs : candidate, recruiter

### programs

- id uuid PK, name, slug (unique), description (nullable), is_open boolean (défaut true, indexé), timestamps

### skill_categories

- id uuid PK, name (unique), slug (unique), timestamps

### skills

- id uuid PK
- skill_category_id foreignUuid nullable → skill_categories (nullOnDelete)
- name (unique), timestamps

### candidates (profil 1-1 avec users)

- user_id foreignUuid → users et clé primaire (cascadeOnDelete)
- phone (nullable), country, city (nullable), timestamps
- Le nom et l'email du candidat sont dans users (pas de duplication).

### applications

- id uuid PK
- candidate_id foreignUuid → candidates.user_id (constrained('candidates', 'user_id'), cascadeOnDelete)
- program_id foreignUuid → programs (cascadeOnDelete)
- education_level string : none, bac, licence, master, other
- field_of_study (nullable)
- years_experience unsignedTinyInteger (défaut 0), projects_count unsignedTinyInteger (défaut 0)
- github_url, portfolio_url, linkedin_url (nullables)
- availability string : immediate, one_month, later
- motivation_text text, cv_path (nullable, fichier stocké hors dossier public)
- status string, défaut received, indexé. Valeurs : received, incomplete, ineligible, priority, to_review, low_fit, accepted, rejected
- submitted_at timestamp (défaut now), timestamps
- Unique sur (candidate_id, program_id) : une candidature par programme et par personne.

### evaluations (résultat du scoring, historique conservé)

- id uuid PK, application_id foreignUuid (cascadeOnDelete)
- rubric_version string, total_score unsignedTinyInteger (0 à 100), completeness_rate unsignedTinyInteger (0 à 100)
- suggested_status string, flags json nullable, is_current boolean (défaut true), computed_at timestamp, timestamps
- Index : (application_id, is_current) et total_score

### evaluation_scores (détail par critère, base de l'explicabilité)

- id uuid PK, evaluation_id foreignUuid (cascadeOnDelete)
- criterion string : skills, experience, education, proofs, motivation, availability
- points decimal(5,2), max_points decimal(5,2), explanation string, timestamps
- Unique sur (evaluation_id, criterion)

### review_notes (décision humaine)

- id uuid PK, application_id foreignUuid (cascadeOnDelete), user_id foreignUuid → users, note text, timestamps

### program_skill (pivot)

- program_id, skill_id (foreignUuid, cascadeOnDelete), is_required boolean (défaut true : true = requise, false = bonus)
- Clé primaire composée (program_id, skill_id)

### application_skill (pivot)

- application_id, skill_id (foreignUuid, cascadeOnDelete), level string : beginner, intermediate, advanced (défaut beginner)
- Clé primaire composée (application_id, skill_id)

## 5. Modèles Eloquent : points d'attention

HasUuids sur : User, Program, SkillCategory, Skill, Application, Evaluation, EvaluationScore, ReviewNote.
Candidate : pas de HasUuids. Déclarer $primaryKey = 'user_id', $incrementing = false, $keyType = 'string'.

Relations :

- User hasOne Candidate
- Candidate belongsTo User, hasMany Application (clé candidate_id, clé locale user_id)
- Program belongsToMany Skill (pivot program_skill, withPivot('is_required')), avec des helpers requiredSkills() et bonusSkills(); hasMany Application
- SkillCategory hasMany Skill; Skill belongsTo SkillCategory
- Application belongsTo Candidate, Program; belongsToMany Skill (pivot application_skill, withPivot('level')); hasMany Evaluation; hasMany ReviewNote; helper currentEvaluation() (évaluation avec is_current = true)
- Evaluation belongsTo Application, hasMany EvaluationScore
- ReviewNote belongsTo Application, User

Le champ role de User ne doit jamais être dans $fillable (il ne doit pas être modifiable depuis un formulaire).

## 6. Rôles, accès et parcours

L'inscription publique est ouverte et crée toujours un utilisateur candidate.
Les recruteurs sont créés uniquement par seeder (role = recruiter), avec un compte de démo documenté dans le README.
L'espace recruteur est protégé par un middleware de rôle.
Parcours candidat : inscription → compléter son profil (candidates) → postuler à un programme (applications) → voir le statut de sa candidature.
Parcours recruteur : liste classée → filtres (programme, statut) → fiche détail avec décomposition du score → changement manuel de statut et notes.
Anti-spam : limitation de débit (rate limiting) sur le formulaire de candidature.

## 7. Logique de scoring (grille v1, à calibrer par les tests)

Le score est calculé sur 100, avec trois notions bien séparées :

- Éligibilité : critères éliminatoires. Programme fermé, champs obligatoires manquants, etc. Cela ne donne pas 0 mais un statut incomplete ou ineligible.
- Adéquation : le score /100.
- Fiabilité des données : completeness_rate (champs renseignés sur champs attendus). Un score de 75 sur un dossier complet n'a pas la même valeur qu'un 75 sur un dossier à moitié vide.

### Grille (proposition, barèmes modifiables dans config/scoring.php)

| Critère | Points | Règle |
| --- | --- | --- |
| skills | 35 | Compétences requises couvertes (jusqu'à 25) + compétences bonus (jusqu'à 10). Le niveau déclaré module chaque compétence avec un plafond (l'auto-évaluation est peu fiable) : beginner 0.6, intermediate 0.85, advanced 1.0 |
| experience | 20 | Années : 2 pts/an, plafonné à 5 ans (max 10). Projets : 2 pts/projet, plafonné à 5 (max 10) |
| education | 15 | Volontairement plafonné pour qu'un profil autodidacte avec de vrais projets puisse compenser. Barème par education_level |
| proofs | 15 | Présence et format valide des liens (GitHub 8, portfolio 5, LinkedIn 2). On ne vérifie pas la qualité du contenu au MVP (à dire honnêtement dans la doc) |
| motivation | 10 | Critère fragile, poids faible : longueur du texte + pertinence par mots-clés du programme. Sert surtout de signal à relire par un humain |
| availability | 5 | immediate 5, one_month 3, later 1 |

### Statuts suggérés (seuils configurables)

- total_score >= 70 → priority
- 50 <= total_score < 70 → to_review
- total_score < 50 → low_fit
- Éligibilité non remplie → incomplete ou ineligible

### Autres règles

Explicabilité : chaque critère produit une ligne dans evaluation_scores avec points, max_points et une explanation lisible (ex. « 3 compétences requises sur 4 »).
Deux statuts distincts : evaluations.suggested_status (proposé par le système) et applications.status (décision actuelle, modifiable par un recruteur).
Versionnage : chaque évaluation enregistre rubric_version. Après un changement de grille, on peut recalculer (nouvelle évaluation is_current = true, l'ancienne passe à false).
Départage en cas d'égalité : preuves vérifiables d'abord, puis date de candidature.
Flags (dans evaluations.flags) : lien invalide, motivation trop courte, aucune compétence déclarée, etc.
Scoring synchrone à la soumission pour le MVP.

### Exemple de référence (illustratif, à utiliser dans les tests et le README)

| Critère | Détail | Points |
| --- | --- | --- |
| skills | 3 requises sur 4 + 1 bonus | 24 / 35 |
| experience | 2 ans, 2 projets | 12 / 20 |
| education | Licence en informatique | 10 / 15 |
| proofs | GitHub valide, pas de portfolio | 8 / 15 |
| motivation | Texte pertinent, longueur correcte | 6 / 10 |
| availability | Immediate | 5 / 5 |
| Total | | 65 / 100 → to_review |

## 8. Structure de code visée

```
app/
  Enums/                     ← ApplicationStatus, EducationLevel, Availability, SkillLevel, Role
  Http/Controllers/          ← ApplicationController (candidat), RecruiterApplicationController
  Http/Middleware/           ← EnsureUserIsRecruiter
  Http/Requests/             ← StoreCandidateProfileRequest, StoreApplicationRequest
  Services/Scoring/          ← ScoringService + une classe par critère
  Models/
config/scoring.php           ← poids, seuils, barèmes, rubric_version
database/seeders/            ← RecruiterSeeder, ProgramSeeder, SkillSeeder, DemoCandidatesSeeder
resources/js/pages/          ← apply, profile, applications/, recruiter/applications/ (index, show)
resources/js/types/          ← types partagés (Application, Evaluation, EvaluationScore, Status…)
tests/                       ← Pest (Unit pour le scoring, Feature pour les parcours)
```

## 9. Plan d'implémentation (ordre conseillé)

1. Vérifier les migrations avec php artisan migrate:fresh puis php artisan db:show --counts.
2. Modèles + relations + enums.
3. Seeders (recruteur de démo, programme, catégories et compétences, candidats fictifs).
4. config/scoring.php + ScoringService + critères (avec leurs tests unitaires).
5. Middleware de rôle + routes.
6. Pages candidat : profil, formulaire de candidature, suivi du statut.
7. Pages recruteur : liste classée avec filtres, fiche détail avec décomposition du score, changement de statut, notes.
8. Tests fonctionnels (soumission valide, invalide, doublon, accès recruteur protégé).
9. README et documentation.

## 10. Tests à prévoir

Unitaires : chaque critère de scoring ; score global avec un profil parfait, un profil vide et des cas limites (plafonds, liens invalides) ; départage.
Fonctionnels : inscription crée toujours un candidate ; un candidat ne peut pas accéder à l'espace recruteur ; soumission valide ; soumission invalide ; doublon (même programme) refusé ; recalcul de la grille.

## 11. Documentation attendue (README)

- Compréhension du problème et acteurs
- Hypothèses et périmètre (MVP et hors périmètre)
- Modèle de données
- Logique de scoring et justification (équité, plafonds, explicabilité)
- Architecture (Laravel + Inertia/React/TS/Tailwind)
- Gestion des erreurs et stratégie de tests
- Lancer le projet (avec identifiants de démo)
- Limites et évolutions : analyse de CV, vérification réelle du GitHub, IA, emails automatiques, import en masse, scoring configurable par programme

## 12. Pièges connus

- applications.candidate_id référence candidates.user_id (pas candidates.id, qui n'existe pas).
- Avec des UUID, un modèle sans HasUuids plante à l'insertion (id nul). Les seeders ne fonctionnent qu'après configuration des modèles.
- Le rôle ne doit jamais être modifiable depuis l'inscription.
- Si npm run build expire à cause du téléchargement de polices (laravel:fonts) : lancer avec NODE_OPTIONS="--dns-result-order=ipv4first --no-network-family-autoselection" ou utiliser une police système.
