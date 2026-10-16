@extends('layouts.master')
@section('page_title', 'Document Requests')
@section('content')
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Document Requests</h6>
            {!! Qs::getPanelOptions() !!}
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#incoming-requests" class="nav-link active" data-toggle="tab">Incoming Requests</a></li>
                <li class="nav-item"><a href="#approved-requests" class="nav-link" data-toggle="tab">Approved Requests</a></li>
                <li class="nav-item"><a href="#rejected-requests" class="nav-link" data-toggle="tab">Rejected Requests</a></li>
            </ul>

            <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="incoming-requests">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Student</th><th>Requested By</th><th>School Year</th><th>Submitted</th><th class="text-center">Action</th></tr></thead>
                            <tbody>
                            @forelse($incomingRequests as $documentRequest)
                                <tr>
                                    <td>{{ $documentRequest->student_name }}</td>
                                    <td>{{ $documentRequest->requester->name }}</td>
                                    <td>{{ $documentRequest->school_year_last_attended }}</td>
                                    <td>{{ $documentRequest->created_at->format('m/d/Y h:i A') }}</td>
                                    <td class="text-center"><a class="btn btn-primary btn-sm" href="{{ route('accountant.document_requests.show', $documentRequest->id) }}">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No incoming requests.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="approved-requests">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Student</th><th>Requested By</th><th>Release Date</th><th>Fee</th><th class="text-center">Action</th></tr></thead>
                            <tbody>
                            @forelse($approvedRequests as $documentRequest)
                                <tr>
                                    <td>{{ $documentRequest->student_name }}</td>
                                    <td>{{ $documentRequest->requester->name }}</td>
                                    <td>{{ $documentRequest->release_date->format('m/d/Y') }}</td>
                                    <td>{{ $documentRequest->fee }}</td>
                                    <td class="text-center">
                                        @if($documentRequest->status === 'collected')
                                            <a href="{{ route('accountant.document_requests.show', $documentRequest->id) }}" class="btn btn-success btn-sm">Collected</a>
                                        @else
                                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#collectRequest{{ $documentRequest->id }}">Mark Collected</button>
                                        <div class="modal fade" id="collectRequest{{ $documentRequest->id }}" tabindex="-1" role="dialog" aria-labelledby="collectRequestLabel{{ $documentRequest->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <form id="collectDocumentRequestForm{{ $documentRequest->id }}" method="POST" action="{{ route('accountant.document_requests.collect', $documentRequest->id) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="collectRequestLabel{{ $documentRequest->id }}">Mark Collected</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                        </div>
                                                        <div class="modal-body text-left">
                                                            <div class="form-group"><label>Request Received by <span class="text-danger">*</span></label><input name="request_received_by" type="text" class="form-control" required maxlength="150"></div>
                                                            <div class="form-group"><label>Request Prepared by <span class="text-danger">*</span></label><input name="request_prepared_by" type="text" class="form-control" required maxlength="150"></div>
                                                            <div class="form-group"><label>Request Released by <span class="text-danger">*</span></label><input name="request_released_by" type="text" class="form-control" required maxlength="150"></div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                                                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#confirmCollectRequest{{ $documentRequest->id }}">Continue</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal fade collection-confirmation-modal" id="confirmCollectRequest{{ $documentRequest->id }}" tabindex="-1" role="dialog" aria-labelledby="confirmCollectRequestLabel{{ $documentRequest->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="confirmCollectRequestLabel{{ $documentRequest->id }}">Are you sure?</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                    </div>
                                                    <div class="modal-body">Mark this document request as collected now?</div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-dismiss="modal">Back</button>
                                                        <button type="button" class="btn btn-success confirm-collection-submit" data-form-id="collectDocumentRequestForm{{ $documentRequest->id }}">Confirm Collection</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No approved requests.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="rejected-requests">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Student</th><th>Requested By</th><th>Rejected</th><th>Reason</th></tr></thead>
                            <tbody>
                            @forelse($rejectedRequests as $documentRequest)
                                <tr>
                                    <td>{{ $documentRequest->student_name }}</td>
                                    <td>{{ $documentRequest->requester->name }}</td>
                                    <td>{{ $documentRequest->updated_at->format('m/d/Y h:i A') }}</td>
                                    <td>{{ $documentRequest->rejection_reason }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">No rejected requests.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.jQuery(document).on('show.bs.modal', '.collection-confirmation-modal', function () {
                var modal = window.jQuery(this);
                var zIndex = 1060 + (window.jQuery('.modal.show').length * 20);
                modal.css('z-index', zIndex);
                window.setTimeout(function () {
                    window.jQuery('.modal-backdrop').not('.collection-confirmation-backdrop').last()
                        .css('z-index', zIndex - 10)
                        .addClass('collection-confirmation-backdrop');
                }, 0);
            });

            window.jQuery(document).on('hidden.bs.modal', '.collection-confirmation-modal', function () {
                window.jQuery('.modal-backdrop.collection-confirmation-backdrop').remove();
                if (window.jQuery('.modal.show').length) {
                    window.jQuery('body').addClass('modal-open');
                }
            });

            document.querySelectorAll('.confirm-collection-submit').forEach(function (button) {
                button.addEventListener('click', function () {
                    var form = document.getElementById(button.dataset.formId);
                    if (form && (!form.reportValidity || form.reportValidity())) {
                        form.submit();
                    }
                });
            });

            var activeTab = window.location.hash;
            if (activeTab) {
                var tab = document.querySelector('.nav-tabs a[href="' + activeTab + '"]');
                if (tab) { window.jQuery(tab).tab('show'); }
            }
        });
    </script>
@endsection