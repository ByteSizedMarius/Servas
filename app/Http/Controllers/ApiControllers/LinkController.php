<?php

namespace App\Http\Controllers\ApiControllers;

use App\Helpers\WebpageData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLinkApiRequest;
use App\Models\Link;
use App\Services\Models\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Spatie\Tags\Tag;

class LinkController extends Controller
{
    public function __construct(
        protected GroupService $groupService,
    )
    {
        //
    }

    public function index(): JsonResponse
    {
        $links = Link::orderBy('created_at', 'desc')
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

        return response()->json($links);
    }

    public function show(int $id): JsonResponse
    {
        $link = Link::filterByCurrentUser()->with('groups')->find($id);

        if (!$link) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json([
            'id' => $link->id,
            'title' => $link->title,
            'url' => $link->link,
            'groups' => $link->groups->pluck('id')->toArray(),
            'created_at' => $link->created_at->toIso8601String(),
            'updated_at' => $link->updated_at->toIso8601String(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLinkApiRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $link = Link::make();

        $link->link = $validated['link'];
        $link->title = $validated['title'];
        $link->user_id = Auth::id();

        if (empty($link->title)) {
            $link->title = WebpageData::getWebPageTitle($link->link);
        }

        $link->save();

        if (key_exists('groups', $validated)) {
            $groupIds = $validated['groups'];
            $link->groups()->sync($groupIds);
            $this->groupService->updateUserGroupsLinkCount(Auth::user());
        }

        if (key_exists('tags', $validated)) {
            $tags = [];
            foreach ($validated['tags'] as $tag) {
                $tags[] = Tag::filterByCurrentUser()->find($tag);
            }
            $link->syncTags($tags);
        }

        $link->load('groups');

        return response()->json([
            'id' => $link->id,
            'title' => $link->title,
            'url' => $link->link,
            'groups' => $link->groups->pluck('id')->toArray(),
            'created_at' => $link->created_at->toIso8601String(),
            'updated_at' => $link->updated_at->toIso8601String(),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $link = Link::filterByCurrentUser()->find($id);

        if (!$link) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'string|nullable',
            'link' => 'url|nullable',
            'groups' => 'array|nullable',
            'groups.*' => 'integer|exists:groups,id',
        ]);

        if (isset($validated['link'])) {
            $link->link = $validated['link'];
        }

        if (array_key_exists('title', $validated)) {
            $link->title = $validated['title'];
            if (empty($link->title)) {
                $link->title = WebpageData::getWebPageTitle($link->link);
            }
        }

        $link->save();

        if (isset($validated['groups'])) {
            $link->groups()->sync($validated['groups']);
            $this->groupService->updateUserGroupsLinkCount(Auth::user());
        }

        $link->load('groups');

        return response()->json([
            'id' => $link->id,
            'title' => $link->title,
            'url' => $link->link,
            'groups' => $link->groups->pluck('id')->toArray(),
            'created_at' => $link->created_at->toIso8601String(),
            'updated_at' => $link->updated_at->toIso8601String(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $link = Link::filterByCurrentUser()->find($id);

        if (!$link) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $link->groups()->detach();
        $link->delete();
        $this->groupService->updateUserGroupsLinkCount(Auth::user());

        return response()->json(['message' => 'Deleted']);
    }
}
