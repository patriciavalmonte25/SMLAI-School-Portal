@extends('layouts.master')
@section('page_title', 'Edit Timetable Record')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title">Edit Timetable Record</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <div class="col-md-8">
                <form class="ajax-update" method="post" action="{{ route('ttr.update', $ttr->id) }}">
                    @csrf @method('PUT')
                    <div class="form-group row">
                        <label for="target_type" class="col-lg-3 col-form-label font-weight-semibold">Timetable For <span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            <select required class="form-control select" name="target_type" id="target_type">
                                <option {{ $ttr->target_type === 'class' ? 'selected' : '' }} value="class">Class Timetable</option>
                                <option {{ $ttr->target_type === 'teacher' ? 'selected' : '' }} value="teacher">Teacher Timetable</option>
                            </select>
                        </div>
                    </div>

                    <div id="class-selection" class="form-group row">
                        <label for="my_class_id" class="col-lg-3 col-form-label font-weight-semibold">Class <span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            <select data-placeholder="Select Class" class="form-control select" name="my_class_id" id="my_class_id">
                                @foreach($my_classes as $mc)
                                    <option {{ $ttr->my_class_id == $mc->id ? 'selected' : '' }} value="{{ $mc->id }}">{{ $mc->name }}</option>
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
                                    <option {{ $ttr->owner_user_id == $tu->id ? 'selected' : '' }} value="{{ $tu->id }}">{{ $tu->name }} ({{ ucwords(str_replace('_', ' ', $tu->user_type)) }})</option>
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

    {{--Timetable Edit Ends--}}

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
            setTimeout(syncTargetFields, 150);
        });
    </script>

@endsection
