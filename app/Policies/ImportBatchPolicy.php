<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ImportBatch;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ImportBatchPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ImportBatch');
    }

    public function view(AuthUser $authUser, ImportBatch $importBatch): bool
    {
        return $authUser->can('View:ImportBatch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ImportBatch');
    }

    public function update(AuthUser $authUser, ImportBatch $importBatch): bool
    {
        return $authUser->can('Update:ImportBatch');
    }

    public function delete(AuthUser $authUser, ImportBatch $importBatch): bool
    {
        return $authUser->can('Delete:ImportBatch');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ImportBatch');
    }

    public function restore(AuthUser $authUser, ImportBatch $importBatch): bool
    {
        return $authUser->can('Restore:ImportBatch');
    }

    public function forceDelete(AuthUser $authUser, ImportBatch $importBatch): bool
    {
        return $authUser->can('ForceDelete:ImportBatch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ImportBatch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ImportBatch');
    }

    public function replicate(AuthUser $authUser, ImportBatch $importBatch): bool
    {
        return $authUser->can('Replicate:ImportBatch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ImportBatch');
    }
}
