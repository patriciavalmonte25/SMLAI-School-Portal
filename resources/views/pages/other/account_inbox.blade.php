@extends('layouts.master')
@section('page_title', 'Inbox')
@section('content')
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Inbox</h6>
            {!! Qs::getPanelOptions() !!}
        </div>
        <div class="card-body">
            @foreach($billingNotices as $delivery)
                <div class="card {{ $delivery->notice->paid_at ? 'border-success' : 'border-danger' }} mb-3">
                    <div class="card-header {{ $delivery->notice->paid_at ? 'bg-success text-white' : 'bg-danger text-white' }}">Payment Notice</div>
                    <div class="card-body">
                        <p>Good day {{ $delivery->parent_name }}.</p>
                        <p>Student: {{ $delivery->notice->student_name }}, {{ $delivery->notice->student_class }}{{ $delivery->notice->student_section ? ' - '.$delivery->notice->student_section : '' }}</p>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered" style="table-layout:fixed;">
                                <thead>
                                <tr>
                                    @foreach($delivery->notice->layout['columns'] ?? [] as $column)
                                        <th style="padding:8px 12px; white-space:normal; overflow-wrap:anywhere;">{{ $column['title'] ?? '' }}</th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($delivery->notice->layout['rows'] ?? [] as $row)
                                    <tr>
                                        @foreach($delivery->notice->layout['columns'] ?? [] as $column)
                                            <td style="padding:8px 12px; white-space:normal; overflow-wrap:anywhere;">{{ $row['cells'][$column['key']] ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="mb-0">Please go to the school cashier to pay the presented amount.</p>
                        @if($delivery->notice->paid_at)
                            <p class="font-weight-bold mb-0 mt-2">Paid on {{ $delivery->notice->paid_at->format('m/d/Y h:i A') }}.</p>
                        @endif
                    </div>
                </div>
            @endforeach

            @forelse($notices as $delivery)
                @php($accountDetails = collect($delivery->notice->accounts)->firstWhere('username', $delivery->username))
                <div class="card border-primary mb-3">
                    <div class="card-header bg-primary text-white">Account Created Notice</div>
                    <div class="card-body">
                        <p>Good day. The account details for {{ $delivery->notice->studentEnrollment->formatted_name }} are as follows:</p>
                        <ul>
                            <li>Account Type: {{ $delivery->account_type }}. Username: {{ $delivery->username }}.</li>
                        </ul>
                        <p class="mb-0">Password: {{ $accountDetails['password'] ?? 'N/A' }}. Please change your password upon logging in. Do not forget these details.</p>
                        @if(Auth::user()->user_type === 'parent')
                            <form method="POST" action="{{ route('account_inbox.account_creation_notices.acknowledge', ['recipient_id' => $delivery->id]) }}">
                                @csrf
                                <div class="form-group form-check mt-4">
                                    <input type="checkbox" class="form-check-input" id="parentAccountNoticeAck{{ $delivery->id }}" name="acknowledged" value="1">
                                    <label class="form-check-label" for="parentAccountNoticeAck{{ $delivery->id }}">By checking this, you acknowledge that this account has been created successfully, and the pending submission and this notice will be removed.</label>
                                </div>
                                <div class="text-right">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                @if($billingNotices->isEmpty())
                    <div class="alert alert-info mb-0">No notices found.</div>
                @endif
            @endforelse
        </div>
    </div>
@endsection
