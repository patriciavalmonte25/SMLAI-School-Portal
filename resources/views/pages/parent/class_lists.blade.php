@extends('layouts.master')
@section('page_title', "Children's Class Lists")
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title">Children's Class Lists</h6>
            {!! Qs::getPanelOptions() !!}
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>Class</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($classes as $class)
                    <tr>
                        <td>{{ $class->name }}</td>
                        <td><a href="{{ route('student.my_class', ['class_id' => $class->id]) }}" class="btn btn-outline-primary btn-sm">View Class List</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted">No class lists are assigned to your children.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection