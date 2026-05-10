<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BulkSheetExport;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BulkSheetExportPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BulkSheetExport');
    }

    public function view(AuthUser $authUser, BulkSheetExport $bulkSheetExport): bool
    {
        return $authUser->can('View:BulkSheetExport');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BulkSheetExport');
    }

    public function update(AuthUser $authUser, BulkSheetExport $bulkSheetExport): bool
    {
        return $authUser->can('Update:BulkSheetExport');
    }

    public function delete(AuthUser $authUser, BulkSheetExport $bulkSheetExport): bool
    {
        return $authUser->can('Delete:BulkSheetExport');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BulkSheetExport');
    }

    public function restore(AuthUser $authUser, BulkSheetExport $bulkSheetExport): bool
    {
        return $authUser->can('Restore:BulkSheetExport');
    }

    public function forceDelete(AuthUser $authUser, BulkSheetExport $bulkSheetExport): bool
    {
        return $authUser->can('ForceDelete:BulkSheetExport');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BulkSheetExport');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BulkSheetExport');
    }

    public function replicate(AuthUser $authUser, BulkSheetExport $bulkSheetExport): bool
    {
        return $authUser->can('Replicate:BulkSheetExport');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BulkSheetExport');
    }
}
