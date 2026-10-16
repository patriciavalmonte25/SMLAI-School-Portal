@extends('layouts.master')
@section('page_title', 'Billings')
@section('content')

    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Billings</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th class="d-none">S/N</th>
                            <th>Grade Level</th>
                            <th>Last Saved</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($grade_options as $grade)
                            <tr>
                                <td class="d-none">{{ $loop->iteration }}</td>
                                <td><a href="{{ route('accountant.billings.list', ['grade_key' => $grade['key']]) }}" style="text-decoration: underline;">{{ $grade['label'] }}</a></td>
                                <td>
                                    @if(isset($existing_lists[$grade['key']]))
                                        {{ optional($existing_lists[$grade['key']]->updated_at)->format('m/d/Y h:i:s A') }}
                                    @else
                                        <span class="text-muted">Not yet created</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No grades found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
        </div>
    </div>

@endsection
