@extends('layouts.master')
@section('page_title', 'Outbox')
@section('content')
    <style>
        .outbox-selection-toolbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 8px;
            padding: 0 0 12px;
        }

        .outbox-selection-counter {
            display: none;
            color: #6c757d;
            white-space: nowrap;
        }

        .outbox-select-table .select-column,
        .outbox-select-table .select-cell {
            display: none;
            width: 48px;
            text-align: center;
        }

        .outbox-select-table.select-mode .select-column,
        .outbox-select-table.select-mode .select-cell {
            display: table-cell;
        }

        .outbox-select-table.select-mode tbody tr.selectable-row {
            cursor: pointer;
        }

        .outbox-select-table.select-mode tbody tr.row-selected,
        .outbox-select-table.select-mode tbody tr.row-selected > td {
            background-color: #d9dee3 !important;
        }

        #outbox-sent-notices-table th,
        #outbox-sent-notices-table td {
            border-left: 0 !important;
            border-right: 0 !important;
        }

        #outbox-sent-notices-table .number-column {
            width: 44px;
            min-width: 44px;
            white-space: nowrap;
        }

        #outbox-sent-notices-table .type-column {
            width: 100px;
            min-width: 100px;
        }

        #outbox-sent-notices-table .sent-by-column {
            width: 110px;
            min-width: 110px;
        }

        #outbox-sent-notices-table .sent-at-column {
            width: 135px;
            min-width: 135px;
            white-space: nowrap;
        }

        #outbox-sent-notices-table .status-column {
            width: 128px;
            min-width: 128px;
            text-align: center;
        }

        #outbox-sent-notices-table .outbox-status-badge {
            display: block;
            box-sizing: border-box;
            width: 100px;
            margin: 0 auto;
            padding-right: 0.65rem;
            padding-left: 0.65rem;
            text-align: center;
            white-space: nowrap;
        }
    </style>
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Outbox</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#send-notice" class="nav-link active" data-toggle="tab">Send Account Creation Notice</a></li>
                <li class="nav-item"><a href="#sent-notices" class="nav-link" data-toggle="tab">Sent Notices</a></li>
            </ul>

            <div class="tab-content" style="padding-top:20px;">
                <div class="tab-pane fade show active" id="send-notice">
                    <form method="POST" action="{{ route('registrar.students.outbox.store') }}">
                        @csrf
                        <div class="form-group">
                            <label for="outbox-enrollment-select">Enrollment Submitter <span class="text-danger">*</span></label>
                            <select name="student_enrollment_id" id="outbox-enrollment-select" class="form-control select-search" data-placeholder="Type a name or username to search..." required>
                                <option value="">Choose an enrollment...</option>
                                @foreach($enrollments as $enrollment)
                                    <option value="{{ $enrollment->id }}" {{ old('student_enrollment_id') == $enrollment->id ? 'selected' : '' }}>
                                        {{ $enrollment->user->user_type === 'parent' ? 'Parent' : 'Temporary' }} account: {{ $enrollment->user->name }} ({{ $enrollment->user->username }}) - Applicant: {{ $enrollment->formatted_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <label class="mt-3">Accounts Created <span class="text-danger">*</span></label>
                        <div class="row mb-1 font-weight-semibold">
                            <div class="col-md-4">Account Type</div>
                            <div class="col-md-4">Username</div>
                            <div class="col-md-4">Password</div>
                        </div>
                        <div id="account-rows">
                            <div class="text-muted mb-3">Select an enrollment account to load its linked accounts.</div>
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary">Send Notice</button>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade" id="sent-notices">
                    <div class="outbox-selection-toolbar">
                        <span class="outbox-selection-counter" aria-live="polite">0 of {{ $notices->count() }} entries selected</span>
                        <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                        <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;" disabled>Delete Selected</button>
                        <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                    </div>
                    <form method="POST" action="{{ route('registrar.students.outbox.delete_selected') }}" class="outbox-delete-submit-form" style="display:none;">
                        @csrf
                    </form>
                    <div class="table-responsive">
                        <table id="outbox-sent-notices-table" class="table table-striped table-bordered outbox-select-table">
                            <thead>
                            <tr>
                                <th class="select-column"><i class="icon-checkmark-circle"></i></th>
                                <th class="number-column">#</th>
                                <th>Enrollment Submitter</th>
                                <th class="type-column">Account Type/s</th>
                                <th>Account Username/s</th>
                                <th class="sent-by-column">Sent By</th>
                                <th class="sent-at-column">Sent At</th>
                                <th class="status-column">Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($notices as $notice)
                                <tr class="selectable-row">
                                    <td class="select-cell"><input type="checkbox" value="{{ $notice->id }}" class="outbox-notice-checkbox"></td>
                                    <td class="number-column">{{ $loop->iteration }}</td>
                                    <td>{{ optional(optional($notice->studentEnrollment)->user)->username ?: optional($notice->studentEnrollment)->account_username }}</td>
                                    <td class="type-column">
                                        @foreach($notice->accounts as $account)
                                            {{ $account['account_type'] }}@if(!$loop->last)<br>@endif
                                        @endforeach
                                    </td>
                                    <td>
                                        @foreach($notice->accounts as $account)
                                            {{ $account['username'] }}@if(!$loop->last)<br>@endif
                                        @endforeach
                                    </td>
                                    <td class="sent-by-column">{{ optional($notice->sentBy)->name ?? 'Unknown' }}</td>
                                    <td class="sent-at-column">{{ $notice->created_at->format('m/d/Y h:i A') }}</td>
                                    <td class="status-column">
                                        @if($notice->acknowledged_at)
                                            <span class="badge badge-success outbox-status-badge">Acknowledged</span>
                                        @else
                                            <span class="badge badge-warning outbox-status-badge">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No notices sent yet.</td>
                                </tr>
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
            var rowsContainer = document.getElementById('account-rows');
            var enrollmentSelect = document.getElementById('outbox-enrollment-select');
            var table = document.getElementById('outbox-sent-notices-table');
            var selectionToolbar = document.querySelector('.outbox-selection-toolbar');
            var deleteForm = document.querySelector('.outbox-delete-submit-form');
            var linkedAccountsByEnrollment = @json($linkedAccounts);

            function renderLinkedAccounts() {
                var enrollmentId = enrollmentSelect.value;
                var accounts = linkedAccountsByEnrollment[enrollmentId] || [];
                rowsContainer.innerHTML = '';

                if (!accounts.length) {
                    rowsContainer.innerHTML = '<div class="text-muted mb-3">No linked accounts are available for this enrollment.</div>';
                    return;
                }

                accounts.forEach(function (account, index) {
                    var row = document.createElement('div');
                    row.className = 'row account-row mb-2';

                    [
                        ['account_type', account.account_type],
                        ['username', account.username],
                        ['password', account.password || 'Password unavailable'],
                    ].forEach(function (field, fieldIndex) {
                        var column = document.createElement('div');
                        column.className = 'col-md-4';
                        var input = document.createElement('input');
                        input.type = 'text';
                        input.className = 'form-control';
                        input.name = 'accounts[' + index + '][' + field[0] + ']';
                        input.value = field[1] || '';
                        input.readOnly = true;
                        input.required = true;
                        column.appendChild(input);
                        row.appendChild(column);
                    });

                    rowsContainer.appendChild(row);
                });
            }

            window.jQuery(enrollmentSelect).on('change', renderLinkedAccounts);
            if (enrollmentSelect.value) {
                renderLinkedAccounts();
            }

            if (!table || !selectionToolbar || !deleteForm) {
                return;
            }

            var toggleButton = selectionToolbar.querySelector('.toggle-select-mode');
            var deleteButton = selectionToolbar.querySelector('.delete-selected');
            var selectAllButton = selectionToolbar.querySelector('.select-all');
            var clearButton = selectionToolbar.querySelector('.clear-selection');
            var counter = selectionToolbar.querySelector('.outbox-selection-counter');
            var checkboxes = Array.prototype.slice.call(table.querySelectorAll('.outbox-notice-checkbox'));

            function selectedCount() {
                return checkboxes.filter(function (checkbox) { return checkbox.checked; }).length;
            }

            function syncRowState(checkbox) {
                checkbox.closest('tr').classList.toggle('row-selected', checkbox.checked);
            }

            function updateSelection() {
                counter.textContent = selectedCount() + ' of ' + checkboxes.length + ' entries selected';
                deleteButton.disabled = selectedCount() === 0;
            }

            function clearSelection() {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = false;
                    syncRowState(checkbox);
                });
                updateSelection();
            }

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    syncRowState(checkbox);
                    updateSelection();
                });
            });

            table.addEventListener('click', function (event) {
                if (!table.classList.contains('select-mode') || event.target.closest('a, button, input')) {
                    return;
                }
                var row = event.target.closest('tr.selectable-row');
                var checkbox = row && row.querySelector('.outbox-notice-checkbox');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    syncRowState(checkbox);
                    updateSelection();
                }
            });

            toggleButton.addEventListener('click', function () {
                var selecting = !table.classList.contains('select-mode');
                table.classList.toggle('select-mode', selecting);
                toggleButton.textContent = selecting ? 'Cancel' : 'Select';
                deleteButton.style.display = selecting ? 'inline-block' : 'none';
                selectAllButton.style.display = selecting ? 'inline-block' : 'none';
                clearButton.style.display = selecting ? 'inline-block' : 'none';
                counter.style.display = selecting ? 'inline-block' : 'none';
                if (!selecting) {
                    clearSelection();
                }
            });

            selectAllButton.addEventListener('click', function () {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = true;
                    syncRowState(checkbox);
                });
                updateSelection();
            });

            clearButton.addEventListener('click', clearSelection);

            deleteButton.addEventListener('click', function () {
                var selected = checkboxes.filter(function (checkbox) { return checkbox.checked; });
                if (!selected.length || !window.confirm('Delete ' + selected.length + ' selected notices?')) {
                    return;
                }

                selected.forEach(function (checkbox) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'notice_ids[]';
                    input.value = checkbox.value;
                    deleteForm.appendChild(input);
                });
                deleteForm.submit();
            });

            updateSelection();
        });
    </script>
@endsection

