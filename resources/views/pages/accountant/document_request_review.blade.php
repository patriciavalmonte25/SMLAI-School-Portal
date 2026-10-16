@extends('layouts.master')
@section('page_title', 'Review Document Request')
@section('content')
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Review Document Request</h6>
            {!! Qs::getPanelOptions() !!}
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="{{ route('accountant.document_requests.index') }}#{{ $documentRequest->status === 'pending' ? 'incoming-requests' : 'approved-requests' }}" class="btn btn-light">Back to Requests</a>
                <span class="text-muted">Submitted by {{ $documentRequest->requester->name }} on {{ $documentRequest->created_at->format('m/d/Y h:i A') }}</span>
            </div>

            @if($documentRequest->status === 'collected')
                <div class="alert alert-success">
                    <h6 class="font-weight-bold">Collection Details</h6>
                    <div>Request Received by: {{ $documentRequest->request_received_by }}</div>
                    <div>Request Prepared by: {{ $documentRequest->request_prepared_by }}</div>
                    <div>Request Released by: {{ $documentRequest->request_released_by }}</div>
                    <div>Date: {{ $documentRequest->collection_date->format('m/d/Y h:i A') }}</div>
                </div>
            @endif

            @include('pages.document_requests._details', ['documentRequest' => $documentRequest])

            @if($documentRequest->status === 'pending')
                <div class="d-flex justify-content-end" style="gap:8px;">
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#approveDocumentRequest">Approve</button>
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectDocumentRequest">Reject</button>
                </div>
            @endif
        </div>
    </div>

    @if($documentRequest->status === 'pending')
    <div class="modal fade" id="approveDocumentRequest" tabindex="-1" role="dialog" aria-labelledby="approveDocumentRequestLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('accountant.document_requests.approve', $documentRequest->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveDocumentRequestLabel">Approve Document Request</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Request to be released on <span class="text-danger">*</span></label>
                            <input name="release_date" type="text" class="form-control date-pick" data-date-format="mm/dd/yyyy" placeholder="MM/DD/YYYY" autocomplete="off" required>
                        </div>
                        <div class="form-group">
                            <label>Fee <span class="text-danger">*</span></label>
                            <input name="fee" type="text" class="form-control" required maxlength="50">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Submit Approval</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectDocumentRequest" tabindex="-1" role="dialog" aria-labelledby="rejectDocumentRequestLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('accountant.document_requests.reject', $documentRequest->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectDocumentRequestLabel">Reject Document Request</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <label>Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required minlength="5" maxlength="2000"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Submit Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection