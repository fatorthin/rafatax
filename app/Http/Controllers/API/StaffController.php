<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Http\Requests\StaffStoreRequest;
use App\Http\Requests\StaffUpdateRequest;
use App\Http\Resources\StaffResource;
use App\Http\Resources\StaffCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return StaffCollection
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $query = Staff::query()
            ->with(['departmentReference', 'positionReference']);

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('no_ktp', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_reference_id', $request->input('department_id'));
        }

        // Filter by position
        if ($request->has('position_id')) {
            $query->where('position_reference_id', $request->input('position_id'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $staff = $query->paginate($perPage);

        return new StaffCollection($staff);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StaffStoreRequest $request
     * @return StaffResource
     */
    public function store(StaffStoreRequest $request)
    {
        $staff = Staff::create($request->validated());

        $staff->load(['departmentReference', 'positionReference']);

        return (new StaffResource($staff))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param Staff $staff
     * @return StaffResource
     */
    public function show(Staff $staff)
    {
        $staff->load([
            'departmentReference',
            'positionReference',
            'clients',
            'trainings'
        ]);

        return new StaffResource($staff);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StaffUpdateRequest $request
     * @param Staff $staff
     * @return StaffResource
     */
    public function update(StaffUpdateRequest $request, Staff $staff)
    {
        $staff->update($request->validated());

        $staff->load(['departmentReference', 'positionReference']);

        return new StaffResource($staff);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Staff $staff
     * @return JsonResponse
     */
    public function destroy(Staff $staff)
    {
        $staff->delete();

        return response()->json([
            'message' => 'Staff berhasil dihapus',
        ], 200);
    }
}
