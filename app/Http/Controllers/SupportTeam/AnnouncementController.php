<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Helpers\ContentVisibility;
use App\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware('teamSA');
    }

    public function index()
    {
        $d['announcements'] = Announcement::orderBy('date', 'asc')->get();
        $d['visibility_types'] = ContentVisibility::types();
        $d['default_visibility'] = ContentVisibility::defaults();
        return view('pages.support_team.announcements.index', $d);
    }

    public function store()
    {
        $data = request()->validate([
            'date' => 'required|date',
            'announcement' => 'required|string',
            'visibility_present' => 'required|in:1',
            'visibility' => 'sometimes|array',
            'visibility.*' => 'string|in:registrar,accountant,teacher,student,parent',
        ]);

        unset($data['visibility_present']);
        $data['created_by'] = Auth::id();
        $data['visibility'] = ContentVisibility::normalize(request('visibility', []));
        Announcement::create($data);

        return Qs::jsonStoreOk();
    }

    public function edit($id)
    {
        $id = $this->decodeId($id);
        $d['announcement'] = Announcement::findOrFail($id);
        $d['visibility_types'] = ContentVisibility::types();
        $d['default_visibility'] = ContentVisibility::defaults();
        return view('pages.support_team.announcements.edit', $d);
    }

    public function update($id)
    {
        $id = $this->decodeId($id);
        $data = request()->validate([
            'date' => 'required|date',
            'announcement' => 'required|string',
            'visibility_present' => 'required|in:1',
            'visibility' => 'sometimes|array',
            'visibility.*' => 'string|in:registrar,accountant,teacher,student,parent',
        ]);

        unset($data['visibility_present']);
        $data['visibility'] = ContentVisibility::normalize(request('visibility', []));
        Announcement::findOrFail($id)->update($data);
        if (request()->expectsJson()) {
            return Qs::jsonUpdateOk();
        }

        return redirect()->route('announcements.index')->with('flash_success', 'Announcement updated successfully.');
    }

    public function delete($id)
    {
        $id = $this->decodeId($id);
        Announcement::destroy($id);
        return Qs::json(__('msg.delete_ok'));
    }

    public function deleteSelected(Request $request)
    {
        $data = $request->validate([
            'announcement_ids' => 'required|array|min:1',
            'announcement_ids.*' => 'required|integer|distinct|exists:announcements,id',
        ]);

        $deleted = Announcement::whereIn('id', $data['announcement_ids'])->delete();
        return redirect()->route('announcements.index')->with('flash_success', $deleted.' announcement(s) deleted successfully.');
    }

    private function decodeId($id)
    {
        return is_numeric($id) ? (int) $id : Qs::decodeHash($id);
    }
}
