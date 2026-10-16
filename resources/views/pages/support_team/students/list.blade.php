@extends('layouts.master')
@section('page_title', 'Student Information - '.$my_class->name)
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title">Students List</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            @if($advisor)
                <div class="mb-3">
                    <div class="font-weight-semibold mb-2">Class Adviser</div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="media align-items-center p-2 border rounded">
                                <div class="media-body font-weight-semibold">
                                    <a href="{{ route('users.show', Qs::hash($advisor->id)) }}" class="user-profile-link">{{ $advisor->name }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#all-students" class="nav-link active" data-toggle="tab">All {{ $my_class->name }} Students</a></li>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">Sections</a>
                    <div class="dropdown-menu dropdown-menu-right">
                        @foreach($sections as $s)
                            <a href="#s{{ $s->id }}" class="dropdown-item" data-toggle="tab">{{ $my_class->name.' '.$s->name }}</a>
                        @endforeach
                    </div>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="all-students">
                    <table class="table datatable-button-html5-columns">
                        <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Name</th>
                            <th>ADM_No</th>
                            <th>Section</th>
                            <th>Email</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($students as $s)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><a href="{{ route('students.show', Qs::hash($s->id)) }}" class="user-profile-link"><u>{{ $s->user->name }}</u></a></td>
                                <td>{{ $s->adm_no }}</td>
                                <td>{{ $my_class->name }}</td>
                                <td>{{ $s->user->email }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach($sections as $se)
                    <div class="tab-pane fade" id="s{{$se->id}}">                         <table class="table datatable-button-html5-columns">
                            <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Name</th>
                                <th>ADM_No</th>
                                <th>Email</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($students->where('section_id', $se->id) as $sr)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><a href="{{ route('students.show', Qs::hash($sr->id)) }}" class="user-profile-link"><u>{{ $sr->user->name }}</u></a></td>
                                    <td>{{ $sr->adm_no }}</td>
                                    <td>{{ $sr->user->email }}</td>
                                </tr>
                            @endforeach

                            </tbody>
                        </table>
                    </div>
                @endforeach

            </div>
        </div>
    </div>

    {{--Student List Ends--}}

@endsection
