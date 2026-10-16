@extends('layouts.master')
@section('page_title', 'Manage Classes')
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

        .class-table-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .class-table-controls .bulk-toolbar {
            margin-bottom: 0;
        }

        .class-table-controls .dataTables_filter {
            margin: 0;
        }

        #manage-classes-table .action-column {
            width: 76px !important;
            min-width: 76px;
            max-width: 76px;
            white-space: nowrap;
        }

        #manage-classes-table .class-code-column {
            width: 128px !important;
            min-width: 128px;
            max-width: 128px;
        }

        #manage-classes-table tbody .class-code-column {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #manage-classes-table thead th {
            padding-right: 2.5rem;
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
        <div class="card-header header-elements-inline">
            <h6 class="card-title">Manage Classes</h6>
        </div>

        
        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#all-classes" class="nav-link active" data-toggle="tab">Manage Classes</a></li>
                <li class="nav-item"><a href="#new-class" class="nav-link" data-toggle="tab"><i class="icon-plus2"></i> Create New Class</a></li>
            </ul>

            <div class="tab-content">
                    <div class="tab-pane fade show active" id="all-classes">
                        <div class="bulk-delete-form">
                            @if(Qs::userIsAdministrator() || Qs::userIsRegistrar())
                                <div class="bulk-toolbar">
                                    <span class="selection-counter text-muted" aria-live="polite">0 of {{ $my_classes->count() }} entries selected</span>
                                    <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                                    <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;">Delete Selected</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                                </div>
                                <form method="POST" action="{{ route('classes.delete_selected') }}" class="bulk-delete-submit-form" style="display:none;">
                                    @csrf
                                </form>
                            @endif

                        <table id="manage-classes-table" class="table bulk-select-table">
                            <thead>
                            <tr>
                                @if(Qs::userIsAdministrator() || Qs::userIsRegistrar())
                                    <th class="select-column"><i class="icon-checkmark-circle"></i></th>
                                @endif
                                <th class="class-code-column">Class Code</th>
                                <th>Name</th>
                                <th>Class Type</th>
                                <th class="action-column">Edit</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($my_classes as $c)
                                <tr class="selectable-row">
                                    @if(Qs::userIsAdministrator() || Qs::userIsRegistrar())
                                        <td class="select-cell">
                                            <input type="checkbox" name="class_ids[]" value="{{ $c->id }}" class="bulk-select-checkbox">
                                        </td>
                                    @endif
                                    <td class="class-code-column">{{ $c->code ?? '' }}</td>
                                    <td>{{ $c->name }}</td>
                                    <td>{{ optional($c->class_type)->name ?: 'Unassigned' }}</td>
                                    <td class="text-center action-cell action-column">
                                        <div class="list-icons">
                                            <a href="{{ route('classes.edit', $c->id) }}" class="list-icons-item" title="Edit {{ $c->name }}">
                                                <i class="icon-paragraph-justify3"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>

                <div class="tab-pane fade" id="new-class">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info border-0 alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>

                                <span>When a class is created, a Section will be automatically created for the class, you can edit it or add more sections to the class at <a target="_blank" href="{{ route('sections.index') }}">Manage Sections</a></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <form class="ajax-store" method="post" action="{{ route('classes.store') }}">
                                @csrf
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">Name <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input name="name" value="{{ old('name') }}" required type="text" class="form-control" placeholder="Name of Class">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">Class Code <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input name="code" value="{{ old('code') }}" required type="text" class="form-control" placeholder="Class Code">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="class_type_id" class="col-lg-3 col-form-label font-weight-semibold">Class Type</label>
                                    <div class="col-lg-9">
                                        <select required data-placeholder="Select Class Type" class="form-control select" name="class_type_id" id="class_type_id">
                                            @foreach($class_types as $ct)
                                                <option {{ old('class_type_id') == $ct->id ? 'selected' : '' }} value="{{ $ct->id }}">{{ $ct->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <button id="ajax-btn" type="submit" class="btn btn-primary">Submit form <i class="icon-paperplane ml-2"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{--Class List Ends--}}

@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var selector = '#manage-classes-table';

            $(selector).DataTable({
                autoWidth: false,
                lengthChange: false,
                paging: false,
                info: false,
                columnDefs: [{ targets: -1, width: '76px', orderable: false }],
                dom: '<"class-table-controls"f>rt'
            });

            var controlsRow = document.querySelector('#manage-classes-table_wrapper .class-table-controls');
            var bulkToolbar = document.querySelector('.bulk-toolbar');
            if (controlsRow && bulkToolbar) {
                controlsRow.appendChild(bulkToolbar);
            }

            document.querySelectorAll('.bulk-delete-form').forEach(function (form) {
                var table = form.querySelector('.bulk-select-table');
                var submitForm = form.querySelector('.bulk-delete-submit-form');
                var toggleButton = form.querySelector('.toggle-select-mode');
                var deleteButton = form.querySelector('.delete-selected');
                var selectAllButton = form.querySelector('.select-all');
                var clearButton = form.querySelector('.clear-selection');
                var selectionCounter = form.querySelector('.selection-counter');
                var checkboxes = Array.prototype.slice.call(form.querySelectorAll('.bulk-select-checkbox'));

                if (!table || !toggleButton || !deleteButton || !selectAllButton || !clearButton || !submitForm) {
                    return;
                }

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

                    row.classList.toggle('row-selected', checkbox.checked);
                }

                function clearSelection() {
                    checkboxes.forEach(function (checkbox) {
                        checkbox.checked = false;
                        syncRowState(checkbox);
                    });
                    updateDeleteButtonState();
                }

                checkboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', function () {
                        syncRowState(checkbox);
                        updateDeleteButtonState();
                    });
                });

                table.addEventListener('click', function (event) {
                    if (!table.classList.contains('select-mode') || event.target.closest('.action-cell')) {
                        return;
                    }

                    if (event.target.closest('a') || event.target.closest('button') || event.target.closest('input')) {
                        return;
                    }

                    var row = event.target.closest('tr.selectable-row');
                    var checkbox = row && row.querySelector('.bulk-select-checkbox');
                    if (!checkbox) {
                        return;
                    }

                    checkbox.checked = !checkbox.checked;
                    syncRowState(checkbox);
                    updateDeleteButtonState();
                });

                toggleButton.addEventListener('click', function () {
                    var selectMode = table.classList.toggle('select-mode');

                    deleteButton.style.display = selectMode ? 'inline-block' : 'none';
                    selectAllButton.style.display = selectMode ? 'inline-block' : 'none';
                    clearButton.style.display = selectMode ? 'inline-block' : 'none';
                    if (selectionCounter) {
                        selectionCounter.style.display = selectMode ? 'inline-block' : 'none';
                    }
                    toggleButton.textContent = selectMode ? 'Cancel' : 'Select';

                    if (!selectMode) {
                        clearSelection();
                    }
                });

                selectAllButton.addEventListener('click', function () {
                    checkboxes.forEach(function (checkbox) {
                        checkbox.checked = true;
                        syncRowState(checkbox);
                    });
                    updateDeleteButtonState();
                });

                clearButton.addEventListener('click', clearSelection);

                deleteButton.addEventListener('click', function () {
                    var totalSelected = selectedCount();
                    if (totalSelected === 0) {
                        return;
                    }

                    swal({
                        title: 'Confirm Deletion',
                        text: 'You are about to delete ' + totalSelected + ' classes and all associated records, including sections, subjects, student records, marks, exams, books, payments, and Timetables. This cannot be undone. Proceed?',
                        icon: 'warning',
                        dangerMode: true,
                        buttons: {
                            cancel: 'Cancel',
                            confirm: 'Proceed'
                        }
                    }).then(function (confirmed) {
                        if (!confirmed) {
                            return;
                        }

                        submitForm.querySelectorAll('input[name="class_ids[]"]').forEach(function (input) {
                            input.remove();
                        });

                        checkboxes.filter(function (checkbox) {
                            return checkbox.checked;
                        }).forEach(function (checkbox) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'class_ids[]';
                            input.value = checkbox.value;
                            submitForm.appendChild(input);
                        });

                        submitForm.submit();
                    });
                });

                updateDeleteButtonState();
            });
        });
    </script>
@endsection
