@extends('layouts.master')
@section('page_title', 'My Dashboard')
@section('content')

    {{--Events Calendar Begins--}}
    <div class="card">
        <div class="card-header header-elements-inline">
            <h5 class="card-title">School Events Calendar</h5>
         {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <div class="fullcalendar-basic"></div>
        </div>
    </div>
    {{--Events Calendar Ends--}}

    <style>
        .fc-event-title {
            font-size: 0.75rem !important;
            padding: 2px 3px !important;
            white-space: normal !important;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.2;
        }
        .fc-event {
            cursor: pointer !important;
        }
        .fc-event:hover {
            opacity: 0.8;
        }
        .event-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            display: none;
            z-index: 9999;
            padding: 24px;
            align-items: center;
            justify-content: center;
        }
        .event-modal-overlay.show {
            display: flex;
        }
        .event-modal {
            position: relative;
            width: min(90vw, 360px);
            max-width: 360px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
            box-shadow: none;
            padding: 20px 18px 18px 18px;
            font-size: 16px;
            line-height: 1.5;
        }
        .event-modal-header {
            display: flex;
            justify-content: flex-end;
            padding-right: 24px;
            margin-bottom: 8px;
        }
        .event-modal-date {
            color: #6c757d;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }
        .event-modal-close {
            position: absolute;
            top: 6px;
            right: 8px;
            border: 0;
            background: none;
            font-size: 18px;
            color: #666;
            cursor: pointer;
            line-height: 1;
            padding: 0;
        }
        .event-modal-close:hover {
            color: #000;
        }
        .event-modal-content {
            white-space: pre-wrap;
            font-size: 18px;
            line-height: 1.55;
        }
    </style>

    <div class="event-modal-overlay" id="eventModalOverlay">
        <div class="event-modal" role="dialog" aria-modal="true">
            <button type="button" class="event-modal-close" aria-label="Close">&times;</button>
            <div class="event-modal-header">
                <span class="event-modal-date" id="eventModalDate"></span>
            </div>
            <div class="event-modal-content" id="eventModalContent"></div>
        </div>
    </div>

    @section('scripts')
        <script>
            $(function() {
                var eventsData = {!! json_encode($events->map(function($evt) { return ['title' => $evt->event, 'start' => $evt->date->format('Y-m-d'), 'description' => $evt->event, 'displayDate' => $evt->date->format('m/d/Y')]; }), JSON_UNESCAPED_SLASHES) !!};

                if ($('.fullcalendar-basic').data('fullCalendar')) {
                    $('.fullcalendar-basic').fullCalendar('destroy');
                }

                $('.fullcalendar-basic').fullCalendar({
                    header: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'month,basicWeek,basicDay'
                    },
                    events: eventsData,
                    eventRender: function(event, element) {
                        element.attr('data-event-title', event.title);
                        element.attr('data-event-description', event.description || event.title);
                        element.attr('data-event-date', event.displayDate || '');
                        element.attr('title', event.description || event.title);
                        element.find('.fc-title').css({
                            'font-size': '0.75rem',
                            'line-height': '1.1',
                            'white-space': 'normal'
                        });
                    }
                });

                $(document).off('click', '.fc-event').on('click', '.fc-event', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var title = $(this).data('event-title') || $(this).find('.fc-title').text().trim();
                    var description = $(this).data('event-description') || title;
                    var eventDate = $(this).data('event-date') || '';

                    $('#eventModalContent').text(description);
                    $('#eventModalDate').text(eventDate);
                    $('#eventModalOverlay').addClass('show');
                });

                $(document).off('click', '.event-modal-close').on('click', '.event-modal-close', function() {
                    $('#eventModalOverlay').removeClass('show');
                });

                $(document).off('click', '#eventModalOverlay').on('click', '#eventModalOverlay', function(e) {
                    if ($(e.target).is('#eventModalOverlay')) {
                        $('#eventModalOverlay').removeClass('show');
                    }
                });
            });
        </script>
    @endsection

    {{--Announcements Begins--}}
    <div class="card">
        <div class="card-header header-elements-inline">
            <h5 class="card-title">Announcements</h5>
         {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            @if($announcements->count() > 0)
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Announcement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($announcements as $ann)
                            <tr>
                                <td><strong>{{ $ann->date->format('m/d/Y') }}</strong></td>
                                <td>{{ $ann->announcement }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-info alert-styled-left alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">Info!</span> No announcements at this time.
                </div>
            @endif
        </div>
    </div>
    {{--Announcements Ends--}}
    @endsection
