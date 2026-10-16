@extends('layouts.master')
@section('page_title', 'Billing List - '.$grade['label'])
@section('content')

    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">{{ $grade['label'] }}</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <div class="d-flex justify-content-end align-items-center mb-3">
                @if($editable)
                    <button id="save-billing-layout" type="button" class="btn btn-success btn-sm">Save Progress</button>
                @else
                    <a href="{{ route('accountant.billings.list', ['grade_key' => $grade['key'], 'edit' => 1]) }}" class="btn btn-primary btn-sm">Edit Billings</a>
                @endif
            </div>

            <style>
                #billing-layout-table th,
                #billing-layout-table td {
                    padding: 8px 12px;
                    vertical-align: middle;
                }

                #billing-layout-table .billing-index-cell {
                    padding: 8px 12px;
                }

                #billing-layout-table .billing-header-input,
                #billing-layout-table .billing-cell-input {
                    border: 0;
                    border-radius: 0;
                    box-shadow: none;
                    width: 100%;
                    height: 38px;
                    min-height: 38px;
                    padding: 8px 10px;
                    white-space: nowrap;
                }

                #billing-layout-table .billing-header-input:focus,
                #billing-layout-table .billing-cell-input:focus {
                    box-shadow: none;
                }
            </style>

            <div class="table-responsive">
                <table class="table table-bordered" id="billing-layout-table">
                    <thead id="billing-layout-head"></thead>
                    <tbody id="billing-layout-body"></tbody>
                </table>
            </div>

            @if($editable)
                <div id="billing-layout-toolbar" class="d-flex justify-content-between align-items-center flex-wrap mt-2" style="gap:8px;">
                    <div class="btn-group" role="group" aria-label="Billing list edit history">
                        <button type="button" class="btn btn-light btn-sm" id="undo-billing-change" disabled>Undo</button>
                        <button type="button" class="btn btn-light btn-sm" id="redo-billing-change" disabled>Redo</button>
                    </div>
                    <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary btn-sm" id="add-billing-row"><i class="icon-plus2 mr-1"></i> Add Row</button>
                            <button type="button" class="btn btn-primary btn-sm" id="add-billing-column"><i class="icon-plus2 mr-1"></i> Add Column</button>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-danger btn-sm" id="remove-billing-row"><i class="icon-minus2 mr-1"></i> Remove Row</button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="remove-billing-column"><i class="icon-minus2 mr-1"></i> Remove Column</button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var layout = {!! json_encode($layout) !!};

            var editable = @json($editable);
            var activeCell = null;
            var history = [JSON.stringify(layout)];
            var historyIndex = 0;
            var undoButton = document.getElementById('undo-billing-change');
            var redoButton = document.getElementById('redo-billing-change');

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

            function escapeAttr(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function escapeHtml(value) {
                return escapeAttr(value).replace(/'/g, '&#039;');
            }

            function render() {
                var head = document.getElementById('billing-layout-head');
                var body = document.getElementById('billing-layout-body');

                var headRow = '<tr>';
                layout.columns.forEach(function (column) {
                    headRow += editable
                        ? '<th><input type="text" class="form-control form-control-sm billing-header-input" data-column-key="' + escapeAttr(column.key) + '" value="' + escapeAttr(column.title || '') + '" placeholder="Column title"></th>'
                        : '<th>' + escapeHtml(column.title || '') + '</th>';
                });
                headRow += '</tr>';
                head.innerHTML = headRow;

                var bodyHtml = '';
                layout.rows.forEach(function (row, rowIndex) {
                    bodyHtml += '<tr data-row-index="' + rowIndex + '">';
                    layout.columns.forEach(function (column) {
                        var value = (row.cells && row.cells[column.key]) ? row.cells[column.key] : '';
                        bodyHtml += editable
                            ? '<td><input type="text" class="form-control form-control-sm billing-cell-input" data-row-index="' + rowIndex + '" data-column-key="' + escapeAttr(column.key) + '" value="' + escapeAttr(value) + '" placeholder="Enter text..."></td>'
                            : '<td>' + escapeHtml(value) + '</td>';
                    });

                    bodyHtml += '</tr>';
                });

                body.innerHTML = bodyHtml;
                bindFieldListeners();
            }

            function bindFieldListeners() {
                document.querySelectorAll('.billing-header-input').forEach(function (input) {
                    input.addEventListener('focus', function () {
                        activeCell = { rowId: null, columnKey: input.getAttribute('data-column-key') };
                    });
                    input.addEventListener('input', function () {
                        var key = input.getAttribute('data-column-key');
                        var column = layout.columns.find(function (c) { return c.key === key; });
                        if (column) {
                            column.title = input.value;
                            recordHistory();
                        }
                    });
                });

                document.querySelectorAll('.billing-cell-input').forEach(function (input) {
                    input.addEventListener('focus', function () {
                        var rowIndex = parseInt(input.getAttribute('data-row-index'), 10);
                        var row = layout.rows[rowIndex];
                        activeCell = row ? { rowId: row.id, columnKey: input.getAttribute('data-column-key') } : null;
                    });
                    input.addEventListener('input', function () {
                        var rowIndex = parseInt(input.getAttribute('data-row-index'), 10);
                        var key = input.getAttribute('data-column-key');

                        if (Number.isNaN(rowIndex) || !layout.rows[rowIndex] || !key) {
                            return;
                        }

                        if (!layout.rows[rowIndex].cells) {
                            layout.rows[rowIndex].cells = {};
                        }

                        layout.rows[rowIndex].cells[key] = input.value;
                        recordHistory();
                    });
                });
            }

            function addRow() {
                var nextIndex = layout.rows.length + 1;
                var cells = {};
                layout.columns.forEach(function (column) {
                    cells[column.key] = '';
                });

                layout.rows.push({
                    id: 'row_' + nextIndex + '_' + Date.now(),
                    cells: cells
                });

                recordHistory();
                render();
            }

            function addColumn() {
                var nextIndex = layout.columns.length + 1;
                var key = 'col_' + nextIndex + '_' + Date.now();
                layout.columns.push({
                    key: key,
                    title: 'Column ' + nextIndex
                });

                layout.rows.forEach(function (row) {
                    if (!row.cells) {
                        row.cells = {};
                    }
                    row.cells[key] = '';
                });

                recordHistory();
                render();
            }

            function showLayoutMessage(message) {
                if (typeof toast === 'function') {
                    toast(message, 'error');
                } else {
                    alert(message);
                }
            }

            function removeRow() {
                if (!activeCell || !activeCell.rowId) {
                    showLayoutMessage('Select a billing cell in the row you want to remove.');
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
                    showLayoutMessage('Select a billing cell in the column you want to remove.');
                    return;
                }
                if (layout.columns.length <= 2) {
                    showLayoutMessage('At least two billing columns are required.');
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

            function saveLayout() {
                fetch('{{ route('accountant.billings.save_list', ['grade_key' => $grade['key']]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ layout: layout })
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.ok) {
                        if (typeof toast === 'function') {
                            toast(data.msg, 'success');
                        } else {
                            alert(data.msg || 'Billing list progress saved.');
                        }
                    } else {
                        alert(data.msg || 'Unable to save billing list progress.');
                    }
                })
                .catch(function () {
                    alert('Unable to save billing list progress right now.');
                });
            }

            if (editable) {
                document.getElementById('add-billing-row').addEventListener('click', addRow);
                document.getElementById('remove-billing-row').addEventListener('click', removeRow);
                document.getElementById('add-billing-column').addEventListener('click', addColumn);
                document.getElementById('remove-billing-column').addEventListener('click', removeColumn);
                document.getElementById('save-billing-layout').addEventListener('click', saveLayout);
                document.getElementById('undo-billing-change').addEventListener('click', undoChange);
                document.getElementById('redo-billing-change').addEventListener('click', redoChange);

            }

            updateHistoryButtons();
            render();
        });
    </script>

@endsection
