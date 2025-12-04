<?php

namespace App\Http\Controllers\tag;
use App\Http\Controllers\Controller;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class TagController extends Controller
{
    /**
     * Get All Tags
     */
    public function index(Request $request)
    {
        $query = Tag::withCount('customers');

        // البحث
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // الترتيب
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        
        if ($sortBy === 'customers_count') {
            $query->orderBy('customers_count', $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $tags = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Create New Tag
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
            'color' => 'nullable|string|max:7', // hex color
            'description' => 'nullable|string'
        ]);

        $tag = Tag::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully',
            'data' => $tag
        ], 201);
    }

    /**
     * Get Tag Details
     */
    public function show($id)
    {
        $tag = Tag::withCount('customers')
            ->with(['customers' => function ($query) {
                $query->select('customers.id', 'customers.name', 'customers.email', 'customers.phone');
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $tag
        ]);
    }

    /**
     * Update Tag
     */
    public function update(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:tags,name,' . $id,
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string'
        ]);

        $tag->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag updated successfully',
            'data' => $tag
        ]);
    }

    /**
     * Delete Tag
     */
    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        $customersCount = $tag->customers()->count();
        
        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => "Tag deleted successfully. {$customersCount} customers were untagged."
        ]);
    }

    /**
     * Get Popular Tags
     */
    public function popular(Request $request)
    {
        $limit = $request->get('limit', 10);
        $tags = Tag::popular($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Bulk Delete Tags
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'exists:tags,id'
        ]);

        $deleted = Tag::whereIn('id', $validated['tag_ids'])->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} tags deleted successfully"
        ]);
    }
}