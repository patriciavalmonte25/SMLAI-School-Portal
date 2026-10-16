<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Helpers\ContentVisibility;
use App\Models\Event;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('teamSA');
    }

    public function index()
    {
        $d['events'] = Event::orderBy('date', 'asc')->get();
        $d['visibility_types'] = ContentVisibility::types();
        $d['default_visibility'] = ContentVisibility::defaults();
        return view('pages.support_team.events.index', $d);
    }

    public function store()
    {
        $data = request()->validate([
            'date' => 'required|date',
            'event' => 'required|string',
            'visibility_present' => 'required|in:1',
            'visibility' => 'sometimes|array',
            'visibility.*' => 'string|in:registrar,accountant,teacher,student,parent',
        ]);

        unset($data['visibility_present']);
        $data['created_by'] = Auth::id();
        $data['visibility'] = ContentVisibility::normalize(request('visibility', []));
        Event::create($data);

        return Qs::jsonStoreOk();
    }

    public function edit($id)
    {
        $id = $this->decodeId($id);
        $d['event'] = Event::findOrFail($id);
        $d['visibility_types'] = ContentVisibility::types();
        $d['default_visibility'] = ContentVisibility::defaults();
        return view('pages.support_team.events.edit', $d);
    }

    public function update($id)
    {
        $id = $this->decodeId($id);
        $data = request()->validate([
            'date' => 'required|date',
            'event' => 'required|string',
            'visibility_present' => 'required|in:1',
            'visibility' => 'sometimes|array',
            'visibility.*' => 'string|in:registrar,accountant,teacher,student,parent',
        ]);

        unset($data['visibility_present']);
        $data['visibility'] = ContentVisibility::normalize(request('visibility', []));
        Event::findOrFail($id)->update($data);
        if (request()->expectsJson()) {
            return Qs::jsonUpdateOk();
        }

        return redirect()->route('events.index')->with('flash_success', 'Event updated successfully.');
    }

    public function delete($id)
    {
        $id = $this->decodeId($id);
        Event::destroy($id);
        return Qs::json(__('msg.delete_ok'));
    }

    public function deleteSelected(Request $request)
    {
        $data = $request->validate([
            'event_ids' => 'required|array|min:1',
            'event_ids.*' => 'required|integer|distinct|exists:events,id',
        ]);

        $deleted = Event::whereIn('id', $data['event_ids'])->delete();
        return redirect()->route('events.index')->with('flash_success', $deleted.' event(s) deleted successfully.');
    }

    private function decodeId($id)
    {
        return is_numeric($id) ? (int) $id : Qs::decodeHash($id);
    }
}
