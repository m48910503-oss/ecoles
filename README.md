# ERP SCOLAIRE SAAS — CAHIER TECHNIQUE & INSTRUCTIONS CODEX

## 1. Vision du projet

Ce projet est une plateforme SaaS de gestion complète d’établissement scolaire, conçue pour les écoles privées et publiques d’Afrique francophone, avec une attention particulière aux réalités du Bénin.

Le système doit permettre à une école de gérer, de manière centralisée et sécurisée :

- les utilisateurs ;
- les rôles et permissions ;
- les élèves ;
- les parents ;
- le personnel ;
- les inscriptions ;
- les classes ;
- les matières ;
- les emplois du temps ;
- les évaluations ;
- les notes ;
- les bulletins ;
- les absences ;
- la discipline ;
- les finances ;
- les communications ;
- les archives ;
- les statistiques.

Le système fonctionne d’abord en **mode mono-établissement**.

Le mode multi-établissement pourra être prévu plus tard, mais il ne doit pas compliquer la première version. Toute fonctionnalité multi-école doit rester désactivée par défaut.

---

## 2. Rôle attendu de Codex

Codex doit agir comme un ingénieur SaaS senior avec plus de 15 ans d’expérience dans :

- Laravel ;
- architecture logicielle ;
- sécurité applicative ;
- ERP métier ;
- plateformes scolaires ;
- systèmes multi-rôles ;
- conception de bases de données relationnelles ;
- interfaces administratives professionnelles.

Codex ne doit pas simplement “faire marcher le code”.

Codex doit construire une base :

- propre ;
- sécurisée ;
- maintenable ;
- évolutive ;
- testable ;
- cohérente métier ;
- compréhensible par un autre développeur.

Chaque décision technique doit respecter la logique métier scolaire déjà définie.

---

## 3. Stack technique obligatoire

Le projet doit être construit avec :

- Laravel dernière version stable disponible ;
- PHP compatible avec cette version Laravel ;
- MySQL/MariaDB ou PostgreSQL ;
- Blade Laravel ;
- Tailwind CSS ;
- JavaScript moderne ;
- Vite ;
- Composer ;
- NPM.

Interdictions techniques pour la version initiale :

- pas de React ;
- pas de Vue ;
- pas de TypeScript ;
- pas d’Inertia ;
- pas de SPA ;
- pas de framework frontend lourd ;
- pas de logique métier dans les fichiers Blade ;
- pas de logique métier massive dans les contrôleurs.

Le frontend doit rester simple :

- Blade ;
- Tailwind CSS ;
- JavaScript léger ;
- composants Blade réutilisables.

### 3.1 Mise en route locale sous XAMPP

Pour travailler en local sur Windows, l'environnement de référence est :

- XAMPP avec Apache + MariaDB ;
- base principale `ecoles` ;
- base de test `ecoles_test`.

Étapes recommandées :

1. Créer les deux bases dans phpMyAdmin ou via le client MariaDB de XAMPP.
2. Copier `.env.example` vers `.env`.
3. Vérifier au minimum ces variables :

```env
APP_URL=http://localhost/ecoles/public
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecoles
DB_USERNAME=root
DB_PASSWORD=
```

4. Exécuter `composer install`, `npm install`, puis `php artisan key:generate`.
5. Lancer `php artisan migrate --seed` sur la base `ecoles`.
6. Lancer la suite de tests avec `php artisan test`.

Notes importantes :

- `phpunit.xml` pointe sur `ecoles_test`, donc cette base doit aussi exister en local.
- la compatibilité validée du projet repose sur MySQL/MariaDB sous XAMPP pour l'environnement local.
- si tu changes de moteur de base de données, il faut vérifier les migrations et la suite de tests avant d'aller plus loin.

---

## 4. Principes d’architecture obligatoires

Le projet doit respecter une architecture Laravel propre.

### 4.1 Structure attendue

La logique doit être organisée autour de :

- Models ;
- Controllers ;
- Form Requests ;
- Services ;
- Policies ;
- Observers si nécessaire ;
- Events et Listeners si nécessaire ;
- Jobs si traitement long ;
- Seeders ;
- Factories ;
- Tests.

### 4.2 Règle non négociable

Les contrôleurs ne doivent pas contenir la logique métier lourde.

Un contrôleur doit :

- recevoir la requête ;
- appeler un Form Request ;
- appeler un Service ;
- retourner une vue ou une redirection.

La logique métier doit être dans des classes Service.

Exemple attendu :

```php
app/
├── Services/
│   ├── Core/
│   ├── Academic/
│   ├── People/
│   ├── Enrollment/
│   ├── Finance/
│   ├── Exams/
│   ├── Reports/
│   └── Documents/
```

---

## 5. Règles générales de qualité

Codex doit toujours :

- écrire du code clair ;
- utiliser des noms explicites ;
- valider toutes les entrées utilisateur ;
- protéger toutes les routes sensibles ;
- gérer les erreurs proprement ;
- utiliser les relations Eloquent correctement ;
- ajouter des index utiles en base de données ;
- utiliser les Soft Deletes pour les données sensibles ;
- journaliser les actions critiques ;
- prévoir les seeders nécessaires ;
- créer des tests pour les règles critiques ;
- respecter les conventions Laravel.

Codex ne doit jamais :

- créer des tables inutiles ;
- mélanger plusieurs responsabilités dans une même classe ;
- contourner les permissions ;
- supprimer définitivement une donnée critique sans justification ;
- mettre des libellés d’interface en anglais ;
- générer du code temporaire laissé dans le projet ;
- développer plusieurs modules en désordre ;
- créer des migrations incohérentes ;
- générer des noms ambigus comme `data`, `info`, `item`, `thing`.

---

## 6. Langue et interface

Toute l’interface utilisateur doit être en français correct.

Les textes doivent être :

- simples ;
- professionnels ;
- sans faute ;
- adaptés à un établissement scolaire ;
- compréhensibles par des utilisateurs non techniques.

Exemples :

Correct :

- “Élève enregistré avec succès.”
- “La demande d’inscription est en cours de vérification.”
- “Vous n’avez pas l’autorisation d’effectuer cette action.”

Incorrect :

- “Student created.”
- “OK done.”
- “Error.”
- “Action failed.”

---

## 7. Sécurité globale

La sécurité doit être intégrée dès le départ.

### 7.1 Authentification

Le système doit gérer :

- connexion ;
- déconnexion ;
- mot de passe sécurisé ;
- réinitialisation du mot de passe ;
- session utilisateur ;
- compte actif ou suspendu.

### 7.2 Autorisation

Le système doit utiliser :

- rôles ;
- permissions ;
- policies Laravel ;
- middleware ;
- vérification côté backend.

Une interface ne doit jamais afficher une action interdite à l’utilisateur connecté.

Même si l’action est masquée côté interface, elle doit aussi être bloquée côté backend.

### 7.3 Audit

Toutes les actions sensibles doivent être enregistrées :

- création ;
- modification ;
- suppression logique ;
- validation ;
- rejet ;
- publication ;
- paiement ;
- correction de note ;
- génération de bulletin ;
- modification de permission ;
- changement de configuration.

Chaque log doit contenir :

- utilisateur ;
- action ;
- module ;
- entité concernée ;
- ancienne valeur si applicable ;
- nouvelle valeur si applicable ;
- date et heure ;
- adresse IP si disponible.

---

## 8. Rôles principaux du système

Les rôles de base sont :

1. Administrateur système
2. Directeur
3. Directeur de section
4. Censeur
5. Surveillant général
6. Secrétaire
7. Comptable
8. Enseignant
9. Parent
10. Élève
11. Responsable salle informatique

Rôles optionnels activables :

- Censeur adjoint ;
- Surveillant adjoint ;
- Directeur adjoint ;
- Responsable présence enseignants.

L’administrateur peut activer ou désactiver les rôles optionnels selon l’école.

Le directeur peut recevoir certaines permissions de l’administrateur en cas de besoin, mais toute délégation doit être tracée.

---

## 9. Ordre obligatoire de développement

Codex doit développer les modules dans cet ordre :

1. Core System
2. Référentiel Académique
3. Gestion des Personnes
4. Inscriptions & Scolarité
5. Infrastructure Scolaire
6. Emplois du Temps
7. Pédagogie
8. Évaluations
9. Bulletins & Résultats
10. Absences & Discipline
11. Finances & Comptabilité
12. Communication & Alertes
13. Archivage & Documents
14. Analytics & Statistiques

Ne jamais commencer un module dépendant avant que ses dépendances principales soient prêtes.

---

# 10. MODULE 1 — CORE SYSTEM

## Objectif

Créer le socle technique du système.

Ce module gère :

- utilisateurs ;
- rôles ;
- permissions ;
- authentification ;
- configuration globale ;
- année académique active ;
- journal d’audit ;
- sécurité.

## Entités principales

- users
- roles
- permissions
- role_user
- permission_role
- academic_years
- system_settings
- audit_logs

## Règles métier

- Un utilisateur peut avoir plusieurs rôles.
- Un rôle contient plusieurs permissions.
- Une permission représente une action précise.
- Une seule année académique peut être active.
- Un utilisateur suspendu ne peut pas se connecter.
- Toute action sensible doit être journalisée.
- Le menu affiché dépend des permissions.

## Permissions de base

Exemples :

- core.users.view
- core.users.create
- core.users.update
- core.users.suspend
- core.roles.manage
- core.permissions.manage
- core.settings.manage
- core.audit.view

---

# 11. MODULE 2 — RÉFÉRENTIEL ACADÉMIQUE

## Objectif

Définir la structure académique et les calendriers scolaires de l’école.

Ce module gère :

- cycles ;
- niveaux ;
- classes ;
- matières ;
- coefficients ;
- calendriers scolaires (périodes + événements) ;
- événements pédagogiques ;
- matière Conduite.

## Règles métier

- Les cycles possibles sont : Maternelle, Primaire, Secondaire.
- La série existe uniquement au secondaire, à partir du second cycle.
- Une matière peut être obligatoire ou optionnelle.
- Les coefficients peuvent varier selon le niveau.
- La matière Conduite est optionnelle.
- Si la Conduite est activée, sa note de base peut varier selon le niveau.
- Seules les retenues et colles diminuent la note de conduite.
- Un calendrier scolaire regroupe ses périodes et ses événements.
- Les calendriers peuvent être différents selon la portée : global, cycle, niveau ou classe.

## Entités principales

- cycles
- levels
- classes
- series
- subjects
- subject_coefficients
- academic_calendars
- academic_periods
- calendar_events
- conduct_rules

---

# 12. MODULE 3 — GESTION DES PERSONNES

## Objectif

Centraliser tous les profils humains du système.

Ce module gère :

- élèves ;
- parents ;
- enseignants ;
- personnel administratif.

## Règles métier

- Chaque élève possède un dossier complet.
- Chaque parent peut être lié à plusieurs enfants.
- Chaque enseignant peut enseigner une ou plusieurs matières selon la politique de l’école.
- Le multi-matières est autorisé mais reste exceptionnel.
- Un membre du personnel peut avoir plusieurs rôles.
- Les modifications importantes doivent être historisées.

## Entités principales

- students
- parents
- student_parent
- staff
- teachers
- staff_documents
- student_documents

---

# 13. MODULE 4 — INSCRIPTIONS & SCOLARITÉ

## Objectif

Gérer l’entrée officielle d’un élève dans l’école.

## Workflow

Demande parent  
→ contrôle anti-doublon  
→ vérification secrétaire  
→ validation directeur ou censeur selon cycle  
→ création dossier élève  
→ paiement minimum  
→ activation élève  
→ affectation classe

## Règles métier

- Une seule demande par élève et par année académique.
- Le même parent peut faire des demandes pour plusieurs enfants.
- La secrétaire vérifie les documents.
- Le directeur et le censeur valident selon le cycle.
- L’élève n’est actif qu’après paiement du minimum requis.
- Le seuil minimum est défini par l’administrateur et le comptable.
- Toute demande possède un statut clair.

## Statuts possibles

- En attente
- En vérification
- Non conforme
- Prêt pour validation
- Validée
- Refusée
- En attente paiement
- Paiement insuffisant
- Actif

## Entités principales

- enrollment_requests
- admission_rules
- payment_thresholds
- enrollment_status_logs

---

# 14. MODULE 5 — INFRASTRUCTURE SCOLAIRE

## Objectif

Gérer l’environnement physique de l’école.

Ce module gère :

- bâtiments ;
- salles ;
- salles spéciales ;
- capacité ;
- disponibilité ;
- équipements.

## Règles métier

- Une salle appartient à un bâtiment.
- Une salle ne peut pas être réservée deux fois au même moment.
- Une salle hors service ne doit pas être proposée dans les emplois du temps.
- L’administrateur peut créer des salles spéciales : Grande salle de fête, Magasin, Salle de réunion, Salle de conférence.
- Les équipements peuvent être associés à une salle.

## Entités principales

- buildings
- rooms
- room_types
- room_equipment
- room_availability_logs

---

# 15. MODULE 6 — EMPLOIS DU TEMPS

## Objectif

Organiser les horaires des classes, enseignants et salles.

## Règles métier

- L’enseignant voit automatiquement son planning du jour.
- Le cours en cours et le prochain cours doivent être visibles.
- Un enseignant ne peut pas avoir deux cours au même moment.
- Une salle ne peut pas être occupée deux fois au même moment.
- Les rattrapages sont demandés par les enseignants.
- Au secondaire, le censeur valide les rattrapages.
- Au primaire, le directeur valide les rattrapages.
- Les événements validés apparaissent dans le calendrier des utilisateurs concernés.

## Entités principales

- timetables
- timetable_slots
- teacher_schedules
- room_schedules
- makeup_requests
- td_sessions

---

# 16. MODULE 7 — PÉDAGOGIE

## Objectif

Suivre le déroulement réel des enseignements.

Ce module gère :

- cahier de texte numérique ;
- progression pédagogique ;
- contenus enseignés ;
- volume horaire prévu ;
- volume horaire réalisé.

## Règles métier

- L’enseignant renseigne les cours effectués.
- Le censeur suit la progression au secondaire.
- Le directeur suit la progression au primaire et à la maternelle.
- Les retards de progression doivent apparaître dans les tableaux de bord.
- Le contenu saisi doit être historisé.

## Entités principales

- lesson_logs
- teaching_progress
- syllabus_items
- teacher_workloads

---

# 17. MODULE 8 — ÉVALUATIONS

## Objectif

Gérer les évaluations, les épreuves et la banque d’épreuves.

## Règles métier

- Les contrôles continus sont programmés par les enseignants.
- Les mini-devoirs et devoirs surveillés sont programmés par la direction ou le censeur.
- Pendant les périodes de composition, le système passe en mode examen.
- En mode examen, aucun cours, TD ou rattrapage ne doit être programmé.
- Les enseignants déposent les épreuves.
- La secrétaire reçoit, vérifie et confirme les épreuves.
- Les dépôts tardifs sont autorisés mais marqués “hors délai”.
- La banque d’épreuves permet aux élèves de consulter les épreuves et corrigés publiés.

## Entités principales

- assessments
- assessment_types
- exam_periods
- exam_papers
- exam_paper_submissions
- paper_bank
- paper_download_logs

---

# 18. MODULE 9 — BULLETINS & RÉSULTATS

## Objectif

Gérer les notes validées, les moyennes, les rangs et les bulletins.

## Gouvernance par cycle

### Secondaire

Enseignant  
→ Censeur  
→ Directeur  
→ Secrétaire  
→ Publication

### Primaire

Enseignant  
→ Directeur  
→ Secrétaire  
→ Publication

### Maternelle

Enseignant  
→ Directeur  
→ Secrétaire  
→ Publication

## Règles métier

- Le système utilise uniquement les notes validées.
- Au secondaire, les notes sont validées par le censeur.
- Au primaire et à la maternelle, les notes sont validées par le directeur.
- La secrétaire configure les règles de calcul avec validation du censeur ou du directeur selon le cycle.
- Le calcul des moyennes varie selon le cycle.
- Au primaire, il n’y a pas forcément de contrôle continu.
- La secrétaire peut corriger une note manquante ou une anomalie.
- Toute correction doit être tracée.
- Les bulletins peuvent être générés individuellement, par classe, par cycle ou pour tout l’établissement.
- Les parents peuvent télécharger les bulletins en PDF ou Excel après publication.
- Le comptable peut bloquer la publication si l’école active le blocage financier.

## Entités principales

- grades
- grade_validations
- grading_rules
- report_cards
- report_card_lines
- student_rankings
- generation_batches
- manual_grade_adjustments

---

# 19. MODULE 10 — ABSENCES & DISCIPLINE

## Objectif

Gérer les présences, absences, retards et sanctions.

## Règles métier

- Il existe un cahier de présence des élèves.
- Il existe un cahier de présence des enseignants.
- En maternelle et primaire, l’appel est fait par l’enseignant titulaire.
- En journée discontinue, il y a un appel le matin et un appel l’après-midi.
- En journée continue, il y a au moins un appel le matin.
- Au secondaire, l’appel est fait au début de chaque cours.
- Le responsable de présence enseignants est désigné par l’administrateur ou le directeur.
- Le parent est informé de l’absence de son enfant, mais sa réponse est facultative.
- Le surveillant général valide ou refuse les justifications.
- Le surveillant général gère les sanctions courantes.
- Le directeur valide les sanctions lourdes.
- Seules les retenues et colles impactent la note de conduite.

## Entités principales

- student_attendances
- teacher_attendances
- absence_justifications
- disciplinary_incidents
- sanctions
- conduct_adjustments

---

# 20. MODULE 11 — FINANCES & COMPTABILITÉ

## Objectif

Gérer les flux financiers de l’école.

Ce module gère :

- frais scolaires ;
- paiements ;
- tranches ;
- restes à payer ;
- reçus ;
- dépenses ;
- rapports financiers ;
- graphiques comptables ;
- paie du personnel si activée.

## Règles métier

- L’élève n’a aucun accès aux finances.
- Le parent voit uniquement les finances de ses enfants.
- Le comptable gère les paiements.
- La secrétaire peut enregistrer certains paiements si l’école l’autorise.
- Les reçus sont uniques et non modifiables.
- Les rapports doivent être journaliers, mensuels, trimestriels, semestriels ou annuels.
- Les graphiques financiers doivent être professionnels et utiles.
- La paie du personnel doit être flexible : salaire fixe, vacataire, horaire, primes, retenues, absences.

## Entités principales

- fee_types
- fee_structures
- student_accounts
- payments
- receipts
- expenses
- payroll_profiles
- payroll_runs
- financial_reports

---

# 21. MODULE 12 — COMMUNICATION & ALERTES

## Objectif

Gérer la communication entre l’école, les enseignants, les parents et les élèves.

## Canaux autorisés

- notifications internes ;
- Email ;
- WhatsApp.

Aucun SMS.

## Règles métier

- Les absences déclenchent une notification au parent.
- Les évaluations déclenchent des rappels J-7, J-3 et J-1.
- Les bulletins publiés déclenchent une notification parent.
- Les messages doivent être tracés.
- La réponse parent est facultative sauf procédure spécifique.
- Les communications importantes doivent pouvoir être ciblées par classe, cycle, rôle ou utilisateur.

## Entités principales

- notifications
- messages
- message_recipients
- communication_templates
- delivery_logs
- whatsapp_logs
- email_logs

---

# 22. MODULE 13 — ARCHIVAGE & DOCUMENTS

## Objectif

Créer la mémoire numérique de l’école.

Ce module gère :

- documents récents ;
- archives existantes ;
- bulletins ;
- dossiers élèves ;
- reçus ;
- documents administratifs ;
- documents pédagogiques.

## Règles métier

- La secrétaire est le gestionnaire principal des archives.
- Les anciens documents papier doivent pouvoir être scannés ou photographiés.
- Les documents doivent être indexés par élève, classe, année, type et date réelle.
- Les archives importées doivent être distinguées des documents générés par le système.
- Les fichiers doivent pouvoir être consultés, téléchargés et recherchés.
- Les documents sensibles doivent être protégés par permissions.
- Les archives physiques doivent pouvoir recevoir une référence numérique.

## Entités principales

- documents
- document_types
- document_categories
- document_versions
- document_links
- import_batches
- archive_locations
- document_access_logs

---

# 23. MODULE 14 — ANALYTICS & STATISTIQUES

## Objectif

Fournir des tableaux de bord professionnels basés uniquement sur des données réelles.

Aucune intelligence artificielle.

Aucune prédiction.

Uniquement :

- calculs statistiques ;
- agrégations ;
- comparaisons ;
- graphiques ;
- indicateurs.

## Axes d’analyse

- académique ;
- discipline ;
- personnel ;
- finances ;
- opérationnel.

## Dashboards par rôle

### Directeur

Vue globale de l’établissement :

- résultats ;
- discipline ;
- personnel ;
- finances ;
- opérations.

### Censeur

Vue pédagogique :

- classes faibles ;
- matières faibles ;
- cohérence des notes ;
- performances enseignants.

### Surveillant général

Vue discipline :

- absences ;
- retards ;
- sanctions ;
- classes sensibles.

### Comptable

Vue financière :

- paiements ;
- impayés ;
- dépenses ;
- recettes ;
- rapports.

### Enseignant

Vue personnelle :

- performance de ses classes ;
- progression des élèves ;
- taux de réussite ;
- masse horaire par semaine ou par mois sous forme de courbes.

### Parent

Vue enfant :

- notes ;
- moyenne ;
- absences ;
- discipline ;
- bulletin.

### Élève

Vue personnelle :

- notes ;
- moyenne ;
- rang ;
- progression.

## Entités principales

- analytics_reports
- academic_stats
- discipline_stats
- staff_stats
- financial_stats
- operational_stats
- dashboard_widgets
- dashboard_snapshots

---

# 24. Interface utilisateur

## Layout général

L’application doit comporter :

- une barre latérale ;
- un header ;
- un tableau de bord par rôle ;
- un système de notifications ;
- un menu dynamique selon les permissions ;
- une zone de contenu claire ;
- une interface responsive mobile.

## Règles UI

- Tailwind CSS uniquement.
- Design sobre.
- Couleurs professionnelles.
- Pas de surcharge visuelle.
- Boutons clairs.
- Tableaux lisibles.
- Filtres visibles.
- Actions importantes confirmées par modal.
- Messages d’erreur compréhensibles.
- Pagination sur les grandes listes.

## Composants Blade recommandés

- x-layout
- x-sidebar
- x-header
- x-card
- x-table
- x-badge
- x-button
- x-modal
- x-alert
- x-form.input
- x-form.select
- x-form.textarea
- x-stats-card

---

# 25. JavaScript

JavaScript doit être utilisé uniquement pour améliorer l’expérience utilisateur.

Utilisations autorisées :

- modales ;
- confirmations ;
- filtres simples ;
- affichage dynamique ;
- prévisualisation de fichier ;
- notifications frontend ;
- graphiques simples.

JavaScript ne doit pas remplacer la validation backend.

---

# 26. Graphiques

Les graphiques doivent être utiles, pas décoratifs.

Ils doivent servir à comprendre :

- les résultats ;
- les finances ;
- les absences ;
- la masse horaire ;
- l’évolution des classes ;
- les tendances internes.

Graphiques attendus :

- courbes ;
- histogrammes ;
- diagrammes circulaires ;
- cartes de synthèse ;
- tableaux dynamiques.

---

# 27. Tests obligatoires

Codex doit prévoir des tests pour les règles critiques.

Exemples :

- un utilisateur sans permission ne peut pas accéder à une route protégée ;
- un parent ne voit que ses enfants ;
- un élève ne voit pas les finances ;
- une demande d’inscription ne peut pas être dupliquée pour le même élève dans la même année ;
- une note non validée ne peut pas être utilisée dans un bulletin ;
- une salle ne peut pas être réservée deux fois au même moment ;
- une seule année académique peut être active ;
- une correction de note est toujours journalisée.

---

# 28. Seeders initiaux obligatoires

Créer au minimum :

- rôles de base ;
- permissions de base ;
- utilisateur administrateur ;
- année académique active ;
- cycles ;
- niveaux ;
- types de documents ;
- types de salles ;
- statuts standards.

---

# 29. Première tâche à exécuter

Codex doit commencer par le Core System.

## Étapes initiales

1. Créer le projet Laravel.
2. Configurer `.env`.
3. Configurer la base de données.
4. Installer et configurer Tailwind CSS avec Vite.
5. Mettre en place l’authentification.
6. Créer les tables users, roles, permissions.
7. Créer les relations rôles-permissions-utilisateurs.
8. Créer les seeders des rôles.
9. Créer les permissions de base.
10. Créer le dashboard initial.
11. Créer le système d’audit logs.
12. Protéger les routes par permissions.

---

# 30. Comportement attendu de Codex

Avant toute modification importante, Codex doit expliquer brièvement :

- ce qu’il va créer ;
- pourquoi ;
- quels fichiers seront concernés.

Après chaque étape, Codex doit indiquer :

- ce qui a été créé ;
- ce qui reste à faire ;
- les commandes à exécuter ;
- les éventuelles migrations à lancer.

Codex doit travailler module par module.

Codex ne doit pas improviser une architecture différente sans raison forte.

Codex doit toujours respecter ce README comme source principale de vérité.

---

# 31. Définition de terminé

Un module est considéré terminé uniquement si :

- les migrations existent ;
- les modèles existent ;
- les relations sont définies ;
- les Form Requests existent ;
- les Policies existent si nécessaire ;
- les Services métier existent ;
- les routes sont protégées ;
- les vues Blade sont utilisables ;
- les permissions sont appliquées ;
- les actions critiques sont journalisées ;
- les tests essentiels existent ;
- l’interface est en français ;
- le module respecte les règles métier.

---

# 32. Commande mentale permanente pour Codex

Construis ce projet comme un produit SaaS professionnel destiné à de vraies écoles.

Ne cherche pas seulement à produire du code.

Cherche à produire un système fiable, sécurisé, clair, maintenable, évolutif et réellement utilisable par une administration scolaire.

Chaque ligne de code doit servir la logique métier.

Chaque module doit pouvoir être compris, maintenu et amélioré par un autre développeur.

Chaque action sensible doit être protégée.

Chaque donnée critique doit être traçable.

Chaque écran doit être simple pour un utilisateur non technique.

Ce README est la référence principale du projet.
