<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    protected $modelTypes = [
        'App\\Models\\Invoice',
        'App\\Models\\MoU',
        'App\\Models\\Client',
        'App\\Models\\CashReport',
        'App\\Models\\CashReference',
        'App\\Models\\Coa'
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $logs = ActivityLog::with('user')
            ->latest()
            ->paginate(20);

        return view('activity-logs.index', [
            'logs' => $logs,
            'modelTypes' => $this->modelTypes
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ActivityLog $activityLog)
    {
        $activityLog->load('user', 'model');
        return view('activity-logs.show', compact('activityLog'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function filter(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest()->paginate(20);

        return view('activity-logs.index', [
            'logs' => $logs,
            'modelTypes' => $this->modelTypes
        ]);
    }
}
