@extends('layouts.master')
@section('page_title', 'Student Grades - '.$student->name)
@section('content')
    <style>
        #student-grade-table th,
        #student-grade-table td {
            min-width: 160px;
            padding: 8px 12px;
            vertical-align: middle;
        }

        #student-grade-table .grade-heading-input,
        #student-grade-table .grade-cell-input {
            width: 100%;
            min-width: 0;
            height: 38px;
            padding: 8px 10px;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }
    </style>

    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Grades: {{ $student->name }}</h6>
            {!! Qs::getPanelOptions() !!}
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:12px;">
                <a href="{{ url()->previous() }}" class="btn btn-light">Back</a>
                <div class="d-flex flex-column align-items-end" style="gap:8px;">
                    @if($can_manage_visibility && !$is_edit_page)
                        <a href="{{ route('students.grades.edit', ['student_id' => Qs::hash($student->id)]) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                    @endif
                    <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                        <span class="text-muted">Visibility: <strong id="current-visibility">{{ $grade_sheet->visibility }}</strong></span>
                        @if($can_manage_visibility)
                            <select id="grade-visibility" class="form-control" style="width:auto; min-width:170px;">
                                <option value="Teaching Staff" {{ $grade_sheet->visibility === 'Teaching Staff' ? 'selected' : '' }}>Teaching Staff</option>
                                <option value="All Users" {{ $grade_sheet->visibility === 'All Users' ? 'selected' : '' }}>All Users</option>
                            </select>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="save-grade-visibility">Save Visibility</button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="student-grade-table">
                    <thead id="student-grade-head"></thead>
                    <tbody id="student-grade-body"></tbody>
                </table>
            </div>

            @if($can_edit_grades)
                <div id="student-grade-toolbar" class="d-flex justify-content-between align-items-center flex-wrap mt-2" style="gap:8px;">
                    <div class="btn-group" role="group" aria-label="Grade edit history">
                        <button type="button" class="btn btn-light btn-sm" id="undo-grade-change" disabled>Undo</button>
                        <button type="button" class="btn btn-light btn-sm" id="redo-grade-change" disabled>Redo</button>
                    </div>
                    <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary btn-sm" id="add-grade-row"><i class="icon-plus2 mr-1"></i> Add Row</button>
                            <button type="button" class="btn btn-primary btn-sm" id="add-grade-column"><i class="icon-plus2 mr-1"></i> Add Column</button>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-danger btn-sm" id="remove-grade-row"><i class="icon-minus2 mr-1"></i> Remove Row</button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="remove-grade-column"><i class="icon-minus2 mr-1"></i> Remove Column</button>
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="save-student-grades">Save Progress</button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var layout = @json($layout);
            var editable = @json($can_edit_grades);
            var head = document.getElementById('student-grade-head');
            var body = document.getElementById('student-grade-body');
            var activeCell = null;
            var history = [JSON.stringify(layout)];
            var historyIndex = 0;
            var undoButton = document.getElementById('undo-grade-change');
            var redoButton = document.getElementById('redo-grade-change');

            function updateHistoryButtons() {
                if (!undoButton || !redoButton) {
                    return;
                }
                undoButton.disabled = historyIndex === 0;
                redoButton.disabled = historyIndex >= history.length - 1;
            }

            function recordHistory() {
                var snapshot = JSON.stringify(layout);
                if (snapshot === history[historyIndex]) {
                    return;
                }

                history = history.slice(0, historyIndex + 1);
                history.push(snapshot);
                if (history.length > 100) {
                    history.shift();
                }
                historyIndex = history.length - 1;
                updateHistoryButtons();
            }

            function undoChange() {
                if (historyIndex === 0) {
                    return;
                }
                historyIndex -= 1;
                layout = JSON.parse(history[historyIndex]);
                activeCell = null;
                render();
                updateHistoryButtons();
            }

            function redoChange() {
                if (historyIndex >= history.length - 1) {
                    return;
                }
                historyIndex += 1;
                layout = JSON.parse(history[historyIndex]);
                activeCell = null;
                render();
                updateHistoryButtons();
            }

            function escapeHtml(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/'/g, '&#039;');
            }

            function render() {
                var headerRow = '<tr>';
                layout.columns.forEach(function (column) {
                    headerRow += editable
                        ? '<th><input type="text" class="form-control form-control-sm grade-heading-input" data-column-key="' + escapeHtml(column.key) + '" value="' + escapeHtml(column.title) + '" placeholder="Column title"></th>'
                        : '<th>' + escapeHtml(column.title) + '</th>';
                });
                headerRow += '</tr>';
                head.innerHTML = headerRow;

                var rowsHtml = '';
                layout.rows.forEach(function (row, rowIndex) {
                    rowsHtml += '<tr>';
                    layout.columns.forEach(function (column) {
                        var value = row.cells && row.cells[column.key] != null ? row.cells[column.key] : '';
                        rowsHtml += editable
                            ? '<td><input type="text" class="form-control form-control-sm grade-cell-input" data-row-index="' + rowIndex + '" data-column-key="' + escapeHtml(column.key) + '" value="' + escapeHtml(value) + '" placeholder="Enter grade"></td>'
                            : '<td>' + escapeHtml(value) + '</td>';
                    });
                    rowsHtml += '</tr>';
                });

                if (!layout.rows.length) {
                    rowsHtml = '<tr><td colspan="' + layout.columns.length + '" class="text-center text-muted">No grades have been entered.</td></tr>';
                }
                body.innerHTML = rowsHtml;

                if (editable) {
                    bindInputs();
                }
            }

            function bindInputs() {
                document.querySelectorAll('.grade-heading-input').forEach(function (input) {
                    input.addEventListener('focus', function () {
                        activeCell = { rowId: null, columnKey: input.getAttribute('data-column-key') };
                    });
                    input.addEventListener('input', function () {
                        var column = layout.columns.find(function (item) {
                            return item.key === input.getAttribute('data-column-key');
                        });
                        if (column) {
                            column.title = input.value;
                            recordHistory();
                        }
                    });
                });

                document.querySelectorAll('.grade-cell-input').forEach(function (input) {
                    input.addEventListener('focus', function () {
                        var row = layout.rows[parseInt(input.getAttribute('data-row-index'), 10)];
                        activeCell = row ? { rowId: row.id, columnKey: input.getAttribute('data-column-key') } : null;
                    });
                    input.addEventListener('input', function () {
                        var row = layout.rows[parseInt(input.getAttribute('data-row-index'), 10)];
                        var key = input.getAttribute('data-column-key');
                        if (row && key) {
                            row.cells[key] = input.value;
                            recordHistory();
                        }
                    });
                });
            }

            function addRow() {
                var cells = {};
                layout.columns.forEach(function (column) { cells[column.key] = ''; });
                layout.rows.push({ id: 'row_' + Date.now(), cells: cells });
                recordHistory();
                render();
            }

            function addColumn() {
                var nextIndex = layout.columns.length + 1;
                var key = 'grade_' + Date.now() + '_' + nextIndex;
                layout.columns.push({ key: key, title: nextIndex === 3 ? 'Grade' : 'Column ' + nextIndex });
                layout.rows.forEach(function (row) {
                    row.cells = row.cells || {};
                    row.cells[key] = '';
                });
                recordHistory();
                render();
            }

            function removeRow() {
                if (!activeCell || !activeCell.rowId) {
                    showToast('Select a grade cell in the row you want to remove.', 'error');
                    return;
                }

                var rowIndex = layout.rows.findIndex(function (row) { return row.id === activeCell.rowId; });
                if (rowIndex < 0) {
                    activeCell = null;
                    return;
                }

                layout.rows.splice(rowIndex, 1);
                activeCell = null;
                recordHistory();
                render();
            }

            function removeColumn() {
                if (!activeCell || !activeCell.columnKey) {
                    showToast('Select a grade cell in the column you want to remove.', 'error');
                    return;
                }
                if (layout.columns.length <= 2) {
                    showToast('At least two grade columns are required.', 'error');
                    return;
                }

                var columnKey = activeCell.columnKey;
                layout.columns = layout.columns.filter(function (column) { return column.key !== columnKey; });
                layout.rows.forEach(function (row) {
                    if (row.cells) {
                        delete row.cells[columnKey];
                    }
                });
                activeCell = null;
                recordHistory();
                render();
            }

            function showToast(message, type) {
                if (typeof toast === 'function') {
                    toast(message, type || 'success');
                } else if (typeof flash === 'function') {
                    flash({ msg: message, type: type || 'success' });
                }
            }

            var saveGradesButton = document.getElementById('save-student-grades');
            if (saveGradesButton) {
                saveGradesButton.addEventListener('click', function () {
                    fetch('{{ route('students.grades.save_layout', ['student_id' => Qs::hash($student->id)]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ layout: layout })
                    })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            if (!response.ok) {
                                throw new Error(data.message || data.errors?.layout?.[0] || 'Unable to save student grades.');
                            }
                            return data;
                        });
                    })
                    .then(function (data) { showToast(data.msg); })
                    .catch(function (error) { showToast(error.message, 'error'); });
                });

                document.getElementById('add-grade-row').addEventListener('click', addRow);
                document.getElementById('remove-grade-row').addEventListener('click', removeRow);
                document.getElementById('add-grade-column').addEventListener('click', addColumn);
                document.getElementById('remove-grade-column').addEventListener('click', removeColumn);
                document.getElementById('undo-grade-change').addEventListener('click', undoChange);
                document.getElementById('redo-grade-change').addEventListener('click', redoChange);

            }

            var saveVisibilityButton = document.getElementById('save-grade-visibility');
            if (saveVisibilityButton) {
                saveVisibilityButton.addEventListener('click', function () {
                    fetch('{{ route('students.grades.save_visibility', ['student_id' => Qs::hash($student->id)]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ visibility: document.getElementById('grade-visibility').value })
                    })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            if (!response.ok) {
                                throw new Error(data.message || 'Unable to update grade visibility.');
                            }
                            return data;
                        });
                    })
                    .then(function (data) {
                        document.getElementById('current-visibility').textContent = data.visibility;
                        showToast(data.msg);
                    })
                    .catch(function (error) { showToast(error.message, 'error'); });
                });
            }

            updateHistoryButtons();
            render();
        });
    </script>
@endsection
