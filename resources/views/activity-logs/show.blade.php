@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Activity Log Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-default">
                            Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Basic Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Date & Time</th>
                                    <td>{{ $activityLog->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>User</th>
                                    <td>{{ $activityLog->user->name }}</td>
                                </tr>
                                <tr>
                                    <th>Action</th>
                                    <td>{{ ucfirst($activityLog->action) }}</td>
                                </tr>
                                <tr>
                                    <th>Model</th>
                                    <td>{{ class_basename($activityLog->model_type) }}</td>
                                </tr>
                                <tr>
                                    <th>IP Address</th>
                                    <td>{{ $activityLog->ip_address }}</td>
                                </tr>
                                <tr>
                                    <th>User Agent</th>
                                    <td>{{ $activityLog->user_agent }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Changes</h5>
                            @if($activityLog->action === 'create')
                                <div class="alert alert-success">
                                    <strong>New Data:</strong>
                                    <pre>{{ json_encode($activityLog->new_data, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @elseif($activityLog->action === 'update')
                                <div class="alert alert-info">
                                    <strong>Old Data:</strong>
                                    <pre>{{ json_encode($activityLog->old_data, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                                <div class="alert alert-success">
                                    <strong>New Data:</strong>
                                    <pre>{{ json_encode($activityLog->new_data, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @elseif($activityLog->action === 'delete')
                                <div class="alert alert-danger">
                                    <strong>Deleted Data:</strong>
                                    <pre>{{ json_encode($activityLog->old_data, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 