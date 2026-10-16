@extends('layouts.master')
@section('page_title', 'Inbox')
@section('content')
    <style>
        .bulk-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 15px;
        }

        .selection-counter {
            display: none;
            align-self: center;
            white-space: nowrap;
            cursor: default;
        }

        .bulk-select-table th,
        .bulk-select-table td {
            border-left: 0 !important;
            border-right: 0 !important;
        }

        #pending-forms .pending-grade-column {
            width: 100px;
            min-width: 100px;
        }

        #pending-forms .pending-action-column {
            width: 84px;
            min-width: 84px;
            white-space: nowrap;
        }

        #approved-forms .approved-at-column {
            width: 150px;
            min-width: 150px;
            white-space: nowrap;
        }

        #approved-forms .approved-status-column {
            width: 100px;
            min-width: 100px;
            text-align: center;
            white-space: nowrap;
        }

        .select-mode .select-column,
        .select-mode .select-cell {
            display: table-cell !important;
        }

        .select-column,
        .select-cell {
            display: none;
            width: 48px;
            text-align: center;
        }

        .select-mode tbody tr.selectable-row {
            cursor: pointer;
        }

        .select-mode tbody tr.row-selected {
            background-color: #d9dee3;
        }

        .select-mode tbody tr.row-selected > td {
            background-color: #d9dee3 !important;
        }
    </style>
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Inbox</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#pending-forms" class="nav-link active" data-toggle="tab">Pending Forms</a></li>
                <li class="nav-item"><a href="#approved-forms" class="nav-link" data-toggle="tab">Approved Forms</a></li>
                <li class="nav-item"><a href="#denied-forms" class="nav-link" data-toggle="tab">Denied Forms</a></li>
            </ul>

            <div class="tab-content" style="padding-top:20px;">
                <div class="tab-pane fade show active" id="pending-forms">
                    <div class="bulk-delete-form" data-tab-name="Pending Forms">
                        <div class="bulk-toolbar">
                            <span class="selection-counter text-muted" aria-live="polite">0 of {{ $pendingEnrollments->count() }} entries selected</span>
                            <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                            <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;">Delete Selected</button>
                            <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                        </div>
                    <form method="POST" action="{{ route('registrar.students.delete_selected') }}" class="bulk-delete-submit-form" style="display:none;">
                        @csrf
                    </form>
                    <table class="table table-striped table-bordered bulk-select-table">
                        <thead>
                        <tr>
                            <th class="select-column">
                                <i class="icon-checkmark-circle"></i>
                            </th>
                            <th>Name</th>
                            <th>Username</th>
                            <th class="pending-grade-column">Grade Level</th>
                            <th>Submitted</th>
                            <th class="text-center pending-action-column">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($pendingEnrollments as $enrollment)
                            <tr class="selectable-row">
                                <td class="select-cell">
                                    <input type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->id }}" class="bulk-select-checkbox">
                                </td>
                                <td>{{ $enrollment->formatted_name }}</td>
                                <td>{{ $enrollment->account_username ?? 'N/A' }}</td>
                                <td class="pending-grade-column">{{ $enrollment->grade_enrolling }}</td>
                                <td>{{ $enrollment->created_at->format('m/d/Y h:i A') }}</td>
                                <td class="text-center action-cell pending-action-column">
                                    <a href="{{ route('registrar.students.view_form', ['enrollment_id' => $enrollment->id]) }}" class="btn btn-primary btn-sm">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No pending enrollment forms.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="approved-forms">
                    <div class="bulk-delete-form" data-tab-name="Approved Forms">
                        <div class="bulk-toolbar">
                            <span class="selection-counter text-muted" aria-live="polite">0 of {{ $approvedEnrollments->count() }} entries selected</span>
                            <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                            <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;">Delete Selected</button>
                            <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                        </div>
                    <form method="POST" action="{{ route('registrar.students.delete_selected') }}" class="bulk-delete-submit-form" style="display:none;">
                        @csrf
                    </form>
                    <table class="table table-striped table-bordered bulk-select-table">
                        <thead>
                        <tr>
                            <th class="select-column">
                                <i class="icon-checkmark-circle"></i>
                            </th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Approved By</th>
                            <th class="approved-at-column">Approved At</th>
                            <th class="approved-status-column">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($approvedEnrollments as $enrollment)
                            <tr class="selectable-row">
                                <td class="select-cell">
                                    <input type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->id }}" class="bulk-select-checkbox">
                                </td>
                                <td>{{ $enrollment->formatted_name }}</td>
                                <td>{{ $enrollment->account_username ?? 'N/A' }}</td>
                                <td>{{ optional($enrollment->approvedBy)->name ?? 'Unknown' }}</td>
                                <td class="approved-at-column">{{ ($enrollment->approved_at ?: $enrollment->updated_at ?: $enrollment->created_at)->format('m/d/Y h:i A') }}</td>
                                <td class="approved-status-column">
                                    @if($enrollment->enrolled_at)
                                        <span>Enrolled</span>
                                    @else
                                        <div class="dropdown">
                                            <a href="#" class="list-icons-item" data-toggle="dropdown" title="Enrollment actions">
                                                <i class="icon-paragraph-justify3"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                @php
                                                    $studentAccountCount = $enrollment->accounts->where('pivot.account_type', 'Student')->count();
                                                    $parentAccountCount = $enrollment->accounts->where('pivot.account_type', 'Parent')->count();
                                                    $totalAccountCount = $enrollment->accounts->count();
                                                    $studentAccountMessage = $studentAccountCount . ' ' . ($studentAccountCount === 1 ? 'Student account has' : 'Student accounts have') . ' already been made for this enrollment form; ' . $totalAccountCount . ' total ' . ($totalAccountCount === 1 ? 'account' : 'accounts') . '.';
                                                    $parentAccountMessage = $parentAccountCount . ' ' . ($parentAccountCount === 1 ? 'Parent account has' : 'Parent accounts have') . ' already been made for this enrollment form; ' . $totalAccountCount . ' total ' . ($totalAccountCount === 1 ? 'account' : 'accounts') . '.';
                                                @endphp
                                                <a class="dropdown-item" href="{{ route('registrar.students.enroll', ['enrollment_id' => $enrollment->id]) }}">
                                                    Create Student Account
                                                    @if($studentAccountCount > 0)
                                                        <i class="icon-info22 text-muted ml-1" role="img" tabindex="0" title="{{ $studentAccountMessage }}" aria-label="{{ $studentAccountMessage }}"></i>
                                                    @endif
                                                </a>
                                                <a class="dropdown-item" href="{{ route('users.create', ['type' => 'parent', 'enrollment_id' => $enrollment->id]) }}">
                                                    Create Parent Account
                                                    @if($parentAccountCount > 0)
                                                        <i class="icon-info22 text-muted ml-1" role="img" tabindex="0" title="{{ $parentAccountMessage }}" aria-label="{{ $parentAccountMessage }}"></i>
                                                    @endif
                                                </a>
                                                @php($accountTypes = $enrollment->accounts->pluck('pivot.account_type')->unique())
                                                <button type="button" class="dropdown-item" data-toggle="modal" data-target="#markEnrolledModal{{ $enrollment->id }}" @if(!$accountTypes->contains('Student') || !$accountTypes->contains('Parent')) disabled title="Create and link a Student and Parent account first" @endif>Mark Enrolled</button>
                                            </div>
                                        </div>
                                        <div class="modal fade" id="markEnrolledModal{{ $enrollment->id }}" tabindex="-1" role="dialog" aria-labelledby="markEnrolledModalLabel{{ $enrollment->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('registrar.students.mark_enrolled', ['enrollment_id' => $enrollment->id]) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="markEnrolledModalLabel{{ $enrollment->id }}">Mark Enrolled</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                        </div>
                                                        <div class="modal-body">Are you sure? Marking {{ $enrollment->formatted_name }} as enrolled cannot be reverted.</div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Proceed</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No approved enrollment forms.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="denied-forms">
                    <div class="bulk-delete-form" data-tab-name="Denied Forms">
                        <div class="bulk-toolbar">
                            <span class="selection-counter text-muted" aria-live="polite">0 of {{ $deniedEnrollments->count() }} entries selected</span>
                            <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                            <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;">Delete Selected</button>
                            <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                        </div>
                    <form method="POST" action="{{ route('registrar.students.delete_selected') }}" class="bulk-delete-submit-form" style="display:none;">
                        @csrf
                    </form>
                    <table class="table table-striped table-bordered bulk-select-table">
                        <thead>
                        <tr>
                            <th class="select-column">
                                <i class="icon-checkmark-circle"></i>
                            </th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Denied By</th>
                            <th>Reason</th>
                            <th>Denied At</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($deniedEnrollments as $enrollment)
                            <tr class="selectable-row">
                                <td class="select-cell">
                                    <input type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->id }}" class="bulk-select-checkbox">
                                </td>
                                <td>{{ $enrollment->formatted_name }}</td>
                                <td>{{ $enrollment->account_username ?? 'N/A' }}</td>
                                <td>{{ optional($enrollment->deniedBy)->name ?? 'Unknown' }}</td>
                                <td>{{ $enrollment->denied_reason ?? 'No reason provided.' }}</td>
                                <td>{{ ($enrollment->denied_at ?: $enrollment->updated_at ?: $enrollment->created_at)->format('m/d/Y h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No denied enrollment forms.</td>
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
            document.querySelectorAll('.bulk-delete-form').forEach(function (form) {
                var table = form.querySelector('.bulk-select-table');
                var submitForm = form.querySelector('.bulk-delete-submit-form');
                var toggleButton = form.querySelector('.toggle-select-mode');
                var deleteButton = form.querySelector('.delete-selected');
                var selectAllButton = form.querySelector('.select-all');
                var clearButton = form.querySelector('.clear-selection');
                var selectionCounter = form.querySelector('.selection-counter');
                var checkboxes = Array.prototype.slice.call(form.querySelectorAll('.bulk-select-checkbox'));

                function selectedCount() {
                    return checkboxes.filter(function (checkbox) {
                        return checkbox.checked;
                    }).length;
                }

                function updateDeleteButtonState() {
                    deleteButton.disabled = selectedCount() === 0;
                    if (selectionCounter) {
                        selectionCounter.textContent = selectedCount() + ' of ' + checkboxes.length + ' entries selected';
                    }
                }

                function syncRowState(checkbox) {
                    var row = checkbox.closest('tr');

                    if (!row) {
                        return;
                    }

                    if (checkbox.checked) {
                        row.classList.add('row-selected');
                    } else {
                        row.classList.remove('row-selected');
                    }
                }

                function enableSelectMode() {
                    table.classList.add('select-mode');
                    toggleButton.textContent = 'Cancel';
                    deleteButton.style.display = 'inline-block';
                    selectAllButton.style.display = 'inline-block';
                    clearButton.style.display = 'inline-block';
                    if (selectionCounter) {
                        selectionCounter.style.display = 'inline-block';
                    }
                    updateDeleteButtonState();
                }

                function selectAllRows() {
                    checkboxes.forEach(function (checkbox) {
                        checkbox.checked = true;
                        syncRowState(checkbox);
                    });

                    updateDeleteButtonState();
                }

                function disableSelectMode() {
                    checkboxes.forEach(function (checkbox) {
                        checkbox.checked = false;
                        syncRowState(checkbox);
                    });

                    table.classList.remove('select-mode');
                    toggleButton.textContent = 'Select';
                    deleteButton.style.display = 'none';
                    selectAllButton.style.display = 'none';
                    clearButton.style.display = 'none';
                    if (selectionCounter) {
                        selectionCounter.style.display = 'none';
                    }
                    deleteButton.disabled = true;
                }

                toggleButton.addEventListener('click', function () {
                    if (table.classList.contains('select-mode')) {
                        disableSelectMode();
                    } else {
                        enableSelectMode();
                    }
                });
                selectAllButton.addEventListener('click', selectAllRows);
                clearButton.addEventListener('click', disableSelectMode);

                checkboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', function (event) {
                        syncRowState(event.target);
                        updateDeleteButtonState();
                    });

                    var row = checkbox.closest('tr');

                    if (row) {
                        row.addEventListener('click', function (event) {
                            if (!table.classList.contains('select-mode')) {
                                return;
                            }

                            if (event.target.closest('input, button, a, textarea, label, .modal, .close, .action-cell')) {
                                return;
                            }

                            checkbox.checked = !checkbox.checked;
                            syncRowState(checkbox);
                            updateDeleteButtonState();
                        });
                    }

                    syncRowState(checkbox);
                });

                deleteButton.addEventListener('click', function () {
                    var totalSelected = selectedCount();

                    if (totalSelected === 0) {
                        return;
                    }

                    swal({
                        title: 'Confirm Deletion',
                        text: 'You are about to delete ' + totalSelected + ' forms. Proceed?',
                        icon: 'warning',
                        dangerMode: true,
                        buttons: {
                            cancel: 'Cancel',
                            confirm: 'Proceed'
                        }
                    }).then(function (confirmed) {
                        if (confirmed) {
                            submitForm.querySelectorAll('input[name="enrollment_ids[]"]').forEach(function (input) {
                                input.remove();
                            });

                            checkboxes.forEach(function (checkbox) {
                                if (checkbox.checked) {
                                    var input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = 'enrollment_ids[]';
                                    input.value = checkbox.value;
                                    submitForm.appendChild(input);
                                }
                            });

                            submitForm.submit();
                        }
                    });
                });

                updateDeleteButtonState();
            });
        });
    </script>
@endsection
