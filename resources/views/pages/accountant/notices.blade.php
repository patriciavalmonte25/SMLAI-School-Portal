@extends('layouts.master')
@section('page_title', 'Notices')
@section('content')
    <style>
        .notice-select-column,
        .notice-select-cell {
            display: none;
            width: 48px;
            text-align: center;
        }

        .notice-selection-table.notice-select-mode .notice-select-column,
        .notice-selection-table.notice-select-mode .notice-select-cell {
            display: table-cell !important;
        }

        .notice-selection-table.notice-select-mode tbody tr.selectable-row {
            cursor: pointer;
        }

        .notice-selection-table.notice-select-mode tbody tr.row-selected,
        .notice-selection-table.notice-select-mode tbody tr.row-selected > td {
            background-color: #d9dee3 !important;
        }

        .notice-selection-toolbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .notice-selection-toolbar .selection-counter {
            display: none;
        }
    </style>
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Notices</h6>
            {!! Qs::getPanelOptions() !!}
        </div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#create-notice" class="nav-link active" data-toggle="tab">Create Notice</a></li>
                <li class="nav-item"><a href="#manage-notices" class="nav-link" data-toggle="tab">Manage Notices</a></li>
                <li class="nav-item"><a href="#paid-notices" class="nav-link" data-toggle="tab">Paid Notices</a></li>
            </ul>

            <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="create-notice">
                    <form method="POST" action="{{ route('accountant.notices.store') }}" id="create-billing-notice-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="notice-class">Class <span class="text-danger">*</span></label>
                                <select id="notice-class" name="class_id" class="form-control" required>
                                    <option value="">Choose Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="notice-student">Student <span class="text-danger">*</span></label>
                                <select id="notice-student" name="student_user_id" class="form-control" disabled required>
                                    <option value="">Choose a class first</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Parent</label>
                            <div id="notice-parents" class="form-control" style="min-height:38px; height:auto;">Select a student to view linked parents.</div>
                        </div>

                        <div class="row align-items-end mb-2">
                            <div class="col-md-4 form-group mb-2">
                                <select id="notice-grade-import" class="form-control">
                                    <option value="">Choose grade...</option>
                                    @foreach($billingLists as $billingList)
                                        <option value="{{ $billingList->grade_key }}">{{ $billingList->grade_label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <style>
                            #notice-lines-table th,
                            #notice-lines-table td {
                                padding: 8px 12px;
                                vertical-align: middle;
                            }

                            #notice-lines-table {
                                table-layout: auto;
                            }

                            #notice-lines-table .notice-header-input,
                            #notice-lines-table .notice-cell-input {
                                border: 0;
                                border-radius: 0;
                                box-shadow: none;
                                outline: none;
                                background: transparent;
                                display: inline-block;
                                vertical-align: middle;
                                width: 100%;
                                height: 38px;
                                min-height: 38px;
                                padding: 8px 10px;
                                line-height: 1.4;
                                white-space: pre-wrap;
                                overflow-wrap: anywhere;
                                resize: none;
                                overflow: hidden;
                            }

                            #notice-lines-table .notice-header-input:focus,
                            #notice-lines-table .notice-cell-input:focus {
                                border: 0;
                                box-shadow: none;
                                outline: none;
                            }
                        </style>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="notice-lines-table">
                                <thead id="notice-lines-head"></thead>
                                <tbody id="notice-lines"></tbody>
                            </table>
                        </div>
                        <div id="notice-layout-fields"></div>
                        <div id="notice-table-toolbar" class="d-flex justify-content-between align-items-center flex-wrap mt-2 mb-3" style="gap:8px;">
                            <div class="btn-group" role="group" aria-label="Notice edit history">
                                <button type="button" class="btn btn-light btn-sm" id="undo-notice-change" disabled>Undo</button>
                                <button type="button" class="btn btn-light btn-sm" id="redo-notice-change" disabled>Redo</button>
                            </div>
                            <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary btn-sm" id="notice-add-row"><i class="icon-plus2 mr-1"></i> Add Row</button>
                                    <button type="button" class="btn btn-primary btn-sm" id="notice-add-column"><i class="icon-plus2 mr-1"></i> Add Column</button>
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="notice-remove-row"><i class="icon-minus2 mr-1"></i> Remove Row</button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="notice-remove-column"><i class="icon-minus2 mr-1"></i> Remove Column</button>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Notice</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="manage-notices">
                    <div class="notice-selection-toolbar" data-notice-selection="unpaid">
                        <span class="selection-counter text-muted" aria-live="polite">0 of {{ $notices->count() }} entries selected</span>
                        <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                        <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;" disabled>Delete Selected</button>
                        <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                    </div>
                    <form method="POST" action="{{ route('accountant.notices.delete_selected') }}" class="notice-delete-submit-form" style="display:none;">
                        @csrf
                        <input type="hidden" name="notice_tab" value="unpaid">
                    </form>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered notice-selection-table">
                            <thead><tr><th class="notice-select-column"><i class="icon-checkmark-circle"></i></th><th>Student</th><th>Class / Section</th><th>Parent Accounts</th><th>Sent</th><th class="text-center">Action</th></tr></thead>
                            <tbody>
                            @forelse($notices as $notice)
                                <tr class="selectable-row">
                                    <td class="notice-select-cell"><input type="checkbox" class="notice-select-checkbox" value="{{ $notice->id }}"></td>
                                    <td>{{ $notice->student_name }}</td>
                                    <td>{{ $notice->student_class }}{{ $notice->student_section ? ' / '.$notice->student_section : '' }}</td>
                                    <td>{{ $notice->recipients->pluck('parent_name')->implode(', ') }}</td>
                                    <td>{{ $notice->created_at->format('m/d/Y h:i A') }}</td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('accountant.notices.mark_paid', ['notice_id' => $notice->id]) }}" onsubmit="return confirm('Mark this notice as paid? This moves it to Paid Notices.');">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">Mark Paid</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">No unpaid notices.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="paid-notices">
                    <div class="notice-selection-toolbar" data-notice-selection="paid">
                        <span class="selection-counter text-muted" aria-live="polite">0 of {{ $paidNotices->count() }} entries selected</span>
                        <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                        <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;" disabled>Delete Selected</button>
                        <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                    </div>
                    <form method="POST" action="{{ route('accountant.notices.delete_selected') }}" class="notice-delete-submit-form" style="display:none;">
                        @csrf
                        <input type="hidden" name="notice_tab" value="paid">
                    </form>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered notice-selection-table">
                            <thead><tr><th class="notice-select-column"><i class="icon-checkmark-circle"></i></th><th>Student</th><th>Class / Section</th><th>Parent Accounts</th><th>Sent</th><th>Marked Paid</th></tr></thead>
                            <tbody>
                            @forelse($paidNotices as $notice)
                                <tr class="selectable-row">
                                    <td class="notice-select-cell"><input type="checkbox" class="notice-select-checkbox" value="{{ $notice->id }}"></td>
                                    <td>{{ $notice->student_name }}</td>
                                    <td>{{ $notice->student_class }}{{ $notice->student_section ? ' / '.$notice->student_section : '' }}</td>
                                    <td>{{ $notice->recipients->pluck('parent_name')->implode(', ') }}</td>
                                    <td>{{ $notice->created_at->format('m/d/Y h:i A') }}</td>
                                    <td>{{ optional($notice->paid_at)->format('m/d/Y h:i A') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">No paid notices.</td></tr>
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
            var classSelect = document.getElementById('notice-class');
            var studentSelect = document.getElementById('notice-student');
            var parentsField = document.getElementById('notice-parents');
            var layout = {
                columns: [
                    { key: 'notice_column_1', title: '' },
                    { key: 'notice_column_2', title: '' }
                ],
                rows: [{ id: 'notice_row_1', cells: { notice_column_1: '', notice_column_2: '' } }]
            };
            var activeCell = null;
            var history = [JSON.stringify(layout)];
            var historyIndex = 0;
            var gradeLayouts = @json($gradeLayouts);
            var studentsUrlTemplate = @json(route('accountant.notices.students', ['class_id' => '__CLASS_ID__']));
            var head = document.getElementById('notice-lines-head');
            var body = document.getElementById('notice-lines');
            var hiddenFields = document.getElementById('notice-layout-fields');
            var undoButton = document.getElementById('undo-notice-change');
            var redoButton = document.getElementById('redo-notice-change');

            function updateHistoryButtons() {
                undoButton.disabled = historyIndex === 0;
                redoButton.disabled = historyIndex >= history.length - 1;
            }

            function recordHistory() {
                var snapshot = JSON.stringify(layout);
                if (snapshot === history[historyIndex]) { return; }
                history = history.slice(0, historyIndex + 1);
                history.push(snapshot);
                if (history.length > 100) { history.shift(); }
                historyIndex = history.length - 1;
                updateHistoryButtons();
            }

            function undoChange() {
                if (historyIndex === 0) { return; }
                historyIndex -= 1;
                layout = JSON.parse(history[historyIndex]);
                activeCell = null;
                render();
                updateHistoryButtons();
            }

            function redoChange() {
                if (historyIndex >= history.length - 1) { return; }
                historyIndex += 1;
                layout = JSON.parse(history[historyIndex]);
                activeCell = null;
                render();
                updateHistoryButtons();
            }

            function render() {
                var headerRow = document.createElement('tr');
                head.innerHTML = '';
                layout.columns.forEach(function (column) {
                    var th = document.createElement('th');
                    var input = document.createElement('textarea');
                    input.className = 'notice-header-input';
                    input.rows = 1;
                    input.value = column.title || '';
                    input.maxLength = 100;
                    input.addEventListener('input', resizeEditor);
                    input.addEventListener('focus', function () {
                        activeCell = { rowId: null, columnKey: column.key };
                    });
                    input.addEventListener('input', function () {
                        column.title = input.value;
                        resizeEditor.call(input);
                        if (layout.columns[0].key === column.key) { fitFirstColumn(); }
                        recordHistory();
                    });
                    th.appendChild(input);
                    resizeEditor.call(input);
                    headerRow.appendChild(th);
                });
                head.appendChild(headerRow);

                body.innerHTML = '';
                layout.rows.forEach(function (row, rowIndex) {
                    var tr = document.createElement('tr');
                    layout.columns.forEach(function (column) {
                        var td = document.createElement('td');
                        var input = document.createElement('textarea');
                        input.className = 'notice-cell-input';
                        input.rows = 1;
                        input.value = row.cells && row.cells[column.key] != null ? row.cells[column.key] : '';
                        input.maxLength = 250;
                        input.addEventListener('input', resizeEditor);
                        input.addEventListener('focus', function () {
                            activeCell = { rowId: row.id, columnKey: column.key };
                        });
                        input.addEventListener('input', function () {
                            row.cells = row.cells || {};
                            row.cells[column.key] = input.value;
                            resizeEditor.call(input);
                            if (layout.columns[0].key === column.key) { fitFirstColumn(); }
                            recordHistory();
                        });
                        td.appendChild(input);
                        resizeEditor.call(input);
                        tr.appendChild(td);
                    });
                    body.appendChild(tr);
                });
                fitFirstColumn();
            }

            function fitFirstColumn() {
                if (!layout.columns.length) { return; }

                var firstColumn = layout.columns[0];
                var sampleInput = head.querySelector('textarea');
                var style = sampleInput ? window.getComputedStyle(sampleInput) : null;
                var context = document.createElement('canvas').getContext('2d');
                var maxTextWidth = 0;
                var values = [firstColumn.title || ''];

                layout.rows.forEach(function (row) {
                    values.push(row.cells && row.cells[firstColumn.key] != null ? row.cells[firstColumn.key] : '');
                });

                if (context && style) {
                    context.font = style.font;
                    values.forEach(function (value) {
                        String(value).split('\n').forEach(function (line) {
                            maxTextWidth = Math.max(maxTextWidth, context.measureText(line).width);
                        });
                    });
                } else {
                    maxTextWidth = Math.max.apply(null, values.map(function (value) { return String(value).length * 8; }));
                }

                var width = Math.max(180, Math.ceil(maxTextWidth + 48));
                document.querySelectorAll('#notice-lines-table tr > :first-child').forEach(function (cell) {
                    cell.style.width = width + 'px';
                    cell.style.minWidth = width + 'px';
                });
                document.querySelectorAll('#notice-lines-table textarea').forEach(function (input) {
                    resizeEditor.call(input);
                });
            }

            function resizeEditor() {
                this.style.height = '38px';
                this.style.height = Math.max(38, this.scrollHeight) + 'px';
            }

            function syncHiddenFields() {
                hiddenFields.innerHTML = '';
                layout.columns.forEach(function (column, index) {
                    addHiddenField('layout[columns][' + index + '][key]', column.key);
                    addHiddenField('layout[columns][' + index + '][title]', column.title || '');
                });
                layout.rows.forEach(function (row, rowIndex) {
                    addHiddenField('layout[rows][' + rowIndex + '][id]', row.id);
                    layout.columns.forEach(function (column) {
                        addHiddenField('layout[rows][' + rowIndex + '][cells][' + column.key + ']', row.cells && row.cells[column.key] || '');
                    });
                });
            }

            function addHiddenField(name, value) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                hiddenFields.appendChild(input);
            }

            function addRow() {
                var cells = {};
                layout.columns.forEach(function (column) { cells[column.key] = ''; });
                layout.rows.push({ id: 'notice_row_' + Date.now(), cells: cells });
                recordHistory();
                render();
            }

            function addColumn() {
                if (layout.columns.length >= 24) {
                    alert('A maximum of 24 columns is allowed.');
                    return;
                }
                var key = 'notice_column_' + Date.now();
                layout.columns.push({ key: key, title: '' });
                layout.rows.forEach(function (row) {
                    row.cells = row.cells || {};
                    row.cells[key] = '';
                });
                recordHistory();
                render();
            }

            function removeRow() {
                if (!activeCell || !activeCell.rowId) {
                    alert('Select a cell in the row you want to remove.');
                    return;
                }
                if (layout.rows.length <= 1) {
                    alert('At least one row is required.');
                    return;
                }
                var rowIndex = layout.rows.findIndex(function (row) { return row.id === activeCell.rowId; });
                if (rowIndex < 0) { activeCell = null; return; }
                layout.rows.splice(rowIndex, 1);
                activeCell = null;
                recordHistory();
                render();
            }

            function removeColumn() {
                if (!activeCell || !activeCell.columnKey) {
                    alert('Select a cell in the column you want to remove.');
                    return;
                }
                if (layout.columns.length <= 2) {
                    alert('At least two columns are required.');
                    return;
                }
                var key = activeCell.columnKey;
                layout.columns = layout.columns.filter(function (column) { return column.key !== key; });
                layout.rows.forEach(function (row) { if (row.cells) { delete row.cells[key]; } });
                activeCell = null;
                recordHistory();
                render();
            }

            classSelect.addEventListener('change', function () {
                studentSelect.disabled = true;
                studentSelect.innerHTML = '<option value="">Loading students...</option>';
                parentsField.textContent = 'Select a student to view linked parents.';
                if (!classSelect.value) {
                    studentSelect.innerHTML = '<option value="">Choose a class first</option>';
                    return;
                }

                fetch(studentsUrlTemplate.replace('__CLASS_ID__', encodeURIComponent(classSelect.value)), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (response) {
                    if (!response.ok) { throw new Error('Unable to load students.'); }
                    return response.json();
                })
                .then(function (students) {
                    studentSelect.innerHTML = '<option value="">Choose student...</option>';
                    students.sort(function (first, second) {
                        return String(first.name || '').localeCompare(String(second.name || ''), undefined, { sensitivity: 'base' });
                    });
                    students.forEach(function (student) {
                        var option = document.createElement('option');
                        option.value = student.id;
                        option.textContent = student.name;
                        option.dataset.parents = JSON.stringify(student.parents);
                        studentSelect.appendChild(option);
                    });
                    studentSelect.disabled = false;
                })
                .catch(function (error) {
                    studentSelect.innerHTML = '<option value="">' + error.message + '</option>';
                });
            });

            studentSelect.addEventListener('change', function () {
                var selected = studentSelect.options[studentSelect.selectedIndex];
                if (!selected || !selected.value) {
                    parentsField.textContent = 'Select a student to view linked parents.';
                    return;
                }
                var parents = JSON.parse(selected.dataset.parents || '[]');
                parentsField.textContent = parents.length ? parents.map(function (parent) { return parent.name; }).join(', ') : 'No linked parent accounts.';
            });

            document.getElementById('notice-grade-import').addEventListener('change', function (event) {
                var importedLayout = gradeLayouts[event.target.value];
                if (importedLayout && importedLayout.columns) {
                    layout = JSON.parse(JSON.stringify(importedLayout));
                    if (!layout.rows.length) {
                        var cells = {};
                        layout.columns.forEach(function (column) { cells[column.key] = ''; });
                        layout.rows.push({ id: 'notice_row_' + Date.now(), cells: cells });
                    }
                    activeCell = null;
                    recordHistory();
                    render();
                }
            });

            document.getElementById('notice-add-row').addEventListener('click', function () {
                if (layout.rows.length >= 200) {
                    alert('A maximum of 200 rows is allowed.');
                    return;
                }
                addRow();
            });
            document.getElementById('notice-add-column').addEventListener('click', addColumn);
            document.getElementById('notice-remove-row').addEventListener('click', removeRow);
            document.getElementById('notice-remove-column').addEventListener('click', removeColumn);
            undoButton.addEventListener('click', undoChange);
            redoButton.addEventListener('click', redoChange);

            document.getElementById('create-billing-notice-form').addEventListener('submit', function (event) {
                if (!window.confirm('Send this payment notice to the linked parent account(s)?')) {
                    event.preventDefault();
                    return;
                }
                syncHiddenFields();
            });

            document.querySelectorAll('.notice-selection-toolbar').forEach(function (toolbar) {
                var tab = toolbar.closest('.tab-pane');
                var table = tab.querySelector('.notice-selection-table');
                var submitForm = tab.querySelector('.notice-delete-submit-form');
                var counter = toolbar.querySelector('.selection-counter');
                var toggleButton = toolbar.querySelector('.toggle-select-mode');
                var deleteButton = toolbar.querySelector('.delete-selected');
                var selectAllButton = toolbar.querySelector('.select-all');
                var clearButton = toolbar.querySelector('.clear-selection');
                var checkboxes = Array.prototype.slice.call(table.querySelectorAll('.notice-select-checkbox'));

                function updateSelection() {
                    var selectedCount = checkboxes.filter(function (checkbox) { return checkbox.checked; }).length;
                    counter.textContent = selectedCount + ' of ' + checkboxes.length + ' entries selected';
                    deleteButton.disabled = selectedCount === 0;
                    checkboxes.forEach(function (checkbox) {
                        checkbox.closest('tr').classList.toggle('row-selected', checkbox.checked);
                    });
                }

                function clearSelection() {
                    checkboxes.forEach(function (checkbox) { checkbox.checked = false; });
                    updateSelection();
                }

                toggleButton.addEventListener('click', function () {
                    var selecting = table.classList.toggle('notice-select-mode');
                    deleteButton.style.display = selecting ? 'inline-block' : 'none';
                    selectAllButton.style.display = selecting ? 'inline-block' : 'none';
                    clearButton.style.display = selecting ? 'inline-block' : 'none';
                    if (counter) { counter.style.display = selecting ? 'inline-block' : 'none'; }
                    toggleButton.textContent = selecting ? 'Cancel' : 'Select';
                    if (!selecting) { clearSelection(); }
                });

                table.addEventListener('click', function (event) {
                    if (!table.classList.contains('notice-select-mode') || event.target.closest('a, button, form, input')) { return; }
                    var row = event.target.closest('tr.selectable-row');
                    if (!row) { return; }
                    var checkbox = row.querySelector('.notice-select-checkbox');
                    checkbox.checked = !checkbox.checked;
                    updateSelection();
                });

                checkboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', updateSelection);
                });
                selectAllButton.addEventListener('click', function () {
                    checkboxes.forEach(function (checkbox) { checkbox.checked = true; });
                    updateSelection();
                });
                clearButton.addEventListener('click', clearSelection);
                deleteButton.addEventListener('click', function () {
                    var selected = checkboxes.filter(function (checkbox) { return checkbox.checked; });
                    if (!selected.length || !window.confirm('Delete ' + selected.length + ' selected notices?')) { return; }
                    submitForm.querySelectorAll('input[name="notice_ids[]"]').forEach(function (input) { input.remove(); });
                    selected.forEach(function (checkbox) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'notice_ids[]';
                        input.value = checkbox.value;
                        submitForm.appendChild(input);
                    });
                    submitForm.submit();
                });

                updateSelection();
            });

            var activeTab = window.location.hash;
            if (activeTab) {
                var tab = document.querySelector('.nav-tabs a[href="' + activeTab + '"]');
                if (tab) { window.jQuery(tab).tab('show'); }
            }

            updateHistoryButtons();
            render();
        });
    </script>
@endsection