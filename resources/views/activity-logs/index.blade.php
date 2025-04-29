@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Activity Logs</h3>
                    <div class="card-tools">
                        <form action="{{ route('activity-logs.filter') }}" method="GET" class="form-inline">
                            <div class="input-group input-group-sm">
                                <select name="action" class="form-control">
                                    <option value="">All Actions</option>
                                    <option value="create" {{ request('action') == 'create' ? 'selected' : '' }}>Create</option>
                                    <option value="update" {{ request('action') == 'update' ? 'selected' : '' }}>Update</option>
                                    <option value="delete" {{ request('action') == 'delete' ? 'selected' : '' }}>Delete</option>
                                    <option value="send_invoice" {{ request('action') == 'send_invoice' ? 'selected' : '' }}>Send Invoice</option>
                                </select>
                                <select name="model_type" class="form-control">
                                    <option value="">All Models</option>
                                    @foreach($modelTypes as $type)
                                        <option value="{{ $type }}" {{ request('model_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From Date">
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To Date">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Model</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>{{ $log->user->name }}</td>
                                        <td>{{ ucfirst($log->action) }}</td>
                                        <td>{{ class_basename($log->model_type) }}</td>
                                        <td>
                                            <a href="{{ route('activity-logs.show', $log) }}" class="btn btn-sm btn-info">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 