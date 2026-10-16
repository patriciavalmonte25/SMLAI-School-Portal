@extends('layouts.master')
@section('page_title', 'Students')
@section('content')
    <style>
        .student-list-heading-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            width: 100%;
        }

        .student-list-heading-row .bulk-toolbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 8px;
        }

        .student-selection-counter {
            display: none;
            align-self: center;
            white-space: nowrap;
            margin-right: auto;
        }

        #registrar-students-table .action-column {
            width: 76px !important;
            min-width: 76px;
            max-width: 76px;
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

        .select-mode tbody tr.row-selected,
        .select-mode tbody tr.row-selected > td {
            background-color: #d9dee3 !important;
        }
    </style>

    <div class="card">
        <div class="card-header bg-white header-elements-inline student-list-heading-row">
            <h6 class="card-title mb-0">Students</h6>
            <div class="bulk-toolbar">
                <span class="student-selection-counter text-muted" aria-live="polite" style="display:none;">0 of {{ $students->count() }} entries selected</span>
                <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;">Delete Selected</button>
                <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
            </div>
            {!! Qs::getPanelOptions() !!}
        </div>
        <form method="POST" action="{{ route('registrar.students.delete_users') }}" class="bulk-delete-submit-form" style="display:none;">
            @csrf
        </form>
        <div class="card-body">
            <div class="form-group mb-2" style="width:100%; max-width:360px;">
                <input type="search" class="form-control form-control-sm" data-list-filter="registrar-students-table" placeholder="Search students..." aria-label="Search students">
                <small class="form-text text-muted" data-list-filter-empty-for="registrar-students-table" hidden>No matching students.</small>
            </div>
            <table id="registrar-students-table" class="table table-striped bulk-select-table">
                <thead>
                <tr>
                    <th class="select-column"><i class="icon-checkmark-circle"></i></th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Grade</th>
                    <th class="action-column">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($students as $student)
                    <tr class="selectable-row">
                        <td class="select-cell"><input type="checkbox" name="user_ids[]" value="{{ $student->id }}" class="bulk-select-checkbox"></td>
                        <td><a href="{{ route('users.show', Qs::hash($student->id)) }}" class="user-profile-link"><u>{{ $student->name }}</u></a></td>
                        <td>{{ $student->username }}</td>
                        <td>{{ optional(optional($student->student_record)->my_class)->name ?: (optional(optional($student->student_enrollment)->my_class)->name ?: optional($student->student_enrollment)->grade_enrolling ?: 'No class assigned') }}</td>
                        <td class="text-center action-column">
                            @if($student->student_record)
                                <a href="{{ route('students.edit', Qs::hash($student->student_record->id)) }}" class="list-icons-item" title="Edit {{ $student->name }}"><i class="icon-paragraph-justify3"></i></a>
                            @else
                                <a href="{{ route('users.edit', Qs::hash($student->id)) }}" class="list-icons-item" title="Edit {{ $student->name }}"><i class="icon-paragraph-justify3"></i></a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No students found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var table = document.getElementById('registrar-students-table');
            var form = document.querySelector('.bulk-delete-submit-form');
            var toggleButton = document.querySelector('.toggle-select-mode');
            var counter = document.querySelector('.student-selection-counter');
            var deleteButton = document.querySelector('.delete-selected');
            var selectAllButton = document.querySelector('.select-all');
            var clearButton = document.querySelector('.clear-selection');
            var checkboxes = Array.prototype.slice.call(document.querySelectorAll('.bulk-select-checkbox'));

            if (!table || !form || !toggleButton || !counter || !deleteButton || !selectAllButton || !clearButton) {
                return;
            }

            function selectedCount() {
                return checkboxes.filter(function (checkbox) { return checkbox.checked; }).length;
            }

            function updateSelection() {
                counter.textContent = selectedCount() + ' of ' + checkboxes.length + ' entries selected';
                deleteButton.disabled = selectedCount() === 0;
            }

            function setRowState(checkbox) {
                checkbox.closest('tr').classList.toggle('row-selected', checkbox.checked);
            }

            function clearSelection() {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = false;
                    setRowState(checkbox);
                });
                updateSelection();
            }

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    setRowState(checkbox);
                    updateSelection();
                });
            });

            table.addEventListener('click', function (event) {
                if (!table.classList.contains('select-mode') || event.target.closest('a, button, input, .action-cell')) {
                    return;
                }
                var row = event.target.closest('tr.selectable-row');
                var checkbox = row && row.querySelector('.bulk-select-checkbox');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    setRowState(checkbox);
                    updateSelection();
                }
            });

            toggleButton.addEventListener('click', function () {
                var selecting = !table.classList.contains('select-mode');
                table.classList.toggle('select-mode', selecting);
                counter.style.display = selecting ? 'inline-block' : 'none';
                toggleButton.textContent = selecting ? 'Cancel' : 'Select';
                deleteButton.style.display = selecting ? 'inline-block' : 'none';
                selectAllButton.style.display = selecting ? 'inline-block' : 'none';
                clearButton.style.display = selecting ? 'inline-block' : 'none';
                if (!selecting) {
                    clearSelection();
                }
            });

            selectAllButton.addEventListener('click', function () {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = true;
                    setRowState(checkbox);
                });
                updateSelection();
            });

            clearButton.addEventListener('click', clearSelection);

            deleteButton.addEventListener('click', function () {
                var selectedIds = checkboxes.filter(function (checkbox) { return checkbox.checked; });
                if (!selectedIds.length) {
                    return;
                }
                if (!window.confirm('Delete ' + selectedIds.length + ' selected student accounts?')) {
                    return;
                }

                form.querySelectorAll('input[name="user_ids[]"]').forEach(function (input) { input.remove(); });
                selectedIds.forEach(function (checkbox) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'user_ids[]';
                    input.value = checkbox.value;
                    form.appendChild(input);
                });
                form.submit();
            });

            updateSelection();
        });
    </script>
@endsection