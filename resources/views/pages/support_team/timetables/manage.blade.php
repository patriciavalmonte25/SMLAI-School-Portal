@extends('layouts.master')
@section('page_title', 'Manage Timetable Record')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-bold">{{ $ttr->name.' ('.($my_class->name ?? optional($ttr->owner)->name ?? 'N/A').')'.' '.$ttr->year }}</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#view-ttr" class="nav-link active" data-toggle="tab">View Timetable</a></li>
                <li class="nav-item"><a target="_blank" href="{{ route('ttr.show', $ttr->id) }}" class="nav-link" >Legacy View</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="view-ttr">
                    <style>
                        #timetable-layout-table {
                            min-width: 1090px;
                            width: 100%;
                            table-layout: fixed;
                        }

                        #timetable-layout-table td {
                            padding: 6px 8px;
                            vertical-align: top;
                        }

                        .timetable-time-cell,
                        .timetable-day-cell,
                        .timetable-blank-cell {
                            padding: 6px;
                        }

                        .timetable-time-cell {
                            padding: 6px;
                        }

                        .timetable-time-block + .timetable-time-block {
                            margin-top: 3px;
                        }

                        .timetable-time-row {
                            display: flex;
                            gap: 4px;
                            align-items: stretch;
                        }

                        .timetable-time-row select {
                            min-width: 0;
                        }

                        .timetable-time-picker {
                            display: flex;
                            align-items: stretch;
                            gap: 4px;
                            width: 100%;
                        }

                        .timetable-time-picker-separator {
                            display: flex;
                            align-items: center;
                            font-weight: 600;
                            color: #555;
                        }

                        .timetable-time-input {
                            flex: 1 1 0;
                            min-width: 0;
                            text-align: center;
                            border-radius: 4px;
                        }

                        .timetable-day-cell,
                        .timetable-blank-cell {
                            padding: 10px;
                        }

                        .timetable-day-cell {
                            display: flex;
                            flex-direction: column;
                            height: 100%;
                        }

                        .timetable-field,
                        .timetable-blank-field {
                            width: 100%;
                            border: 0;
                            border-radius: 0;
                            box-shadow: none;
                            white-space: normal;
                            overflow-wrap: break-word;
                            word-break: normal;
                        }

                        .timetable-field-subject {
                            border-bottom: 1px solid #ddd;
                        }

                        .timetable-day-cell .timetable-field,
                        .timetable-blank-wrapper .timetable-blank-field {
                            flex: 1 1 50%;
                            min-height: 38px;
                            padding: 5px 8px;
                        }

                        .timetable-blank-cell .timetable-field-subject {
                            border-right: 1px solid #ddd;
                            border-bottom: 0;
                        }

                        .timetable-field:focus,
                        .timetable-blank-field:focus {
                            box-shadow: none;
                        }

                        .timetable-blank-wrapper {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            min-height: 76px;
                        }

                        .timetable-blank-display {
                            min-height: 76px;
                            background: #fafafa;
                            width: 100%;
                            border: 0;
                            border-radius: 0;
                            box-shadow: none;
                            resize: vertical;
                            padding: 6px 8px;
                            white-space: normal;
                            overflow-wrap: break-word;
                            word-break: normal;
                        }
                    </style>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 font-weight-semibold">Edit Timetable</h6>
                        <button id="save-timetable-layout" type="button" class="btn btn-success btn-sm">Save Progress</button>
                    </div>

                    <div class="form-group timetable-builder-controls">
                        <label for="timetable-title" class="font-weight-semibold">Timetable title</label>
                        <input id="timetable-title" class="form-control mb-3" type="text" maxlength="100" value="{{ $ttr->name }}" required>
                        <label for="existing-timetable-template" class="font-weight-semibold">Fill from an existing Timetable</label>
                        <select id="existing-timetable-template" class="form-control">
                            <option value="">Choose an existing Timetable...</option>
                            @foreach($existing_templates as $template)
                                <option value="{{ $template['id'] }}">
                                    {{ $template['name'] }}{{ $template['owner'] ? ' - '.$template['owner'] : '' }}{{ $template['class'] ? ' - '.$template['class'] : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="timetable-layout-table">
                            <thead>
                            <tr>
                                <th style="width:140px;">Time</th>
                                <th style="width:190px;">Monday</th>
                                <th style="width:190px;">Tuesday</th>
                                <th style="width:190px;">Wednesday</th>
                                <th style="width:190px;">Thursday</th>
                                <th style="width:190px;">Friday</th>
                            </tr>
                            </thead>
                            <tbody id="timetable-layout-body"></tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="btn-group">
                            <button id="undo-timetable-change" type="button" class="btn btn-light btn-sm" disabled>Undo</button>
                            <button id="redo-timetable-change" type="button" class="btn btn-light btn-sm" disabled>Redo</button>
                        </div>
                        <div>
                            <button id="remove-timetable-row" type="button" class="btn btn-outline-danger btn-sm mr-2" disabled>Remove Row</button>
                            <div class="btn-group dropup">
                                <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="icon-plus2 mr-1"></i> Add Row
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="#" id="add-subject-row">Subject Row</a>
                                    <a class="dropdown-item" href="#" id="add-blank-row">Blank Row</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var days = {!! json_encode($layout_days) !!};
            var teacherOptions = {!! json_encode($layout_teacher_options) !!};
            var rows = {!! json_encode($layout_rows) !!};
            var existingTemplates = {!! json_encode($existing_templates) !!};
            var activeRowId = null;
            var history = [];
            var historyIndex = -1;
            var historyTimer = null;
            var undoButton = document.getElementById('undo-timetable-change');
            var redoButton = document.getElementById('redo-timetable-change');
            var removeRowButton = document.getElementById('remove-timetable-row');

            function updateHistoryButtons() {
                undoButton.disabled = historyIndex <= 0;
                redoButton.disabled = historyIndex >= history.length - 1;
            }

            function recordHistory() {
                if (historyTimer) {
                    clearTimeout(historyTimer);
                    historyTimer = null;
                }
                syncRowsFromDom();
                var snapshot = JSON.stringify({
                    rows: rows,
                    title: document.getElementById('timetable-title').value
                });
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

            function queueHistory() {
                if (historyTimer) {
                    clearTimeout(historyTimer);
                }
                historyTimer = setTimeout(recordHistory, 400);
            }

            function undoChange() {
                if (historyIndex <= 0) {
                    return;
                }
                historyIndex--;
                var snapshot = JSON.parse(history[historyIndex]);
                rows = snapshot.rows;
                document.getElementById('timetable-title').value = snapshot.title;
                activeRowId = null;
                renderRows();
                updateHistoryButtons();
            }

            function redoChange() {
                if (historyIndex >= history.length - 1) {
                    return;
                }
                historyIndex++;
                var snapshot = JSON.parse(history[historyIndex]);
                rows = snapshot.rows;
                document.getElementById('timetable-title').value = snapshot.title;
                activeRowId = null;
                renderRows();
                updateHistoryButtons();
            }

            function updateRemoveRowButton() {
                removeRowButton.disabled = !activeRowId || !rows.some(function (row) {
                    return String(row.id) === String(activeRowId);
                });
            }

            function removeCurrentRow() {
                if (!activeRowId) {
                    return;
                }
                syncRowsFromDom();
                var rowIndex = rows.findIndex(function (row) {
                    return String(row.id) === String(activeRowId);
                });
                if (rowIndex < 0) {
                    return;
                }
                rows.splice(rowIndex, 1);
                activeRowId = rows.length ? rows[Math.min(rowIndex, rows.length - 1)].id : null;
                renderRows();
                recordHistory();
            }

            function makeSelectOptions(selectedId) {
                var html = '<option value=""></option>';
                teacherOptions.forEach(function (opt) {
                    var selected = String(selectedId || '') === String(opt.id) ? ' selected' : '';
                    html += '<option value="' + opt.id + '"' + selected + '>' + opt.label + '</option>';
                });
                return html;
            }

            function createTimeCell(row) {
                return '' +
                    '<div class="timetable-time-cell">' +
                        '<div class="timetable-time-block">' +
                            '<small class="text-muted d-block mb-1">Start</small>' +
                            '<div class="timetable-time-row">' +
                                '<div class="timetable-time-picker">' +
                                    '<input type="text" maxlength="2" inputmode="numeric" class="form-control form-control-sm timetable-time-input" placeholder="00" data-key="time_start_hour" value="' + escapeAttr(row.time_start_hour || '00') + '">' +
                                    '<div class="timetable-time-picker-separator">:</div>' +
                                    '<input type="text" maxlength="2" inputmode="numeric" class="form-control form-control-sm timetable-time-input" placeholder="00" data-key="time_start_minute" value="' + escapeAttr(row.time_start_minute || '00') + '">' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="timetable-time-block">' +
                            '<small class="text-muted d-block mb-1">End</small>' +
                            '<div class="timetable-time-row">' +
                                '<div class="timetable-time-picker">' +
                                    '<input type="text" maxlength="2" inputmode="numeric" class="form-control form-control-sm timetable-time-input" placeholder="00" data-key="time_end_hour" value="' + escapeAttr(row.time_end_hour || '00') + '">' +
                                    '<div class="timetable-time-picker-separator">:</div>' +
                                    '<input type="text" maxlength="2" inputmode="numeric" class="form-control form-control-sm timetable-time-input" placeholder="00" data-key="time_end_minute" value="' + escapeAttr(row.time_end_minute || '00') + '">' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
            }

            function escapeAttr(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function sanitizeTimeValue(key, value) {
                var sanitized = String(value || '').replace(/[^0-9]/g, '');

                if (key.indexOf('_hour') !== -1) {
                    if (!sanitized) {
                        return '';
                    }

                    var hour = parseInt(sanitized, 10);
                    if (Number.isNaN(hour)) {
                        return '';
                    }

                    if (hour < 0) {
                        hour = 0;
                    }

                    if (hour > 23) {
                        hour = 23;
                    }

                    return String(hour).padStart(2, '0');
                }

                if (key.indexOf('_minute') !== -1) {
                    if (!sanitized) {
                        return '';
                    }

                    var minute = parseInt(sanitized, 10);
                    if (Number.isNaN(minute)) {
                        return '';
                    }

                    if (minute < 0) {
                        minute = 0;
                    }

                    if (minute > 59) {
                        minute = 59;
                    }

                    return String(minute).padStart(2, '0');
                }

                return value;
            }

            function createDayCell(row, day) {
                var subjectKey = day + '_subject';
                var teacherKey = day + '_teacher_id';
                return '' +
                    '<div class="timetable-day-cell">' +
                        '<input type="text" class="form-control form-control-sm timetable-field timetable-field-subject" placeholder="Subject" data-key="' + subjectKey + '" value="' + (row[subjectKey] || '').replace(/"/g, '&quot;') + '">' +
                        '<select class="form-control form-control-sm timetable-field" data-key="' + teacherKey + '">' + makeSelectOptions(row[teacherKey]).replace('<option value=""></option>', '<option value="">Teacher</option>') + '</select>' +
                    '</div>';
            }

            function createBlankCell(row) {
                return '' +
                    '<div class="timetable-blank-cell">' +
                        '<textarea class="form-control form-control-sm timetable-blank-display" data-key="blank_subject" placeholder="Blank row text...">' + escapeAttr(row.blank_subject || '') + '</textarea>' +
                    '</div>';
            }

            function renderRows() {
                var body = document.getElementById('timetable-layout-body');
                body.innerHTML = '';

                rows.forEach(function (row, idx) {
                    row.id = row.id || ('row_' + Date.now() + '_' + idx);
                    var tr = document.createElement('tr');
                    tr.setAttribute('data-row-index', String(idx));
                    tr.setAttribute('data-row-id', String(row.id));

                    var timeTd = document.createElement('td');
                    timeTd.innerHTML = createTimeCell(row);
                    tr.appendChild(timeTd);

                    if (row.type === 'blank') {
                        var blankTd = document.createElement('td');
                        blankTd.colSpan = 5;
                        blankTd.innerHTML = createBlankCell(row);
                        tr.appendChild(blankTd);
                    } else {
                        days.forEach(function (day) {
                            var td = document.createElement('td');
                            td.innerHTML = createDayCell(row, day);
                            tr.appendChild(td);
                        });
                    }

                    body.appendChild(tr);
                });

                body.querySelectorAll('input, select, textarea').forEach(function (el) {
                    el.addEventListener('focusin', function () {
                        activeRowId = el.closest('tr').getAttribute('data-row-id');
                        updateRemoveRowButton();
                    });
                    el.addEventListener('change', function () {
                        var rowEl = el.closest('tr');
                        if (!rowEl) return;
                        var rowIndex = parseInt(rowEl.getAttribute('data-row-index'), 10);
                        var key = el.getAttribute('data-key');
                        if (!Number.isNaN(rowIndex) && key) {
                            activeRowId = rowEl.getAttribute('data-row-id');
                            if (key.indexOf('time_') === 0) {
                                el.value = sanitizeTimeValue(key, el.value);
                            }

                            rows[rowIndex][key] = el.value;
                            recordHistory();
                        }
                    });
                    el.addEventListener('input', function () {
                        var rowEl = el.closest('tr');
                        var rowIndex = rowEl ? parseInt(rowEl.getAttribute('data-row-index'), 10) : NaN;
                        var key = el.getAttribute('data-key');
                        if (!Number.isNaN(rowIndex) && key) {
                            activeRowId = rowEl.getAttribute('data-row-id');
                            rows[rowIndex][key] = el.value;
                            updateRemoveRowButton();
                            queueHistory();
                        }
                        if (key && key.indexOf('time_') === 0) {
                            var cleaned = String(el.value || '').replace(/[^0-9]/g, '');
                            el.value = cleaned.slice(0, 2);
                            if (!Number.isNaN(rowIndex) && key) {
                                rows[rowIndex][key] = el.value;
                            }
                        }
                    });
                });
                updateRemoveRowButton();
            }

            function addRow(type) {
                syncRowsFromDom();

                var baseRow = {
                    id: 'row_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                    type: type,
                    time_start_hour: '',
                    time_start_minute: '',
                    time_end_hour: '',
                    time_end_minute: '',
                    monday_subject: '',
                    monday_teacher_id: null,
                    tuesday_subject: '',
                    tuesday_teacher_id: null,
                    wednesday_subject: '',
                    wednesday_teacher_id: null,
                    thursday_subject: '',
                    thursday_teacher_id: null,
                    friday_subject: '',
                    friday_teacher_id: null,
                    blank_subject: '',
                    blank_teacher_id: null
                };
                rows.push(baseRow);
                activeRowId = baseRow.id;
                renderRows();
                recordHistory();
            }

            function syncRowsFromDom() {
                var body = document.getElementById('timetable-layout-body');
                if (!body) {
                    return;
                }

                body.querySelectorAll('tr[data-row-index]').forEach(function (rowEl) {
                    var rowIndex = parseInt(rowEl.getAttribute('data-row-index'), 10);
                    if (Number.isNaN(rowIndex) || !rows[rowIndex]) {
                        return;
                    }

                    rowEl.querySelectorAll('[data-key]').forEach(function (field) {
                        var key = field.getAttribute('data-key');
                        if (!key) {
                            return;
                        }

                        var value = field.value;
                        if (key.indexOf('time_') === 0) {
                            value = sanitizeTimeValue(key, value);
                            field.value = value;
                        }

                        rows[rowIndex][key] = value;
                    });
                });
            }

            function saveLayout() {
                recordHistory();

                fetch('{{ route('ttr.layout.save', $ttr->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        title: document.getElementById('timetable-title').value,
                        rows: rows
                    })
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.ok) {
                        if (typeof toast === 'function') {
                            toast(data.msg, 'success');
                        } else {
                            alert(data.msg || 'Timetable progress saved.');
                        }
                    } else {
                        alert(data.msg || 'Unable to save Timetable progress.');
                    }
                })
                .catch(function () {
                    alert('Unable to save Timetable progress right now.');
                });
            }

            document.getElementById('add-subject-row').addEventListener('click', function (e) {
                e.preventDefault();
                addRow('subject');
            });

            document.getElementById('add-blank-row').addEventListener('click', function (e) {
                e.preventDefault();
                addRow('blank');
            });

            document.getElementById('save-timetable-layout').addEventListener('click', function () {
                saveLayout();
            });

            undoButton.addEventListener('click', undoChange);
            redoButton.addEventListener('click', redoChange);
            removeRowButton.addEventListener('click', removeCurrentRow);
            document.getElementById('timetable-title').addEventListener('input', queueHistory);
            document.getElementById('timetable-title').addEventListener('change', recordHistory);

            document.getElementById('existing-timetable-template').addEventListener('change', function () {
                var selectedTemplate = existingTemplates.find(function (template) {
                    return String(template.id) === String(this.value);
                }, this);

                if (!selectedTemplate || !Array.isArray(selectedTemplate.rows) || !selectedTemplate.rows.length) {
                    this.value = '';
                    alert('This Timetable has no saved builder layout to copy.');
                    return;
                }

                syncRowsFromDom();
                rows = JSON.parse(JSON.stringify(selectedTemplate.rows));
                activeRowId = null;
                renderRows();
                recordHistory();
                this.value = '';
            });

            renderRows();
            recordHistory();
        });
    </script>

    {{--Timetable Manage Ends--}}

@endsection
