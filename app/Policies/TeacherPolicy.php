<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'people.teachers.view',
            'people.teachers.create',
            'people.teachers.update',
            'people.teachers.assign_subjects',
        ]);
    }

    public function view(User $user, Teacher $teacher): bool
    {
        if ($teacher->user_id !== null && $teacher->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('people.teachers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('people.teachers.create');
    }

    public function update(User $user, Teacher $teacher): bool
    {
        if ($teacher->user_id !== null && $teacher->user_id === $user->id) {
            return false;
        }

        return $user->hasPermissionTo('people.teachers.update');
    }

    public function assignSubjects(User $user, Teacher $teacher): bool
    {
        return $user->hasPermissionTo('people.teachers.assign_subjects');
    }

    public function inviteAccount(User $user, Teacher $teacher): bool
    {
        return $this->view($user, $teacher) && $user->hasPermissionTo('people.teachers.invite');
    }

    public function validateInvitation(User $user, Teacher $teacher): bool
    {
        return $this->view($user, $teacher) && $user->hasPermissionTo('people.teachers.validate');
    }

    public function history(User $user, Teacher $teacher): bool
    {
        return $user->hasPermissionTo('people.history.view');
    }

    public function viewDocuments(User $user, Teacher $teacher): bool
    {
        return $this->view($user, $teacher) && $user->hasPermissionTo('people.documents.view');
    }

    public function attachDocument(User $user, Teacher $teacher): bool
    {
        return $this->view($user, $teacher) && $user->hasPermissionTo('people.documents.attach');
    }

    public function removeDocument(User $user, Teacher $teacher): bool
    {
        return $this->view($user, $teacher) && $user->hasPermissionTo('people.documents.remove');
    }

    public function viewAccount(User $user, Teacher $teacher): bool
    {
        return $this->view($user, $teacher) && $user->hasPermissionTo('people.accounts.view');
    }

    public function createAccount(User $user, Teacher $teacher): bool
    {
        return $this->view($user, $teacher) && $user->hasPermissionTo('people.accounts.create');
    }

    public function resetAccount(User $user, Teacher $teacher): bool
    {
        return $this->view($user, $teacher) && $teacher->user_id !== null && $user->hasPermissionTo('people.accounts.reset');
    }

    public function toggleAccount(User $user, Teacher $teacher): bool
    {
        return $this->view($user, $teacher) && $teacher->user_id !== null && $user->hasPermissionTo('people.accounts.suspend');
    }

    public function unlinkAccount(User $user, Teacher $teacher): bool
    {
        return $this->view($user, $teacher) && $teacher->user_id !== null && $user->hasPermissionTo('people.accounts.unlink');
    }
}
