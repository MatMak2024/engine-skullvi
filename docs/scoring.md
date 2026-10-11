# Score v2

Tout est dans `config/scoring.php`. `rubric_version` = `v2`. Changer un poids sans changer cette version mélange des évaluations qui ne sont pas comparables. Après un changement de grille : `php artisan applications:rescore`.

Le total est la somme des critères, arrondie, plafonnée à 100. Le front affiche les lignes renvoyées par le serveur.

## Barème

| Critère | Max | Règle |
| --- | --- | --- |
| Compétences | 30 | Exigées jusqu'à 25, bonus jusqu'à 5. Chaque compétence déclarée est pondérée : débutant 0,6, intermédiaire 0,85, avancé 1. L'auto-évaluation n'est pas prise pour argent comptant |
| Expérience | 20 | 2 pts par an, plafonné à 5 ans (10). 2 pts par projet, plafonné à 5 projets (10) |
| Formation | 15 | Aucun 0, bac 6, licence 10, master 15, autre 8. Plafond volontaire : un profil avec des projets peut compenser |
| Preuves | 15 | GitHub 8 si l'hôte est `github.com`. Portfolio 5 si l'URL est valide. LinkedIn 2 si l'hôte est `linkedin.com`. CV présent 3. Documents demandés jusqu'à 2, au prorata de ceux fournis. La somme brute dépasse 15 : elle est plafonnée. Le contenu n'est pas lu |
| Motivation | 10 | Longueur jusqu'à 6 (≥ 400 caractères : 6, ≥ 200 : 4, ≥ 80 : 2, sinon 0) plus jusqu'à 4 pour les mots du texte de la cible présents dans la lettre. Moins de 80 caractères : drapeau `short_motivation` |
| Disponibilité | 5 | Immédiate 5, sous un mois 3, plus tard 1 |
| Localisation | 5 | Voir plus bas |

Drapeaux possibles : `no_skills`, `invalid_github`, `invalid_portfolio`, `invalid_linkedin`, `short_motivation`, `missing_qualifications`.

## Localisation

Ce critère note la précision du lieu déclaré et, selon le mode de travail, la correspondance avec le lieu du poste. Il ne note pas une origine.

Le lieu du compte (`users`) prime sur celui du profil candidat.

| Mode | Règle |
| --- | --- |
| Télétravail | Pays déclaré 3, ville 1, adresse ou code postal 1. Aucune correspondance exigée |
| Sur place | Pays 2, ville 2, adresse 1, seulement si le lieu correspond. Offre sur place sans pays : même barème que le télétravail |
| Hybride | Pays déclaré 3, ville du lieu de travail 1 si elle correspond, adresse 1 |

## Suggestion

Calculée dans `SuggestedStatus`, après le total.

1. Cible fermée → `ineligible`.
2. Motivation, niveau, disponibilité ou pays absents → `incomplete`.
3. Sinon : `>= 70` → `priority` ; `>= 50` → `to_review` ; sinon `low_fit`.

Cette suggestion n'écrit pas `applications.status`.

## Complétude

Quinze cases : niveau, domaine, années, projets, GitHub, portfolio, LinkedIn, disponibilité, motivation, CV, documents demandés (ou vrai s'il n'y en a pas), téléphone, pays, ville, au moins une compétence. Le taux est le pourcentage de cases remplies. Il n'est pas ajouté au score.

## Classement

Score décroissant. À égalité, points de preuves décroissants. À égalité encore, date de candidature croissante (la plus ancienne d'abord). Un dossier sans évaluation est en bas (score traité comme −1).

## Exemple de référence

Offre sur place à Lomé, candidat à Dakar. Le test `test_reference_profile_scores_65_and_suggests_to_review` verrouille ces chiffres.

| Critère | Points |
| --- | --- |
| Compétences | 24 |
| Expérience | 12 |
| Formation | 10 |
| Preuves | 8 |
| Motivation | 6 |
| Disponibilité | 5 |
| Localisation | 0 |
| **Total** | **65 → à examiner** |

La localisation vaut 0 : le lieu ne correspond pas à un poste sur place.

## Limites assumées

- Les niveaux de compétence sont déclarés. Le coefficient plafonne la confiance, il ne vérifie pas le niveau.
- Les preuves valident un format d'URL et la présence d'un fichier. Pas la qualité.
- La motivation compte des caractères et des mots. Une lettre longue et hors sujet peut marquer des points de longueur. Le poids reste à 10 pour que ce signal ne domine pas.
- Le score est individuel. Il n'y a pas de courbe pour forcer un quota de prioritaires.
