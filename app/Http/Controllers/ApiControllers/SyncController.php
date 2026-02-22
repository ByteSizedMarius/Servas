<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Link;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    /**
     * Full state dump for sync diffing. Returns all links and groups
     * with timestamps so the extension can detect what changed.
     */
    public function state(): JsonResponse
    {
        $links = Link::orderBy('id')
            ->filterByCurrentUser()
            ->with('groups')
            ->get()
            ->map(fn(Link $link) => [
                'id' => $link->id,
                'title' => $link->title,
                'url' => $link->link,
                'groups' => $link->groups->pluck('id')->toArray(),
                'created_at' => $link->created_at->toIso8601String(),
                'updated_at' => $link->updated_at->toIso8601String(),
            ]);

        $groups = Group::orderBy('id')
            ->filterByCurrentUser()
            ->get()
            ->map(fn(Group $group) => [
                'id' => $group->id,
                'title' => $group->title,
                'parent_group_id' => $group->parent_group_id,
                'created_at' => $group->created_at->toIso8601String(),
                'updated_at' => $group->updated_at->toIso8601String(),
            ]);

        return response()->json([
            'links' => $links,
            'groups' => $groups,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
