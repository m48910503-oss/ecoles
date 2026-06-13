<?php

namespace App\Support;

class PeoplePermissions
{
    /**
     * @return array<int, array{name: string, module: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'people.dashboard.view', 'module' => 'people', 'description' => 'Accéder au tableau de bord des personnes'],
            ['name' => 'people.students.view', 'module' => 'people', 'description' => 'Consulter les élèves'],
            ['name' => 'people.students.create', 'module' => 'people', 'description' => 'Créer un dossier élève'],
            ['name' => 'people.students.update', 'module' => 'people', 'description' => 'Modifier un dossier élève'],
            ['name' => 'people.students.disable', 'module' => 'people', 'description' => 'Suspendre ou réactiver un élève'],
            ['name' => 'people.students.view_sensitive', 'module' => 'people', 'description' => 'Consulter les informations sensibles des élèves'],
            ['name' => 'people.parents.view', 'module' => 'people', 'description' => 'Consulter les parents et tuteurs'],
            ['name' => 'people.parents.create', 'module' => 'people', 'description' => 'Créer un parent ou tuteur'],
            ['name' => 'people.parents.update', 'module' => 'people', 'description' => 'Modifier un parent ou tuteur'],
            ['name' => 'people.parents.link_student', 'module' => 'people', 'description' => 'Associer un parent à un élève'],
            ['name' => 'people.staff.view', 'module' => 'people', 'description' => 'Consulter le personnel administratif'],
            ['name' => 'people.staff.create', 'module' => 'people', 'description' => 'Créer un membre du personnel'],
            ['name' => 'people.staff.update', 'module' => 'people', 'description' => 'Modifier un membre du personnel'],
            ['name' => 'people.staff.disable', 'module' => 'people', 'description' => 'Suspendre ou réactiver un membre du personnel'],
            ['name' => 'people.teachers.view', 'module' => 'people', 'description' => 'Consulter les enseignants'],
            ['name' => 'people.teachers.create', 'module' => 'people', 'description' => 'Créer un enseignant'],
            ['name' => 'people.teachers.update', 'module' => 'people', 'description' => 'Modifier un enseignant'],
            ['name' => 'people.teachers.assign_subjects', 'module' => 'people', 'description' => 'Affecter des matières aux enseignants'],
            ['name' => 'people.teachers.invite', 'module' => 'people', 'description' => 'Générer un lien d’inscription enseignant'],
            ['name' => 'people.teachers.validate', 'module' => 'people', 'description' => 'Valider l’inscription d’un enseignant'],
            ['name' => 'people.documents.view', 'module' => 'people', 'description' => 'Consulter les documents liés aux personnes'],
            ['name' => 'people.documents.attach', 'module' => 'people', 'description' => 'Associer un document à une personne'],
            ['name' => 'people.documents.remove', 'module' => 'people', 'description' => 'Retirer un document lié à une personne'],
            ['name' => 'people.accounts.view', 'module' => 'people', 'description' => 'Consulter les comptes utilisateurs liés aux personnes'],
            ['name' => 'people.accounts.create', 'module' => 'people', 'description' => 'Créer ou lier un compte utilisateur à une personne'],
            ['name' => 'people.accounts.reset', 'module' => 'people', 'description' => 'Réinitialiser l’accès d’un compte lié'],
            ['name' => 'people.accounts.suspend', 'module' => 'people', 'description' => 'Suspendre ou réactiver un compte lié'],
            ['name' => 'people.accounts.unlink', 'module' => 'people', 'description' => 'Dissocier un compte utilisateur d’une personne'],
            ['name' => 'people.history.view', 'module' => 'people', 'description' => 'Consulter l’historique des personnes'],
        ];
    }
}
