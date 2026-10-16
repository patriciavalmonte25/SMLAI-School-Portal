@extends('layouts.master')
@section('page_title', 'School Statistics Lists')

@section('content')
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">School Statistics Full Lists</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <div style="margin-bottom: 12px;">
                <a href="{{ route('dashboard') }}" style="text-decoration: underline;">Return to Dashboard</a>
            </div>

            <h5 class="mb-4">{{ $activeTypeLabel }}</h5>

            <div class="table-responsive" style="padding-top: 20px;">
                @if($activeType === 'students')
                    <h6 class="font-weight-semibold mb-3">Boys and Girls by Class</h6>
                    <table class="table table-striped table-bordered mb-4">
                        <thead>
                        <tr>
                            <th>Class</th>
                            <th>Boys</th>
                            <th>Girls</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($classes as $class)
                            @php
                                $summary = $classSummaries->get($class->id);
                            @endphp
                            <tr>
                                <td>{{ $class->name }}</td>
                                <td>{{ (int) optional($summary)->boys_count }}</td>
                                <td>{{ (int) optional($summary)->girls_count }}</td>
                                <td>{{ (int) optional($summary)->total_students }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No classes found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    <h6 class="font-weight-semibold mb-3">Students in Alphabetical Order</h6>
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Grade &amp; Section</th>
                            <th>Gender</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td><a href="{{ route('users.show', Qs::hash($student->id)) }}" class="user-profile-link"><u>{{ $student->name }}</u></a></td>
                                <td>{{ optional(optional($student->student_record)->my_class)->name ?? optional(optional($student->student_enrollment)->my_class)->name ?? 'N/A' }}</td>
                                <td>{{ $student->gender ?: 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">No student accounts found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                @endif

                @if($activeType === 'parents')
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Grades &amp; Sections of Child/Children</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($parents->isEmpty())
                            <tr>
                                <td colspan="2" class="text-center text-muted">No parent accounts found.</td>
                            </tr>
                        @else
                            @foreach($parents as $parentUser)
                                <tr>
                                    <td><a href="{{ route('users.show', Qs::hash($parentUser->id)) }}" class="user-profile-link"><u>{{ $parentUser->name }}</u></a></td>
                                    <td>{{ $parentGradeSections[$parentUser->id] ?? 'No linked child records' }}</td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                @endif

                @if($activeType === 'teachers')
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role/s</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($teachers as $teacher)
                            <tr>
                                <td><a href="{{ route('users.show', Qs::hash($teacher->id)) }}" class="user-profile-link"><u>{{ $teacher->name }}</u></a></td>
                                <td>{{ is_array($teacher->roles) && count($teacher->roles) ? implode(', ', $teacher->roles) : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No teacher accounts found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                @endif

                @if($activeType === 'administrators')
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role/s</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($administrators as $administrator)
                            <tr>
                                <td><a href="{{ route('users.show', Qs::hash($administrator->id)) }}" class="user-profile-link"><u>{{ $administrator->name }}</u></a></td>
                                <td>{{ is_array($administrator->roles) && count($administrator->roles) ? implode(', ', $administrator->roles) : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No administrator accounts found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection
