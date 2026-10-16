@extends('layouts.master')
@section('page_title', 'My Children')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title">My Children</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <table class="table datatable-button-html5-columns">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>LRN</th>
                    <th>Class</th>
                    <th class="text-center" style="width:56px; min-width:56px; white-space:nowrap;">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($students as $s)
                    <tr>
                        @php
                            $studentRecord = $s->student_record;
                            $studentEnrollment = $s->student_enrollment;
                            $className = optional(optional($studentRecord)->my_class)->name ?: optional(optional($studentEnrollment)->my_class)->name;
                        @endphp
                        <td>{{ $s->name }}</td>
                        <td>{{ optional($s->student_record)->adm_no ?? optional($s->student_enrollment)->student_id ?? $s->username }}</td>
                        <td>{{ $className ?: 'No class assigned' }}</td>
                        <td class="text-center" style="width:56px; min-width:56px; white-space:nowrap;">
                            <div class="list-icons">
                                <div class="dropdown">
                                    <a href="#" class="list-icons-item" data-toggle="dropdown">
                                        <i class="icon-paragraph-justify3"></i>
                                    </a>

                                    <div class="dropdown-menu dropdown-menu-left">
                                        @if($s->student_record)
                                            <a href="{{ route('students.show', Qs::hash($s->student_record->id)) }}" class="dropdown-item">Profile</a>
                                        @else
                                            <a href="{{ route('users.show', Qs::hash($s->id)) }}" class="dropdown-item">Profile</a>
                                        @endif
                                        <a href="{{ route('students.grades.show', Qs::hash($s->id)) }}" class="dropdown-item">Grades</a>

                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

        </div>
    </div>

    {{--Student List Ends--}}

@endsection
