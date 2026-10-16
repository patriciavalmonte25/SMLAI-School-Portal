@extends('layouts.master')
@section('page_title', 'Edit Announcement')
@section('content')
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Edit Announcement</h6>
            <a href="{{ route('announcements.index') }}" class="btn btn-light btn-sm">Back to Announcements</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('announcements.update', Qs::hash($announcement->id)) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Announcement Date: <span class="text-danger">*</span></label>
                            <input name="date" value="{{ old('date', $announcement->date->format('m/d/Y')) }}" type="text" class="form-control date-pick" placeholder="MM/DD/YYYY" data-date-format="mm/dd/yyyy" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Announcement: <span class="text-danger">*</span></label>
                    <textarea required name="announcement" placeholder="Enter announcement" class="form-control" rows="6">{{ old('announcement', $announcement->announcement) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="d-block">Visibility</label>
                    <input type="hidden" name="visibility_present" value="1">
                    @php($selectedVisibility = old('visibility', $announcement->visibility !== null ? $announcement->visibility : $default_visibility))
                    <div class="visibility-options">
                        @foreach($visibility_types as $type => $label)
                            <label class="font-weight-normal d-block mb-1">
                                <input type="checkbox" name="visibility[]" value="{{ $type }}" {{ in_array($type, $selectedVisibility, true) ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="icon-checkmark3 position-left"></i> Submit Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection