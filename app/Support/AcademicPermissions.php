<?php

namespace App\Support;

class AcademicPermissions
{
    /**
     * @return array<int, array{name: string, module: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'academic.cycles.view', 'module' => 'academic', 'description' => 'Consulter les cycles scolaires'],
            ['name' => 'academic.cycles.create', 'module' => 'academic', 'description' => 'Créer un cycle scolaire'],
            ['name' => 'academic.cycles.update', 'module' => 'academic', 'description' => 'Modifier un cycle scolaire'],
            ['name' => 'academic.cycles.disable', 'module' => 'academic', 'description' => 'Activer ou désactiver un cycle scolaire'],
            ['name' => 'academic.cycles.manage', 'module' => 'academic', 'description' => 'Gérer tous les cycles scolaires'],
            ['name' => 'academic.levels.view', 'module' => 'academic', 'description' => 'Consulter les niveaux scolaires'],
            ['name' => 'academic.levels.create', 'module' => 'academic', 'description' => 'Créer un niveau scolaire'],
            ['name' => 'academic.levels.update', 'module' => 'academic', 'description' => 'Modifier un niveau scolaire'],
            ['name' => 'academic.levels.disable', 'module' => 'academic', 'description' => 'Activer ou désactiver un niveau scolaire'],
            ['name' => 'academic.levels.manage', 'module' => 'academic', 'description' => 'Gérer tous les niveaux scolaires'],
            ['name' => 'academic.series.view', 'module' => 'academic', 'description' => 'Consulter les séries'],
            ['name' => 'academic.series.create', 'module' => 'academic', 'description' => 'Créer une série'],
            ['name' => 'academic.series.update', 'module' => 'academic', 'description' => 'Modifier une série'],
            ['name' => 'academic.series.disable', 'module' => 'academic', 'description' => 'Activer ou désactiver une série'],
            ['name' => 'academic.series.manage', 'module' => 'academic', 'description' => 'Gérer toutes les séries'],
            ['name' => 'academic.classes.view', 'module' => 'academic', 'description' => 'Consulter les classes académiques'],
            ['name' => 'academic.classes.create', 'module' => 'academic', 'description' => 'Créer une classe académique'],
            ['name' => 'academic.classes.update', 'module' => 'academic', 'description' => 'Modifier une classe académique'],
            ['name' => 'academic.classes.disable', 'module' => 'academic', 'description' => 'Activer ou désactiver une classe académique'],
            ['name' => 'academic.classes.manage', 'module' => 'academic', 'description' => 'Gérer toutes les classes académiques'],
            ['name' => 'academic.subjects.view', 'module' => 'academic', 'description' => 'Consulter les matières'],
            ['name' => 'academic.subjects.create', 'module' => 'academic', 'description' => 'Créer une matière'],
            ['name' => 'academic.subjects.update', 'module' => 'academic', 'description' => 'Modifier une matière'],
            ['name' => 'academic.subjects.disable', 'module' => 'academic', 'description' => 'Activer ou désactiver une matière'],
            ['name' => 'academic.subjects.manage', 'module' => 'academic', 'description' => 'Gérer toutes les matières'],
            ['name' => 'academic.coefficients.view', 'module' => 'academic', 'description' => 'Consulter les coefficients'],
            ['name' => 'academic.coefficients.create', 'module' => 'academic', 'description' => 'Créer un coefficient'],
            ['name' => 'academic.coefficients.update', 'module' => 'academic', 'description' => 'Modifier un coefficient'],
            ['name' => 'academic.coefficients.disable', 'module' => 'academic', 'description' => 'Activer ou désactiver un coefficient'],
            ['name' => 'academic.coefficients.manage', 'module' => 'academic', 'description' => 'Gérer tous les coefficients'],
            ['name' => 'academic.conduct.view', 'module' => 'academic', 'description' => 'Consulter les réglages de conduite'],
            ['name' => 'academic.conduct.create', 'module' => 'academic', 'description' => 'Créer un réglage de conduite'],
            ['name' => 'academic.conduct.update', 'module' => 'academic', 'description' => 'Modifier un réglage de conduite'],
            ['name' => 'academic.conduct.disable', 'module' => 'academic', 'description' => 'Activer ou désactiver un réglage de conduite'],
            ['name' => 'academic.conduct.manage', 'module' => 'academic', 'description' => 'Gérer tous les réglages de conduite'],
            ['name' => 'academic.config.view', 'module' => 'academic', 'description' => 'Consulter la configuration académique'],
            ['name' => 'academic.config.manage', 'module' => 'academic', 'description' => 'Gérer toute la configuration académique'],
            ['name' => 'academic.config.update', 'module' => 'academic', 'description' => 'Créer ou modifier la configuration académique'],
            ['name' => 'academic.periods.view', 'module' => 'academic', 'description' => 'Consulter les périodes académiques'],
            ['name' => 'academic.periods.create', 'module' => 'academic', 'description' => 'Créer une période académique'],
            ['name' => 'academic.periods.update', 'module' => 'academic', 'description' => 'Modifier une période académique'],
            ['name' => 'academic.periods.lock', 'module' => 'academic', 'description' => 'Verrouiller ou déverrouiller une période académique'],
            ['name' => 'academic.periods.manage', 'module' => 'academic', 'description' => 'Gérer toutes les périodes académiques'],
            ['name' => 'academic.day_config.view', 'module' => 'academic', 'description' => 'Consulter les configurations de journée'],
            ['name' => 'academic.day_config.create', 'module' => 'academic', 'description' => 'Créer une configuration de journée'],
            ['name' => 'academic.day_config.update', 'module' => 'academic', 'description' => 'Modifier une configuration de journée'],
            ['name' => 'academic.day_config.lock', 'module' => 'academic', 'description' => 'Verrouiller ou déverrouiller une configuration de journée'],
            ['name' => 'academic.day_config.manage', 'module' => 'academic', 'description' => 'Gérer toutes les configurations de journée'],
            ['name' => 'academic.calendars.view', 'module' => 'academic', 'description' => 'Consulter les calendriers scolaires'],
            ['name' => 'academic.calendars.create', 'module' => 'academic', 'description' => 'Créer un calendrier scolaire'],
            ['name' => 'academic.calendars.update', 'module' => 'academic', 'description' => 'Modifier un calendrier scolaire'],
            ['name' => 'academic.calendars.disable', 'module' => 'academic', 'description' => 'Activer ou désactiver un calendrier scolaire'],
            ['name' => 'academic.calendars.manage', 'module' => 'academic', 'description' => 'Gérer tous les calendriers scolaires'],
            ['name' => 'academic.events.view', 'module' => 'academic', 'description' => 'Consulter les événements scolaires'],
            ['name' => 'academic.events.create', 'module' => 'academic', 'description' => 'Créer un événement scolaire'],
            ['name' => 'academic.events.update', 'module' => 'academic', 'description' => 'Modifier un événement scolaire'],
            ['name' => 'academic.events.validate', 'module' => 'academic', 'description' => 'Valider un événement scolaire'],
            ['name' => 'academic.events.publish', 'module' => 'academic', 'description' => 'Publier un événement scolaire'],
            ['name' => 'academic.events.cancel', 'module' => 'academic', 'description' => 'Annuler un événement scolaire'],
            ['name' => 'academic.events.delete', 'module' => 'academic', 'description' => 'Supprimer un événement scolaire non publié'],
            ['name' => 'academic.events.archive', 'module' => 'academic', 'description' => 'Archiver un événement scolaire'],
            ['name' => 'academic.events.view_internal', 'module' => 'academic', 'description' => 'Voir les événements internes'],
            ['name' => 'academic.events.view_public', 'module' => 'academic', 'description' => 'Voir les événements publics'],
            ['name' => 'academic.events.manage_all', 'module' => 'academic', 'description' => 'Gérer tous les événements scolaires'],
        ];
    }
}
