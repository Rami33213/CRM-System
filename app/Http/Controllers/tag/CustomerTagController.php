<?php

namespace App\Http\Controllers\tag;
use App\Http\Controllers\Controller;

use App\Models\Customer;
use App\Models\Tag;
use Illuminate\Http\Request;

class CustomerTagController extends Controller
{
    /**
     * Get Customer's Tags
     */
    public function index($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        $tags = $customer->tags;

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name
            ],
            'data' => $tags
        ]);
    }

    /**
     * Attach Tag to Customer
     */
    public function attach(Request $request, $customerId)
    {
        $validated = $request->validate([
            'tag_id' => 'required|exists:tags,id'
        ]);

        $customer = Customer::findOrFail($customerId);
        $tag = Tag::findOrFail($validated['tag_id']);

        // التحقق من أن التاغ غير موجود مسبقاً
        if ($customer->tags()->where('tag_id', $tag->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tag already attached to this customer'
            ], 400);
        }

        $customer->tags()->attach($tag->id);

        return response()->json([
            'success' => true,
            'message' => 'Tag attached successfully',
            'data' => [
                'customer' => $customer->load('tags'),
                'tag' => $tag
            ]
        ]);
    }

    /**
     * Attach Multiple Tags to Customer
     */
    public function attachMultiple(Request $request, $customerId)
    {
        $validated = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'exists:tags,id'
        ]);

        $customer = Customer::findOrFail($customerId);

        // إضافة التاغات (sync بدون حذف القديمة)
        $customer->tags()->syncWithoutDetaching($validated['tag_ids']);

        return response()->json([
            'success' => true,
            'message' => count($validated['tag_ids']) . ' tags attached successfully',
            'data' => $customer->load('tags')
        ]);
    }

    /**
     * Detach Tag from Customer
     */
    public function detach($customerId, $tagId)
    {
        $customer = Customer::findOrFail($customerId);
        $tag = Tag::findOrFail($tagId);

        $customer->tags()->detach($tagId);

        return response()->json([
            'success' => true,
            'message' => 'Tag detached successfully',
            'data' => [
                'customer' => $customer->load('tags'),
                'detached_tag' => $tag
            ]
        ]);
    }

    /**
     * Sync Customer Tags (استبدال كل التاغات)
     */
    public function sync(Request $request, $customerId)
    {
        $validated = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'exists:tags,id'
        ]);

        $customer = Customer::findOrFail($customerId);
        $customer->tags()->sync($validated['tag_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Tags synced successfully',
            'data' => $customer->load('tags')
        ]);
    }

    /**
     * Detach All Tags from Customer
     */
    public function detachAll($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        $customer->tags()->detach();

        return response()->json([
            'success' => true,
            'message' => 'All tags detached successfully'
        ]);
    }

    /**
     * Search Customers by Tag
     */
    public function searchByTag(Request $request)
    {
        $validated = $request->validate([
            'tag_id' => 'sometimes|exists:tags,id',
            'tag_name' => 'sometimes|string',
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'exists:tags,id'
        ]);

        $query = Customer::with(['tags', 'segment', 'employee']);

        // البحث بـ tag_id واحد
        if (isset($validated['tag_id'])) {
            $query->whereHas('tags', function ($q) use ($validated) {
                $q->where('tags.id', $validated['tag_id']);
            });
        }

        // البحث بـ tag_name
        if (isset($validated['tag_name'])) {
            $query->whereHas('tags', function ($q) use ($validated) {
                $q->where('tags.name', 'LIKE', '%' . $validated['tag_name'] . '%');
            });
        }

        // البحث بعدة tags (العملاء اللي عندهم أي واحد منها)
        if (isset($validated['tag_ids'])) {
            $query->whereHas('tags', function ($q) use ($validated) {
                $q->whereIn('tags.id', $validated['tag_ids']);
            });
        }

        $customers = $query->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    /**
     * Create Tag and Attach to Customer (في خطوة واحدة)
     */
    public function createAndAttach(Request $request, $customerId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string'
        ]);

        $customer = Customer::findOrFail($customerId);

        // إنشاء التاغ
        $tag = Tag::create($validated);

        // ربطه بالعميل
        $customer->tags()->attach($tag->id);

        return response()->json([
            'success' => true,
            'message' => 'Tag created and attached successfully',
            'data' => [
                'tag' => $tag,
                'customer' => $customer->load('tags')
            ]
        ], 201);
    }

    /**
     * Get Customers by Multiple Tags (AND logic)
     * العملاء اللي عندهم كل التاغات
     */
    public function searchByAllTags(Request $request)
    {
        $validated = $request->validate([
            'tag_ids' => 'required|array|min:1',
            'tag_ids.*' => 'exists:tags,id'
        ]);

        $query = Customer::with(['tags', 'segment', 'employee']);

        foreach ($validated['tag_ids'] as $tagId) {
            $query->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        }

        $customers = $query->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'message' => 'Customers with ALL specified tags',
            'data' => $customers
        ]);
    }
}