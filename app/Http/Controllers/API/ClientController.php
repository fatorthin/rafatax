<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Http\Resources\ClientResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $query = Client::query()
            ->with(['mous', 'staff']);

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('npwp', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by grade
        if ($request->has('grade')) {
            $query->where('grade', $request->input('grade'));
        }

        // Filter by jenis_wp
        if ($request->has('jenis_wp')) {
            $query->where('jenis_wp', $request->input('jenis_wp'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $clients = $query->paginate($perPage);

        return ClientResource::collection($clients);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return ClientResource
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:clients,code',
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'owner_name' => 'nullable|string|max:255',
            'owner_role' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:255',
            'jenis_wp' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:255',
            'pph_25_reporting' => 'nullable|string|max:255',
            'pph_23_reporting' => 'nullable|string|max:255',
            'pph_21_reporting' => 'nullable|string|max:255',
            'pph_4_reporting' => 'nullable|string|max:255',
            'ppn_reporting' => 'nullable|string|max:255',
            'spt_reporting' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
        ]);

        $client = Client::create($validated);

        $client->load(['mous', 'staff']);

        return (new ClientResource($client))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param Client $client
     * @return ClientResource
     */
    public function show(Client $client)
    {
        $client->load(['mous', 'staff']);

        return new ClientResource($client);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Client $client
     * @return ClientResource
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:255|unique:clients,code,' . $client->id,
            'company_name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'owner_name' => 'nullable|string|max:255',
            'owner_role' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:255',
            'jenis_wp' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:255',
            'pph_25_reporting' => 'nullable|string|max:255',
            'pph_23_reporting' => 'nullable|string|max:255',
            'pph_21_reporting' => 'nullable|string|max:255',
            'pph_4_reporting' => 'nullable|string|max:255',
            'ppn_reporting' => 'nullable|string|max:255',
            'spt_reporting' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
        ]);

        $client->update($validated);

        $client->load(['mous', 'staff']);

        return new ClientResource($client);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Client $client
     * @return JsonResponse
     */
    public function destroy(Client $client)
    {
        $client->delete();

        return response()->json([
            'message' => 'Client berhasil dihapus',
        ], 200);
    }

    /**
     * Restore a soft deleted client.
     *
     * @param int $id
     * @return ClientResource|JsonResponse
     */
    public function restore(int $id)
    {
        $client = Client::withTrashed()->findOrFail($id);

        if (!$client->trashed()) {
            return response()->json([
                'message' => 'Client tidak dalam keadaan terhapus',
            ], 400);
        }

        $client->restore();
        $client->load(['mous', 'staff']);

        return new ClientResource($client);
    }

    /**
     * Force delete a client permanently.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function forceDelete(int $id)
    {
        $client = Client::withTrashed()->findOrFail($id);

        $client->forceDelete();

        return response()->json([
            'message' => 'Client berhasil dihapus permanen',
        ], 200);
    }
}
