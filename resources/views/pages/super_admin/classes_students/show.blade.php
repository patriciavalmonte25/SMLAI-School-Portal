@extends('layouts.master')
@section('page_title', $my_class->name)
@section('content')

    <div class="card">
        <div class="card-body">
            <h5 class="font-weight-semibold mb-3">{{ $my_class->name }}</h5>
            <h6 class="font-weight-semibold mb-3">Class Adviser</h6>

            @if($advisor)
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="media align-items-center p-2 border rounded">
                            <div class="media-body">
                                <div class="font-weight-semibold">
                                    @if(!$isStudent)
                                        <a href="{{ route('users.show', Qs::hash($advisor->id)) }}" class="user-profile-link">{{ $advisor->name }}</a>
                                    @else
                                        {{ $advisor->name }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-info mb-4">No class adviser has been assigned to this class yet.</div>
            @endif

            @if($isSeniorHigh && $pathwayGroups->isNotEmpty())
                @foreach($pathwayGroups as $pathwayName => $pathwayStudents)
                    <h6 class="font-weight-semibold mb-3 mt-4">{{ $pathwayName }}</h6>
                    <div class="row">
                        @foreach($pathwayStudents as $student)
                            <div class="col-md-4 mb-3">
                                <div class="media align-items-center p-2 border rounded">
                                    <div class="media-body">
                                        <div class="font-weight-semibold">
                                            @if(!$isStudent)
                                                <a href="{{ route('users.show', Qs::hash($student->id)) }}" class="user-profile-link">{{ $student->name }}</a>
                                            @else
                                            {{ $student->name }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @elseif($students->isNotEmpty())
                <h6 class="font-weight-semibold mb-3">Students</h6>
                <div class="row">
                    @foreach($students as $student)
                        <div class="col-md-4 mb-3">
                            <div class="media align-items-center p-2 border rounded">
                                <div class="media-body">
                                    <div class="font-weight-semibold">
                                            @if(!$isStudent)
                                                <a href="{{ route('users.show', Qs::hash($student->id)) }}" class="user-profile-link">{{ $student->name }}</a>
                                            @else
                                        {{ $student->name }}
                                            @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <h6 class="font-weight-semibold mb-3">Students</h6>
                <div class="alert alert-info">No active students found in this class.</div>
            @endif
        </div>
    </div>

@endsection
