<?php

namespace App\Support;

class CorePermissions
{
    /**
     * @return array<int, array{name: string, module: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'core.dashboard.view', 'module' => 'core', 'description' => 'Accéder au tableau de bord'],
            ['name' => 'core.users.view', 'module' => 'core', 'description' => 'Consulter les utilisateurs'],
            ['name' => 'core.users.create', 'module' => 'core', 'description' => 'Créer un utilisateur'],
            ['name' => 'core.users.update', 'module' => 'core', 'description' => 'Modifier un utilisateur'],
            ['name' => 'core.users.suspend', 'module' => 'core', 'description' => 'Suspendre ou réactiver un utilisateur'],
            ['name' => 'core.roles.view', 'module' => 'core', 'description' => 'Consulter les rôles'],
            ['name' => 'core.roles.manage', 'module' => 'core', 'description' => 'Gérer les rôles et permissions'],
            ['name' => 'core.schools.view', 'module' => 'core', 'description' => 'Consulter les établissements'],
            ['name' => 'core.schools.create', 'module' => 'core', 'description' => 'Créer un établissement'],
            ['name' => 'core.schools.update', 'module' => 'core', 'description' => 'Modifier un établissement'],
            ['name' => 'core.schools.delete', 'module' => 'core', 'description' => 'Supprimer un établissement inactif'],
            ['name' => 'core.schools.switch', 'module' => 'core', 'description' => 'Changer l’établissement courant'],
            ['name' => 'core.academic_years.view', 'module' => 'core', 'description' => 'Consulter les années académiques'],
            ['name' => 'core.academic_years.create', 'module' => 'core', 'description' => 'Créer une année académique'],
            ['name' => 'core.academic_years.update', 'module' => 'core', 'description' => 'Modifier une année académique'],
            ['name' => 'core.academic_years.delete', 'module' => 'core', 'description' => 'Supprimer une année académique'],
            ['name' => 'core.academic_years.activate', 'module' => 'core', 'description' => 'Définir l’année académique active'],
            ['name' => 'core.settings.manage', 'module' => 'core', 'description' => 'Modifier les paramètres généraux'],
            ['name' => 'core.multi_school.manage', 'module' => 'core', 'description' => 'Activer le mode multi-établissement'],
            ['name' => 'core.audit.view', 'module' => 'core', 'description' => 'Consulter le journal d’audit'],
        ];
    }
}
