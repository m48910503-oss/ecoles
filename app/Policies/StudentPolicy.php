<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'people.students.view',
            'people.students.create',
            'people.students.update',
            'people.students.disable',
            'people.students.view_sensitive',
        ]);
    }

    public function view(User $user, Student $student): bool
    {
        if ($student->user_id !== null && $student->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('parent')) {
            return $student->parents()->where('user_id', $user->id)->exists();
        }

        return $user->hasAnyPermission([
            'people.students.view',
            'people.students.view_sensitive',
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('people.students.create');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasPermissionTo('people.students.update');
    }

    public function disable(User $user, Student $student): bool
    {
        return $user->hasPermissionTo('people.students.disable');
    }

    public function viewSensitive(User $user, Student $student): bool
    {
        if ($student->user_id !== null && $student->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('parent') && $student->parents()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $user->hasPermissionTo('people.students.view_sensitive');
    }

    public function history(User $user, Student $student): bool
    {
        return $user->hasPermissionTo('people.history.view');
    }

    public function linkParent(User $user, Student $student): bool
    {
        return $user->hasAnyPermission([
            'people.parents.link_student',
            'people.students.update',
        ]);
    }

    public function viewDocuments(User $user, Student $student): bool
    {
        return $this->view($user, $student) && $user->hasPermissionTo('people.documents.view');
    }

    public function attachDocument(User $user, Student $student): bool
    {
        return $this->view($user, $student) && $user->hasPermissionTo('people.documents.attach');
    }

    public function removeDocument(User $user, Student $student): bool
    {
        return $this->view($user, $student) && $user->hasPermissionTo('people.documents.remove');
    }

    public function viewAccount(User $user, Student $student): bool
    {
        return $this->view($user, $student) && $user->hasPermissionTo('people.accounts.view');
    }

    public function createAccount(User $user, Student $student): bool
    {
        return $this->view($user, $student) && $user->hasPermissionTo('people.accounts.create');
    }

    public function resetAccount(User $user, Student $student): bool
    {
        return $this->view($user, $student) && $student->user_id !== null && $user->hasPermissionTo('people.accounts.reset');
    }

    public function toggleAccount(User $user, Student $student): bool
    {
        return $this->view($user, $student) && $student->user_id !== null && $user->hasPermissionTo('people.accounts.suspend');
    }

    public function unlinkAccount(User $user, Student $student): bool
    {
        return $this->view($user, $student) && $student->user_id !== null && $user->hasPermissionTo('people.accounts.unlink');
    }
}
