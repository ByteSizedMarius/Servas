<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\Models\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function __construct(
        protected GroupService $groupService,
    )
    {
        //
    }

    /**
     * Returns all groups for the current user.
     */
    public function getAllGroups(): JsonResponse
    {
        $groups = Group::orderBy('title')
            ->filterByCurrentUser()
            ->withCount(['groups'])
            ->get()
            ->map(fn(Group $group) => [
                'id' => $group->id,
                'title' => $group->title,
                'parent_group_id' => $group->parent_group_id,
                'child_groups_count' => $group->groups_count,
                'links_count' => $group->links_count,
                'created_at' => $group->created_at->toIso8601String(),
                'updated_at' => $group->updated_at->toIso8601String(),
            ]);

        return response()->json($groups);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:3',
            'parent_group_id' => 'nullable|integer|exists:groups,id',
        ]);

        $group = Group::make();
        $group->title = $validated['title'];
        $group->parent_group_id = $validated['parent_group_id'] ?? null;
        $group->user_id = Auth::id();
        $group->query_options = $this->groupService->cleanupQueryOptions([
            'containsTagsOr' => [],
            'containsTagsAnd' => [],
            'containsTagsNot' => [],
        ]);
        $group->updateLinksCount();
        $group->save();

        return response()->json([
            'id' => $group->id,
            'title' => $group->title,
            'parent_group_id' => $group->parent_group_id,
            'created_at' => $group->created_at->toIso8601String(),
            'updated_at' => $group->updated_at->toIso8601String(),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $group = Group::filterByCurrentUser()->find($id);

        if (!$group) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'string|min:3',
            'parent_group_id' => 'nullable|integer|exists:groups,id',
        ]);

        if (isset($validated['title'])) {
            $group->title = $validated['title'];
        }

        if (array_key_exists('parent_group_id', $validated) && $group->id !== $validated['parent_group_id']) {
            $group->parent_group_id = $validated['parent_group_id'];
        }

        $group->save();

        return response()->json([
            'id' => $group->id,
            'title' => $group->title,
            'parent_group_id' => $group->parent_group_id,
            'created_at' => $group->created_at->toIso8601String(),
            'updated_at' => $group->updated_at->toIso8601String(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $group = Group::filterByCurrentUser()->find($id);

        if (!$group) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($publicLink = $group->publicLink) {
            $publicLink->delete();
        }

        $group->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
