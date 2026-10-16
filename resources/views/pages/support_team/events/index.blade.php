@extends('layouts.master')
@section('page_title', 'Manage School Calendar Events')
@section('content')

    <style>
        #manage-events-table .select-column,
        #manage-events-table .select-cell {
            display: none;
            width: 48px;
            text-align: center;
        }

        #manage-events-table.select-mode .select-column,
        #manage-events-table.select-mode .select-cell {
            display: table-cell !important;
        }

        #manage-events-table .action-column {
            width: 52px !important;
            min-width: 52px;
            max-width: 52px;
            white-space: nowrap;
        }

        #manage-events-table .date-column {
            width: 105px !important;
            min-width: 105px;
            max-width: 105px;
            white-space: nowrap;
        }

        #manage-events-table .created-on-column {
            width: 130px !important;
            min-width: 130px;
            max-width: 130px;
            padding-left: 24px !important;
            white-space: nowrap;
        }

        #manage-events-table.select-mode tbody tr.selectable-row,
        #manage-events-table tbody tr.row-selected {
            cursor: pointer;
        }

        #manage-events-table tbody tr.row-selected,
        #manage-events-table tbody tr.row-selected > td {
            background-color: #d9dee3 !important;
        }

        .events-table-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .bulk-toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .selection-counter {
            display: none;
            white-space: nowrap;
        }
    </style>

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#events" class="nav-link {{ !isset($event) ? 'active' : '' }}" data-toggle="tab">School Calendar</a></li>
                <li class="nav-item"><a href="#manage-events" class="nav-link {{ isset($event) ? 'active' : '' }}" data-toggle="tab">Manage Events</a></li>
            </ul>

            <div class="tab-content">
                {{-- Create/Edit Events Tab --}}
                <div class="tab-pane fade {{ !isset($event) ? 'show active' : '' }}" id="events">
                    <form method="post" class="ajax-store" action="{{ isset($event) ? route('events.update', Qs::hash($event->id)) : route('events.store') }}" data-fouc>
                        @csrf
                        @if(isset($event))
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Event Date: <span class="text-danger">*</span></label>
                                    <input name="date" value="{{ isset($event) ? $event->date->format('m/d/Y') : old('date') }}" type="text" class="form-control date-pick" placeholder="MM/DD/YYYY" data-date-format="mm/dd/yyyy" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Event: <span class="text-danger">*</span></label>
                            <textarea required name="event" placeholder="Enter event" class="form-control" rows="6">{{ isset($event) ? $event->event : old('event') }}</textarea>
                        </div>

                        <div class="form-group">
                            <label class="d-block">Visibility</label>
                            <input type="hidden" name="visibility_present" value="1">
                            <div class="visibility-options">
                                @php($selectedVisibility = old('visibility', isset($event) ? ($event->visibility !== null ? $event->visibility : $default_visibility) : $default_visibility))
                                @foreach($visibility_types as $type => $label)
                                    <label class="font-weight-normal d-block mb-1">
                                        <input type="checkbox" name="visibility[]" value="{{ $type }}" {{ in_array($type, $selectedVisibility, true) ? 'checked' : '' }}>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="icon-checkmark3 position-left"></i> Submit Form
                        </button>
                    </form>
                </div>

                {{-- Manage Events Tab --}}
                <div class="tab-pane fade {{ isset($event) ? 'show active' : '' }}" id="manage-events">
                    <div class="bulk-delete-form">
                    <div class="bulk-toolbar">
                        <span class="selection-counter text-muted" aria-live="polite">0 of {{ $events->count() }} entries selected</span>
                        <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                        <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;" disabled>Delete Selected</button>
                        <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                    </div>
                    <form method="POST" action="{{ route('events.delete_selected') }}" class="bulk-delete-submit-form" style="display:none;">
                        @csrf
                    </form>
                    <table id="manage-events-table" class="table bulk-select-table">
                        <thead>
                        <tr>
                            <th class="select-column"><i class="icon-checkmark-circle"></i></th>
                            <th class="date-column">Date</th>
                            <th>Event</th>
                            <th>Created By</th>
                            <th class="created-on-column">Created On</th>
                            <th class="text-center action-column">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($events as $evt)
                            <tr class="selectable-row">
                                <td class="select-cell"><input type="checkbox" name="event_ids[]" value="{{ $evt->id }}" class="bulk-select-checkbox"></td>
                                <td class="date-column">{{ $evt->date->format('m/d/Y') }}</td>
                                <td>{{ substr($evt->event, 0, 50) }}{{ strlen($evt->event) > 50 ? '...' : '' }}</td>
                                <td>{{ $evt->creator->name ?? 'N/A' }}</td>
                                <td class="created-on-column">{{ $evt->created_at->format('m/d/Y') }}</td>
                                <td class="text-center action-cell action-column">
                                    <div class="list-icons">
                                        <a href="{{ route('events.edit', Qs::hash($evt->id)) }}" class="list-icons-item" title="Edit event">
                                            <i class="icon-paragraph-justify3"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No events found</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var table = $('#manage-events-table').DataTable({
                autoWidth: false,
                paging: false,
                info: false,
                lengthChange: false,
                columnDefs: [
                    { targets: 1, width: '105px' },
                    { targets: 4, width: '130px' },
                    { targets: -1, width: '52px', orderable: false }
                ],
                dom: '<"events-table-controls"f>rt'
            });
            var form = document.querySelector('#manage-events-table').closest('.bulk-delete-form');
            var controls = document.querySelector('#manage-events-table_wrapper .events-table-controls');
            var toolbar = form.querySelector('.bulk-toolbar');
            var submitForm = form.querySelector('.bulk-delete-submit-form');
            var toggleButton = form.querySelector('.toggle-select-mode');
            var deleteButton = form.querySelector('.delete-selected');
            var selectAllButton = form.querySelector('.select-all');
            var clearButton = form.querySelector('.clear-selection');
            var counter = form.querySelector('.selection-counter');
            var checkboxes = Array.prototype.slice.call(form.querySelectorAll('.bulk-select-checkbox'));
            if (controls) controls.appendChild(toolbar);

            function selectedCount() {
                return checkboxes.filter(function (checkbox) { return checkbox.checked; }).length;
            }
            function syncRow(checkbox) {
                var row = checkbox.closest('tr');
                if (row) row.classList.toggle('row-selected', checkbox.checked);
            }
            function updateSelection() {
                deleteButton.disabled = selectedCount() === 0;
                counter.textContent = selectedCount() + ' of ' + checkboxes.length + ' entries selected';
            }
            function clearSelection() {
                checkboxes.forEach(function (checkbox) { checkbox.checked = false; syncRow(checkbox); });
                updateSelection();
            }

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () { syncRow(checkbox); updateSelection(); });
            });
            table.table().container().addEventListener('click', function (event) {
                if (!table.table().node().classList.contains('select-mode') || event.target.closest('.action-cell') || event.target.closest('a, button, input')) return;
                var row = event.target.closest('tr.selectable-row');
                var checkbox = row && row.querySelector('.bulk-select-checkbox');
                if (!checkbox) return;
                checkbox.checked = !checkbox.checked;
                syncRow(checkbox);
                updateSelection();
            });
            toggleButton.addEventListener('click', function () {
                var selecting = table.table().node().classList.toggle('select-mode');
                deleteButton.style.display = selectAllButton.style.display = clearButton.style.display = selecting ? 'inline-block' : 'none';
                counter.style.display = selecting ? 'inline-block' : 'none';
                toggleButton.textContent = selecting ? 'Cancel' : 'Select';
                if (!selecting) clearSelection();
            });
            selectAllButton.addEventListener('click', function () {
                checkboxes.forEach(function (checkbox) { checkbox.checked = true; syncRow(checkbox); });
                updateSelection();
            });
            clearButton.addEventListener('click', clearSelection);
            deleteButton.addEventListener('click', function () {
                if (!selectedCount()) return;
                swal({
                    title: 'Confirm Deletion',
                    text: 'Delete ' + selectedCount() + ' selected calendar event(s)? This cannot be undone.',
                    icon: 'warning',
                    dangerMode: true,
                    buttons: { cancel: 'Cancel', confirm: 'Delete' }
                }).then(function (confirmed) {
                    if (!confirmed) return;
                    checkboxes.filter(function (checkbox) { return checkbox.checked; }).forEach(function (checkbox) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'event_ids[]';
                        input.value = checkbox.value;
                        submitForm.appendChild(input);
                    });
                    submitForm.submit();
                });
            });
        });
    </script>
@endsection
