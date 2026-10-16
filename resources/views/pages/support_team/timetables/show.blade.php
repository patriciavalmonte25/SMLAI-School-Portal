@extends('layouts.master')
@section('page_title', 'View Timetable')
@section('content')

    <style>
        .timetable-display-table {
            width: 100%;
            min-width: 1200px;
            table-layout: fixed;
        }

        .timetable-display-table th,
        .timetable-display-table td {
            min-width: 170px;
            padding: 14px 18px !important;
            vertical-align: middle;
            white-space: normal;
            overflow-wrap: break-word;
            word-break: normal;
        }

        .timetable-display-table th:first-child,
        .timetable-display-table td:first-child {
            min-width: 180px;
        }
    </style>

    <div class="card">
        <div class="card-header">
            <div class="mb-2">
                <a href="{{ url()->previous() }}" onclick="if(window.history.length > 1){ event.preventDefault(); window.history.back(); }" class="text-primary">
                    <i class="icon-arrow-left8 mr-1"></i> Back
                </a>
            </div>
            <div class="row">
                <div class="col-md-4"><h6 class="card-title"><strong>Name: </strong> {{ $ttr->name }}</h6></div>
            </div>
        </div>
            <div class="card-body">
                @if(!empty($use_layout_legacy))
                <div class="table-responsive">
                <table class="table table-striped text-center timetable-display-table">
                    <thead>
                    <tr>
                        <th>Time</th>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($layout_rows_legacy as $row)
                        <tr>
                            <td>{{ $row['display_time'] }}</td>
                            @if(($row['type'] ?? 'subject') === 'blank')
                                <td colspan="5">{{ $row['blank_subject'] ?? '' }}</td>
                            @else
                                <td>
                                    <div>{{ $row['monday_subject'] ?? '' }}</div>
                                    <small class="text-muted">{{ $row['monday_teacher_name'] ?? '' }}</small>
                                </td>
                                <td>
                                    <div>{{ $row['tuesday_subject'] ?? '' }}</div>
                                    <small class="text-muted">{{ $row['tuesday_teacher_name'] ?? '' }}</small>
                                </td>
                                <td>
                                    <div>{{ $row['wednesday_subject'] ?? '' }}</div>
                                    <small class="text-muted">{{ $row['wednesday_teacher_name'] ?? '' }}</small>
                                </td>
                                <td>
                                    <div>{{ $row['thursday_subject'] ?? '' }}</div>
                                    <small class="text-muted">{{ $row['thursday_teacher_name'] ?? '' }}</small>
                                </td>
                                <td>
                                    <div>{{ $row['friday_subject'] ?? '' }}</div>
                                    <small class="text-muted">{{ $row['friday_teacher_name'] ?? '' }}</small>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
                @else
                <div class="table-responsive">
                <table class="table table-striped text-center timetable-display-table">
                    <thead>
                    <tr>
                        <th rowspan="2">Time <i class="icon-arrow-right7 ml-2"></i> <br> Date<i class="icon-arrow-down7 ml-2"></i>
                        </th>
                        @foreach($time_slots as $tms)
                            <th rowspan="2">{{ $tms->time_from }} <br>
                            {{ $tms->time_to }}
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                        @foreach($days as $day)
                            <tr>
                                @if($ttr->exam_id)
                                <td><strong>{{ date('l', strtotime($day)) }} <br> {{ date('d/m/Y', strtotime($day)) }} </strong></td>
                                @else
                                <td><strong>{{ $day }}</strong></td>
                                @endif
                                @foreach($d_time->where('day', $day) as $dt)
                                <td>{{ $dt['subject'] }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                @endif
            </div>
        </div>

@endsection