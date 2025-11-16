<?php


namespace App\Http\Controllers\order;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::query();

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $services = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'estimated_delivery_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'features' => 'nullable|array',
            'image' => 'nullable|string'
        ]);

        $service = Service::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully',
            'data' => $service
        ], 201);
    }

    public function show($id)
    {
        $service = Service::with('orderItems.order.customer')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $service
        ]);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'estimated_delivery_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'features' => 'nullable|array',
            'image' => 'nullable|string'
        ]);

        $service->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully',
            'data' => $service
        ]);
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully'
        ]);
    }

    public function restore($id)
    {
        $service = Service::withTrashed()->findOrFail($id);
        $service->restore();

        return response()->json([
            'success' => true,
            'message' => 'Service restored successfully',
            'data' => $service
        ]);
    }

    public function toggleActive($id)
    {
        $service = Service::findOrFail($id);
        $service->is_active = !$service->is_active;
        $service->save();

        return response()->json([
            'success' => true,
            'message' => 'Service status updated',
            'data' => $service
        ]);
    }

    public function stats($id)
    {
        $service = Service::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'total_orders' => $service->total_orders,
                'total_revenue' => $service->total_revenue,
                'service' => $service
            ]
        ]);
    }
}