@extends('layouts.master')
@section('page_title', 'Manage Timetables')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title">Manage Timetables</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <style>
                .timetable-create-form {
                    padding: 24px;
                }

                .timetable-list-action-column {
                    box-sizing: border-box;
                    width: 96px !important;
                    min-width: 96px;
                    max-width: 96px;
                    padding: 12px 16px !important;
                    white-space: nowrap;
                }

                @media (max-width: 767px) {
                    .timetable-create-form {
                        padding: 16px 8px;
                    }
                }
            </style>
            @if($is_student)
                <ul class="nav nav-tabs nav-tabs-highlight">
                    <li class="nav-item"><a href="#student-class-timetable" class="nav-link active" data-toggle="tab">My Class Timetable</a></li>
                    <li class="nav-item"><a href="#student-staff-timetables" class="nav-link" data-toggle="tab">Administrator and Teacher Timetables</a></li>
                </ul>
                <div class="tab-content pt-3">
                    <div class="tab-pane fade show active" id="student-class-timetable">
                        @forelse($student_class_timetables as $ttr)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div><strong>{{ $ttr->my_class->name }}</strong><span class="text-muted ml-2">{{ $ttr->year }}</span></div>
                                <a href="{{ route('ttr.show', $ttr->id) }}" class="btn btn-outline-primary btn-sm">View Timetable</a>
                            </div>
                        @empty
                            <div class="alert alert-info mb-0">No Timetable is available for your class.</div>
                        @endforelse
                    </div>
                    <div class="tab-pane fade" id="student-staff-timetables">
                        <h6 class="font-weight-semibold">Administrators</h6>
                        @forelse($administrator_timetables as $ttr)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>{{ $ttr->owner->name }}<span class="text-muted ml-2">{{ $ttr->year }}</span></div>
                                <a href="{{ route('ttr.show', $ttr->id) }}" class="btn btn-outline-primary btn-sm">View Timetable</a>
                            </div>
                        @empty
                            <p class="text-muted">No Timetables are available for administrators.</p>
                        @endforelse
                        <h6 class="font-weight-semibold mt-4">Teachers</h6>
                        @forelse($teacher_timetables as $ttr)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>{{ $ttr->owner->name }}<span class="text-muted ml-2">{{ $ttr->year }}</span></div>
                                <a href="{{ route('ttr.show', $ttr->id) }}" class="btn btn-outline-primary btn-sm">View Timetable</a>
                            </div>
                        @empty
                            <p class="text-muted">No Timetables are available for teachers.</p>
                        @endforelse
                    </div>
                </div>
            @elseif($is_parent)
                <ul class="nav nav-tabs nav-tabs-highlight">
                    @foreach($child_timetables as $childTimetable)
                        <li class="nav-item"><a href="#child-timetable-{{ $loop->index }}" class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="tab">{{ $childTimetable['child']->name }}</a></li>
                    @endforeach
                </ul>
                <div class="tab-content pt-3">
                    @forelse($child_timetables as $childTimetable)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="child-timetable-{{ $loop->index }}">
                            @forelse($childTimetable['records'] as $ttr)
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div><strong>{{ $ttr->my_class->name }}</strong><span class="text-muted ml-2">{{ $ttr->year }}</span></div>
                                    <a href="{{ route('ttr.show', $ttr->id) }}" class="btn btn-outline-primary btn-sm">View Timetable</a>
                                </div>
                            @empty
                                <div class="alert alert-info mb-0">No Timetable is available for this child.</div>
                            @endforelse
                        </div>
                    @empty
                        <div class="alert alert-info mb-0">No linked children were found.</div>
                    @endforelse
                </div>
            @else
            <ul class="nav nav-tabs nav-tabs-highlight">
                @if($can_manage_timetables)
                <li class="nav-item"><a href="#add-tt" class="nav-link active" data-toggle="tab">Create Timetable</a></li>
                @endif
                <li class="nav-item"><a href="#class-ttrs" class="nav-link {{ !$can_manage_timetables ? 'active' : '' }}" data-toggle="tab">Class Timetables</a></li>
                <li class="nav-item"><a href="#teacher-ttrs" class="nav-link" data-toggle="tab">Teacher/Administrator Timetables</a></li>
            </ul>


            <div class="tab-content">

                @if($can_manage_timetables)
                <div class="tab-pane fade show active" id="add-tt">
                         <div class="col-md-8 timetable-create-form">
                       <form method="post" action="{{ route('ttr.store') }}">
                           @csrf
                           <div class="form-group row">
                               <label for="target_type" class="col-lg-3 col-form-label font-weight-semibold">Timetable For <span class="text-danger">*</span></label>
                               <div class="col-lg-9">
                                   <select required class="form-control select" name="target_type" id="target_type">
                                       <option {{ old('target_type') === 'class' ? 'selected' : '' }} value="class">Class Timetable</option>
                                       <option {{ old('target_type') === 'teacher' ? 'selected' : '' }} value="teacher">Teacher Timetable</option>
                                   </select>
                               </div>
                           </div>

                           <div id="class-selection" class="form-group row">
                               <label for="my_class_id" class="col-lg-3 col-form-label font-weight-semibold">Class <span class="text-danger">*</span></label>
                               <div class="col-lg-9">
                                   <select data-placeholder="Select Class" class="form-control select" name="my_class_id" id="my_class_id">
                                       @foreach($my_classes as $mc)
                                           <option {{ old('my_class_id') == $mc->id ? 'selected' : '' }} value="{{ $mc->id }}">{{ $mc->name }}</option>
                                       @endforeach
                                   </select>
                               </div>
                           </div>

                           <div id="owner-selection" class="form-group row" style="display:none;">
                               <label for="owner_user_id" class="col-lg-3 col-form-label font-weight-semibold">Teacher <span class="text-danger">*</span></label>
                               <div class="col-lg-9">
                                   <select data-placeholder="Select User" class="form-control select-search" name="owner_user_id" id="owner_user_id">
                                       <option value=""></option>
                                       @foreach($timetable_users as $tu)
                                           <option {{ old('owner_user_id') == $tu->id ? 'selected' : '' }} value="{{ $tu->id }}">{{ $tu->name }} ({{ ucwords(str_replace('_', ' ', $tu->user_type)) }})</option>
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
                @endif

                <div class="tab-pane fade {{ !$can_manage_timetables ? 'show active' : '' }}" id="class-ttrs">
                    <table class="table datatable-button-html5-columns">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th class="timetable-list-action-column">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($tt_records->where('target_type', 'class') as $ttr)
                                @php $can_edit_timetable = in_array(Auth::user()->user_type, ['admin', 'administrator', 'teacher']); @endphp
                                <tr>
                                    <td>{{ $ttr->name }}</td>
                                    <td class="text-center timetable-list-action-column">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                    <i class="icon-paragraph-justify3"></i>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-right">
                                                    {{--View--}}
                                                    <a href="{{ route('ttr.show', $ttr->id) }}" class="dropdown-item"><i class="icon-eye"></i> View</a>

                                                    @if($can_edit_timetable)
                                                    {{--Manage--}}
                                                    <a href="{{ route('ttr.manage', $ttr->id) }}" class="dropdown-item"><i class="icon-plus-circle2"></i> Manage</a>
                                                    {{--Edit--}}
                                                    <a href="{{ route('ttr.edit', $ttr->id) }}" class="dropdown-item"><i class="icon-pencil"></i> Edit</a>
                                                    @endif

                                                    {{--Delete--}}
                                                    @if(in_array(Auth::user()->user_type, ['admin', 'administrator']))
                                                        <a id="{{ $ttr->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item"><i class="icon-trash"></i> Delete</a>
                                                        <form method="post" id="item-delete-{{ $ttr->id }}" action="{{ route('ttr.destroy', $ttr->id) }}" class="hidden">@csrf @method('delete')</form>
                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No class Timetable records found.</td>
                                </tr>
                            @endforelse

                            </tbody>
                        </table>
                </div>

                <div class="tab-pane fade" id="teacher-ttrs">
                    <table class="table datatable-button-html5-columns">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th class="timetable-list-action-column">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($tt_records->where('target_type', 'teacher') as $ttr)
                                @php $can_edit_timetable = in_array(Auth::user()->user_type, ['admin', 'administrator']) || (Auth::user()->user_type === 'teacher' && (int) $ttr->owner_user_id === (int) Auth::id()); @endphp
                                <tr>
                                    <td>{{ $ttr->name }}</td>
                                    <td class="text-center timetable-list-action-column">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                    <i class="icon-paragraph-justify3"></i>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-right">
                                                    {{--View--}}
                                                    <a href="{{ route('ttr.show', $ttr->id) }}" class="dropdown-item"><i class="icon-eye"></i> View</a>

                                                    @if($can_edit_timetable)
                                                    {{--Manage--}}
                                                    <a href="{{ route('ttr.manage', $ttr->id) }}" class="dropdown-item"><i class="icon-plus-circle2"></i> Manage</a>
                                                    {{--Edit--}}
                                                    <a href="{{ route('ttr.edit', $ttr->id) }}" class="dropdown-item"><i class="icon-pencil"></i> Edit</a>
                                                    @endif

                                                    {{--Delete--}}
                                                    @if(in_array(Auth::user()->user_type, ['admin', 'administrator']))
                                                        <a id="{{ $ttr->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item"><i class="icon-trash"></i> Delete</a>
                                                        <form method="post" id="item-delete-{{ $ttr->id }}" action="{{ route('ttr.destroy', $ttr->id) }}" class="hidden">@csrf @method('delete')</form>
                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No teacher/administrator Timetable records found.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                </div>

            </div>
            @endif
        </div>
    </div>

    @if($can_manage_timetables)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                function syncTargetFields() {
                    var targetType = document.getElementById('target_type');
                    var classSelection = document.getElementById('class-selection');
                    var ownerSelection = document.getElementById('owner-selection');
                    var classField = document.getElementById('my_class_id');
                    var ownerField = document.getElementById('owner_user_id');
                    var mode = targetType ? targetType.value : 'class';

                    if (mode === 'teacher') {
                        if (classSelection) classSelection.style.display = 'none';
                        if (ownerSelection) ownerSelection.style.display = '';
                        if (classField) classField.required = false;
                        if (ownerField) ownerField.required = true;
                    } else {
                        if (classSelection) classSelection.style.display = '';
                        if (ownerSelection) ownerSelection.style.display = 'none';
                        if (classField) classField.required = true;
                        if (ownerField) ownerField.required = false;
                    }
                }

                $(document).on('change select2:select', '#target_type', syncTargetFields);
                syncTargetFields();

                // Some select plugins initialize after DOMContentLoaded.
                setTimeout(syncTargetFields, 150);
            });
        </script>
    @endif

    {{--Timetable Ends--}}

@endsection
