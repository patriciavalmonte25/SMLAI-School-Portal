<html>
<head>
    <title>Timetable - {{ $ttr->name.' - '.$ttr->year }}</title>
    <style>
        @media print {

            td, th {
                padding: 20px 5px;
                text-align: center;
                font-size: 14px;
            }

            @page {
                size: landscape;   /* auto is the initial value */
                margin: 0;  /* this affects the margin in the printer settings */
            }

            html {
                background-color: #FFFFFF;
                margin: 0; /* this affects the margin on the html before sending to printer */
            }

            body {
                margin: 0 10mm; /* margin you want for the content */
            }
        }

        td {
            text-align: center;
        }

    </style>
</head>
<body>
<div class="container">
    <div id="print" xmlns:margin-top="http://www.w3.org/1999/xhtml">
        {{--    Logo N School Details--}}
        <table width="100%">
            <tr>
                <td >
                    <strong><span style="color: #1b0c80; font-size: 25px;">{{ strtoupper(config('app.name')) }}</span></strong><br/>
                    {{-- <strong><span style="color: #1b0c80; font-size: 20px;">MINNA, NIGER STATE</span></strong><br/>--}}
                    <strong><span style="color: #000; font-size: 15px;"><i>{{ ucwords($s['address']) }}</i></span></strong><br/>
                    <strong><span style="color: #000; text-decoration: underline; font-size: 15px;"><i>{{ config('app.url') }}/i></span></strong>
                    <br /> <br />
                    <strong><span style="color: #000; font-size: 15px;"> TIMETABLE FOR {{ strtoupper(($my_class->name ?? optional($timetableOwner)->name ?? 'N/A'). ' ('.$ttr->year.')' ) }}
                    </span></strong>
                </td>
            </tr>
        </table>

        {{-- Tabulation Begins --}}
        <table cellpadding="20" style="width:100%; border-collapse:collapse; border: 1px solid #000; margin: 10px auto;" border="1">
            @if(!empty($use_layout_legacy))
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
                        <td>{{ $row['monday_subject'] ?? '' }} {{ $row['monday_teacher_name'] ? '('.$row['monday_teacher_name'].')' : '' }}</td>
                        <td>{{ $row['tuesday_subject'] ?? '' }} {{ $row['tuesday_teacher_name'] ? '('.$row['tuesday_teacher_name'].')' : '' }}</td>
                        <td>{{ $row['wednesday_subject'] ?? '' }} {{ $row['wednesday_teacher_name'] ? '('.$row['wednesday_teacher_name'].')' : '' }}</td>
                        <td>{{ $row['thursday_subject'] ?? '' }} {{ $row['thursday_teacher_name'] ? '('.$row['thursday_teacher_name'].')' : '' }}</td>
                        <td>{{ $row['friday_subject'] ?? '' }} {{ $row['friday_teacher_name'] ? '('.$row['friday_teacher_name'].')' : '' }}</td>
                    @endif
                </tr>
            @endforeach
            </tbody>
            @else
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
            @endif
        </table>
    </div>
</div>

<script>
    window.print();
</script>
</body>
</html>
