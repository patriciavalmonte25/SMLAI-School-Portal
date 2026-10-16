@extends('layouts.master')
@section('page_title', 'Edit Event')
@section('content')
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Edit Event</h6>
            <a href="{{ route('events.index') }}" class="btn btn-light btn-sm">Back to Events</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('events.update', Qs::hash($event->id)) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Event Date: <span class="text-danger">*</span></label>
                            <input name="date" value="{{ old('date', $event->date->format('m/d/Y')) }}" type="text" class="form-control date-pick" placeholder="MM/DD/YYYY" data-date-format="mm/dd/yyyy" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Event: <span class="text-danger">*</span></label>
                    <textarea required name="event" placeholder="Enter event" class="form-control" rows="6">{{ old('event', $event->event) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="d-block">Visibility</label>
                    <input type="hidden" name="visibility_present" value="1">
                    @php($selectedVisibility = old('visibility', $event->visibility !== null ? $event->visibility : $default_visibility))
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