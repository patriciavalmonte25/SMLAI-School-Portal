<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Requests\TimeTable\TSRequest;
use App\Http\Requests\TimeTable\TTRecordRequest;
use App\Http\Requests\TimeTable\TTRequest;
use App\Models\Setting;
use App\Models\StudentEnrollment;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\Models\TimeTable;
use App\Models\TimeTableRecord;
use App\User;
use App\Repositories\ExamRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\TimeTableRepo;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TimeTableController extends Controller
{
    protected $tt, $my_class, $exam, $year;

    public function __construct(TimeTableRepo $tt, MyClassRepo $mc, ExamRepo $exam)
    {
        $this->tt = $tt;
        $this->my_class = $mc;
        $this->exam = $exam;
        $this->year = Qs::getCurrentSession();
    }

    public function index()
    {
        $user = Auth::user();
        $d['is_student'] = $user->user_type === 'student';
        $d['is_parent'] = $user->user_type === 'parent';
        $d['can_manage_timetables'] = $this->canManageTimetables();
        $d['my_classes'] = $d['can_manage_timetables'] ? $this->my_class->all() : collect();
        $d['timetable_users'] = User::whereIn('user_type', ['teacher', 'admin', 'administrator'])
            ->when($user->user_type === 'teacher', function ($query) use ($user) {
                $query->where('id', $user->id);
            })
            ->orderBy('name')->get();
        $d['tt_records'] = $d['can_manage_timetables']
            ? $this->tt->getAllRecords()
            : collect();

        if($d['is_student']) {
            $classIds = $this->classIdsForStudent($user->id);
            $d['student_class_timetables'] = TimeTableRecord::with(['my_class', 'exam', 'owner'])
                ->where('target_type', 'class')->whereIn('my_class_id', $classIds)->orderBy('year', 'desc')->get();
            $d['administrator_timetables'] = TimeTableRecord::with('owner')
                ->where('target_type', 'teacher')->whereHas('owner', function ($query) {
                    $query->whereIn('user_type', ['admin', 'administrator']);
                })->orderBy('name')->get();
            $d['teacher_timetables'] = TimeTableRecord::with('owner')
                ->where('target_type', 'teacher')->whereHas('owner', function ($query) {
                    $query->where('user_type', 'teacher');
                })->orderBy('name')->get();
        }

        if($d['is_parent']) {
            $d['child_timetables'] = $this->childrenForParent($user->id)->map(function ($child) {
                return [
                    'child' => $child,
                    'records' => TimeTableRecord::with(['my_class', 'exam'])
                        ->where('target_type', 'class')
                        ->whereIn('my_class_id', $this->classIdsForStudent($child->id))
                        ->orderBy('year', 'desc')->get(),
                ];
            });
        }

        return view('pages.support_team.timetables.index', $d);
    }

    public function manage($ttr_id)
    {
        $d['ttr_id'] = $ttr_id;
        $d['ttr'] = $ttr = $this->tt->findRecord($ttr_id);
        $this->authorizeEdit($ttr);
        $d['time_slots'] = $this->tt->getTimeSlotByTTR($ttr_id);
        $d['ts_existing'] = $this->tt->getExistingTS($ttr_id);
        $d['subjects'] = $ttr->my_class_id
            ? $this->my_class->getSubject(['my_class_id' => $ttr->my_class_id])->get()
            : Subject::orderBy('name')->get();
        $d['my_class'] = $this->my_class->find($ttr->my_class_id);

        if($ttr->exam_id){
            $d['exam_id'] = $ttr->exam_id;
            $d['exam'] = $this->exam->find($ttr->exam_id);
        }

        $d['timetable_users'] = User::whereIn('user_type', ['teacher', 'admin', 'administrator'])->orderBy('name')->get();
        $templates = TimeTableRecord::with(['my_class', 'owner'])
            ->where('target_type', $ttr->target_type)
            ->where('id', '<>', $ttr->id);
        if($ttr->target_type === 'teacher') {
            $templates->whereHas('owner', function ($query) {
                $query->whereIn('user_type', ['teacher', 'admin', 'administrator']);
            });
        }
        $d['existing_templates'] = $templates->orderBy('name')->get()->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'owner' => optional($template->owner)->name,
                'class' => optional($template->my_class)->name,
                'rows' => $this->layoutRowsForRecord($template),
            ];
        })->values();
        $d['layout_days'] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $d['layout_teacher_options'] = $d['timetable_users']->map(function($tu){
            return [
                'id' => $tu->id,
                'label' => $tu->name.' ('.ucwords(str_replace('_', ' ', $tu->user_type)).')',
            ];
        })->values();

        $layoutRows = $this->layoutRowsForRecord($ttr);
        if(!is_array($layoutRows) || !count($layoutRows)){
            $layoutRows = $this->defaultLayoutRows();
        }
        $d['layout_rows'] = $layoutRows;

        $d['tts'] = $this->tt->getTimeTable(['ttr_id' => $ttr_id]);

        return view('pages.support_team.timetables.manage', $d);
    }

    public function store(TTRequest $req)
    {
        $record = $this->tt->findRecord($req->ttr_id);
        $this->authorizeEdit($record);
        abort_unless((int) $this->tt->findTimeSlot($req->ts_id)->ttr_id === (int) $record->id, 403);
        $data = $req->all();
        $data['ttr_id'] = $record->id;
        $tms = $this->tt->findTimeSlot($req->ts_id);
        $d_date = $req->exam_date ?? $req->day;
        $data['timestamp_from'] = strtotime($d_date.' '.$tms->time_from);
        $data['timestamp_to'] = strtotime($d_date.' '.$tms->time_to);

        $this->tt->create($data);

        return Qs::jsonStoreOk();
    }

    public function update(TTRequest $req, $tt_id)
    {
        $timeTable = TimeTable::findOrFail($tt_id);
        $record = $this->tt->findRecord($timeTable->ttr_id);
        $this->authorizeEdit($record);
        abort_unless((int) $req->ttr_id === (int) $record->id, 403);
        abort_unless((int) $this->tt->findTimeSlot($req->ts_id)->ttr_id === (int) $record->id, 403);
        $data = $req->all();
        $data['ttr_id'] = $record->id;
        $tms = $this->tt->findTimeSlot($req->ts_id);
        $d_date = $req->exam_date ?? $req->day;
        $data['timestamp_from'] = strtotime($d_date.' '.$tms->time_from);
        $data['timestamp_to'] = strtotime($d_date.' '.$tms->time_to);

        $this->tt->update($tt_id, $data);

        return back()->with('flash_success', __('msg.update_ok'));

    }

    public function delete($tt_id)
    {
        $timeTable = TimeTable::findOrFail($tt_id);
        $this->authorizeEdit($this->tt->findRecord($timeTable->ttr_id));
        $this->tt->delete($tt_id);
        return back()->with('flash_success', __('msg.delete_ok'));
    }

    /*********** TIME SLOTS *************/

    public function store_time_slot(TSRequest $req)
    {
        $this->authorizeEdit($this->tt->findRecord($req->ttr_id));
        $data = $req->all();
        $data['meridian_from'] = '';
        $data['meridian_to'] = '';
        $data['time_from'] = $tf = str_pad((string)$req->hour_from, 2, '0', STR_PAD_LEFT).':'.$req->min_from;
        $data['time_to'] = $tt = str_pad((string)$req->hour_to, 2, '0', STR_PAD_LEFT).':'.$req->min_to;
        $data['timestamp_from'] = strtotime('1970-01-01 '.$tf);
        $data['timestamp_to'] = strtotime('1970-01-01 '.$tt);
        $data['full'] = $tf.' - '.$tt;

        if($tf == $tt){
            return response()->json(['msg' => __('msg.invalid_time_slot'), 'ok' => FALSE]);
        }

        $this->tt->createTimeSlot($data);
        return Qs::jsonStoreOk();
    }

    public function use_time_slot(Request $req, $ttr_id)
    {
        $this->validate($req, ['ttr_id' => 'required'], [], ['ttr_id' => 'Timetable Record']);
        $this->authorizeEdit($this->tt->findRecord($ttr_id));
        abort_unless((int) $req->ttr_id === (int) $ttr_id, 403);

        $d = [];  //  Empty Current Time Slot Before Adding New
        $this->tt->deleteTimeSlots(['ttr_id' => $ttr_id]);
        $time_slots = $this->tt->getTimeSlotByTTR($req->ttr_id)->toArray();

        foreach($time_slots as $ts){
            $ts['ttr_id'] = $ttr_id;
            $this->tt->createTimeSlot($ts);
        }

        return redirect()->route('ttr.manage', $ttr_id)->with('flash_success', __('msg.update_ok'));

    }

    public function edit_time_slot($ts_id)
    {
        $d['tms'] = $this->tt->findTimeSlot($ts_id);
        $this->authorizeEdit($this->tt->findRecord($d['tms']->ttr_id));
        return view('pages.support_team.timetables.time_slots.edit', $d);
    }

    public function update_time_slot(TSRequest $req, $ts_id)
    {
        $timeSlot = $this->tt->findTimeSlot($ts_id);
        $this->authorizeEdit($this->tt->findRecord($timeSlot->ttr_id));
        abort_unless((int) $req->ttr_id === (int) $timeSlot->ttr_id, 403);
        $data = $req->all();
        $data['meridian_from'] = '';
        $data['meridian_to'] = '';
        $data['time_from'] = $tf = str_pad((string)$req->hour_from, 2, '0', STR_PAD_LEFT).':'.$req->min_from;
        $data['time_to'] = $tt = str_pad((string)$req->hour_to, 2, '0', STR_PAD_LEFT).':'.$req->min_to;
        $data['timestamp_from'] = strtotime('1970-01-01 '.$tf);
        $data['timestamp_to'] = strtotime('1970-01-01 '.$tt);
        $data['full'] = $tf.' - '.$tt;

        if($tf == $tt){
            return back()->with('flash_danger', __('msg.invalid_time_slot'));
        }

        $this->tt->updateTimeSlot($ts_id, $data);
        return redirect()->route('ttr.manage', $req->ttr_id)->with('flash_success', __('msg.update_ok'));
    }

    public function delete_time_slot($ts_id)
    {
        $timeSlot = $this->tt->findTimeSlot($ts_id);
        $this->authorizeEdit($this->tt->findRecord($timeSlot->ttr_id));
        $this->tt->deleteTimeSlot($ts_id);
        return back()->with('flash_success', __('msg.delete_ok'));
    }


    /*********** RECORDS *************/

    public function edit_record($ttr_id)
    {
        $d['ttr'] = $ttr = $this->tt->findRecord($ttr_id);
        $this->authorizeEdit($ttr);
        $d['exams'] = $this->exam->getExam(['year' => $ttr->year]);
        $d['my_classes'] = $this->my_class->all();
        $d['timetable_users'] = User::whereIn('user_type', ['teacher', 'admin', 'administrator'])->orderBy('name')->get();

        return view('pages.support_team.timetables.edit', $d);
    }

    public function show_record($ttr_id)
    {
        $d_time = [];
        $d['ttr'] = $ttr = $this->tt->findRecord($ttr_id);
        $this->authorizeView($ttr);
        $d['ttr_id'] = $ttr_id;
        $d['my_class'] = $this->my_class->find($ttr->my_class_id);
        $d['timetableOwner'] = $ttr->owner;

        $layoutRows = $ttr->timetable_layout ? json_decode($ttr->timetable_layout, true) : [];
        $d['layout_rows'] = (is_array($layoutRows) && count($layoutRows)) ? $layoutRows : [];
        $d['use_layout_legacy'] = count($d['layout_rows']) > 0;
        $teacherLookup = User::whereIn('user_type', ['teacher', 'admin', 'administrator'])->pluck('name', 'id');
        $d['layout_rows_legacy'] = $this->buildLegacyLayoutRows($d['layout_rows'], $teacherLookup);

        if($d['use_layout_legacy']){
            return view('pages.support_team.timetables.show', $d);
        }

        $d['time_slots'] = $tms = $this->tt->getTimeSlotByTTR($ttr_id);
        $d['tts'] = $tts = $this->tt->getTimeTable(['ttr_id' => $ttr_id]);

        if($ttr->exam_id){
            $d['exam_id'] = $ttr->exam_id;
            $d['exam'] = $this->exam->find($ttr->exam_id);
            $d['days'] = $days = $tts->unique('exam_date')->pluck('exam_date');
            $d_date = 'exam_date';
        }

        else{
            $d['days'] = $days = $tts->unique('day')->pluck('day');
            $d_date = 'day';
        }

        foreach ($days as $day) {
            foreach ($tms as $tm) {
                $d_time[] = ['day' => $day, 'time' => $tm->full, 'subject' => $tts->where('ts_id', $tm->id)->where($d_date, $day)->first()->subject->name ?? NULL ];
            }
        }

        $d['d_time'] = collect($d_time);

        return view('pages.support_team.timetables.show', $d);
    }
    public function print_record($ttr_id)
    {
        $d_time = [];
        $d['ttr'] = $ttr = $this->tt->findRecord($ttr_id);
        $this->authorizeView($ttr);
        $d['ttr_id'] = $ttr_id;
        $d['my_class'] = $this->my_class->find($ttr->my_class_id);
        $d['timetableOwner'] = $ttr->owner;

        $layoutRows = $ttr->timetable_layout ? json_decode($ttr->timetable_layout, true) : [];
        $d['layout_rows'] = (is_array($layoutRows) && count($layoutRows)) ? $layoutRows : [];
        $d['use_layout_legacy'] = count($d['layout_rows']) > 0;
        $teacherLookup = User::whereIn('user_type', ['teacher', 'admin', 'administrator'])->pluck('name', 'id');
        $d['layout_rows_legacy'] = $this->buildLegacyLayoutRows($d['layout_rows'], $teacherLookup);

        $d['time_slots'] = $tms = $this->tt->getTimeSlotByTTR($ttr_id);
        $d['tts'] = $tts = $this->tt->getTimeTable(['ttr_id' => $ttr_id]);

        if($ttr->exam_id){
            $d['exam_id'] = $ttr->exam_id;
            $d['exam'] = $this->exam->find($ttr->exam_id);
            $d['days'] = $days = $tts->unique('exam_date')->pluck('exam_date');
            $d_date = 'exam_date';
        }

        else{
            $d['days'] = $days = $tts->unique('day')->pluck('day');
            $d_date = 'day';
        }

        foreach ($days as $day) {
            foreach ($tms as $tm) {
                $d_time[] = ['day' => $day, 'time' => $tm->full, 'subject' => $tts->where('ts_id', $tm->id)->where($d_date, $day)->first()->subject->name ?? NULL ];
            }
        }

        $d['d_time'] = collect($d_time);
        $d['s'] = Setting::all()->flatMap(function($s){
            return [$s->type => $s->description];
        });

        if($d['use_layout_legacy']){
            return view('pages.support_team.timetables.print', $d);
        }

        return view('pages.support_team.timetables.print', $d);
    }

    public function store_record(TTRecordRequest $req)
    {
        abort_unless($this->canManageTimetables(), 403);
        $data = $req->all();
        $data['year'] = $this->year;
        $data['exam_id'] = null;
        $data['timetable_layout'] = json_encode($this->defaultLayoutRows());

        if($data['target_type'] === 'class'){
            $classId = (int) $data['my_class_id'];
            $myClass = $this->my_class->find($classId);

            if(!$myClass){
                return response()->json(['ok' => false, 'msg' => 'Selected class is invalid.']);
            }

            $data['name'] = $this->availableTimetableTitle(trim($myClass->name));
            $data['owner_user_id'] = null;
        }

        if($data['target_type'] === 'teacher'){
            if(Auth::user()->user_type === 'teacher') {
                $data['owner_user_id'] = Auth::id();
            }
            $ownerUserId = (int) $data['owner_user_id'];
            $owner = User::where('id', $data['owner_user_id'])
                ->whereIn('user_type', ['teacher', 'admin', 'administrator'])
                ->first();

            if(!$owner){
                return response()->json(['ok' => false, 'msg' => 'Please select a valid teacher or administrator.']);
            }

            $exists = TimeTableRecord::where('target_type', 'teacher')
                ->where('owner_user_id', $ownerUserId)
                ->exists();

            if($exists){
                return response()->json(['ok' => false, 'msg' => 'A Timetable already exists for this teacher/administrator.']);
            }

            $data['name'] = trim($owner->name)."'s Timetable";
            $data['my_class_id'] = null;
        }

        $record = $this->tt->createRecord($data);

        if($req->expectsJson() || $req->ajax()){
            return response()->json([
                'ok' => true,
                'msg' => __('msg.store_ok'),
                'url' => route('ttr.manage', $record->id),
            ]);
        }

        return redirect()->route('ttr.manage', $record->id)->with('flash_success', __('msg.store_ok'));
    }

    public function update_record(TTRecordRequest $req, $id)
    {
        $record = $this->tt->findRecord($id);
        $this->authorizeEdit($record);
        $data = $req->all();
        $data['exam_id'] = null;

        if($data['target_type'] === 'class'){
            $classId = (int) $data['my_class_id'];
            $myClass = $this->my_class->find($classId);
            if(!$myClass){
                return response()->json(['ok' => false, 'msg' => 'Selected class is invalid.']);
            }

            $data['name'] = (int) $record->my_class_id === $classId && $record->target_type === 'class'
                ? $record->name
                : $this->availableTimetableTitle(trim($myClass->name), $record->id);
            $data['owner_user_id'] = null;
        }

        if($data['target_type'] === 'teacher'){
            if(Auth::user()->user_type === 'teacher') {
                $data['owner_user_id'] = Auth::id();
            }
            $ownerUserId = (int) $data['owner_user_id'];
            $owner = User::where('id', $data['owner_user_id'])
                ->whereIn('user_type', ['teacher', 'admin', 'administrator'])
                ->first();

            if(!$owner){
                return response()->json(['ok' => false, 'msg' => 'Please select a valid teacher or administrator.']);
            }

            $exists = TimeTableRecord::where('target_type', 'teacher')
                ->where('owner_user_id', $ownerUserId)
                ->where('id', '<>', $id)
                ->exists();

            if($exists){
                return response()->json(['ok' => false, 'msg' => 'A Timetable already exists for this teacher/administrator.']);
            }

            $data['name'] = trim($owner->name)."'s Timetable";
            $data['my_class_id'] = null;
        }

        $this->tt->updateRecord($id, $data);

        return Qs::jsonUpdateOk();
    }

    public function delete_record($ttr_id)
    {
        $this->authorizeEdit($this->tt->findRecord($ttr_id));
        $this->tt->deleteRecord($ttr_id);
        return back()->with('flash_success', __('msg.delete_ok'));
    }

    public function save_layout(Request $request, $ttr_id)
    {
        $record = $this->tt->findRecord($ttr_id);
        $this->authorizeEdit($record);
        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:100',
                Rule::unique('time_table_records', 'name')->ignore($record->id),
            ],
            'rows' => 'present|array|max:200',
            'rows.*.id' => 'required|string',
            'rows.*.type' => 'required|in:subject,blank',
            'rows.*.time_start_hour' => 'nullable|integer|between:0,23',
            'rows.*.time_start_minute' => ['nullable', 'regex:/^(0[0-9]|[1-5][0-9])$/'],
            'rows.*.time_end_hour' => 'nullable|integer|between:0,23',
            'rows.*.time_end_minute' => ['nullable', 'regex:/^(0[0-9]|[1-5][0-9])$/'],
            'rows.*.monday_subject' => 'nullable|string',
            'rows.*.monday_teacher_id' => 'nullable|integer|exists:users,id',
            'rows.*.tuesday_subject' => 'nullable|string',
            'rows.*.tuesday_teacher_id' => 'nullable|integer|exists:users,id',
            'rows.*.wednesday_subject' => 'nullable|string',
            'rows.*.wednesday_teacher_id' => 'nullable|integer|exists:users,id',
            'rows.*.thursday_subject' => 'nullable|string',
            'rows.*.thursday_teacher_id' => 'nullable|integer|exists:users,id',
            'rows.*.friday_subject' => 'nullable|string',
            'rows.*.friday_teacher_id' => 'nullable|integer|exists:users,id',
            'rows.*.blank_subject' => 'nullable|string',
            'rows.*.blank_teacher_id' => 'nullable|integer|exists:users,id',
        ]);

        $normalizedRows = collect($validated['rows'])->map(function ($row) {
            unset($row['time_start_meridian'], $row['time_end_meridian']);
            return $row;
        })->values()->all();

        $record = $this->tt->findRecord($ttr_id);
        $record->name = trim($validated['title']);
        $record->timetable_layout = json_encode($normalizedRows);
        $record->save();

        return response()->json([
            'ok' => true,
            'msg' => 'Timetable progress saved successfully.',
        ]);
    }

    protected function defaultLayoutRows()
    {
        return [
            [
                'id' => uniqid('row_', true),
                'type' => 'blank',
                'time_start_hour' => '',
                'time_start_minute' => '',
                'time_end_hour' => '',
                'time_end_minute' => '',
                'monday_subject' => '',
                'monday_teacher_id' => null,
                'tuesday_subject' => '',
                'tuesday_teacher_id' => null,
                'wednesday_subject' => '',
                'wednesday_teacher_id' => null,
                'thursday_subject' => '',
                'thursday_teacher_id' => null,
                'friday_subject' => '',
                'friday_teacher_id' => null,
                'blank_subject' => '',
                'blank_teacher_id' => null,
            ],
        ];
    }

    protected function layoutRowsForRecord(TimeTableRecord $record)
    {
        $savedRows = $record->timetable_layout ? json_decode($record->timetable_layout, true) : [];
        if(is_array($savedRows) && count($savedRows)) {
            return $savedRows;
        }

        $timeSlots = $this->tt->getTimeSlotByTTR($record->id);
        if($timeSlots->isEmpty()) {
            return [];
        }

        $entries = $this->tt->getTimeTable(['ttr_id' => $record->id]);
        return $timeSlots->map(function ($timeSlot) use ($entries) {
            $start = $this->parseTimetableTime($timeSlot->time_from);
            $end = $this->parseTimetableTime($timeSlot->time_to);
            $row = [
                'id' => 'legacy_'.$timeSlot->id,
                'type' => 'subject',
                'time_start_hour' => $start['hour'],
                'time_start_minute' => $start['minute'],
                'time_end_hour' => $end['hour'],
                'time_end_minute' => $end['minute'],
                'blank_subject' => '',
                'blank_teacher_id' => null,
            ];

            foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
                $entry = $entries->first(function ($timetableEntry) use ($timeSlot, $day) {
                    return (int) $timetableEntry->ts_id === (int) $timeSlot->id
                        && strtolower((string) $timetableEntry->day) === $day;
                });
                $row[$day.'_subject'] = optional(optional($entry)->subject)->name ?? '';
                $row[$day.'_teacher_id'] = null;
            }

            return $row;
        })->values()->all();
    }

    protected function parseTimetableTime($time)
    {
        $timestamp = strtotime('1970-01-01 '.(string) $time);
        return $timestamp === false
            ? ['hour' => '', 'minute' => '']
            : ['hour' => (int) date('H', $timestamp), 'minute' => date('i', $timestamp)];
    }

    protected function buildLegacyLayoutRows(array $rows, $teacherLookup)
    {
        return collect($rows)->map(function ($row) use ($teacherLookup) {
            $startHour = trim((string)($row['time_start_hour'] ?? ''));
            $startMinute = trim((string)($row['time_start_minute'] ?? ''));
            $endHour = trim((string)($row['time_end_hour'] ?? ''));
            $endMinute = trim((string)($row['time_end_minute'] ?? ''));

            $startTime = ($startHour !== '' && $startMinute !== '')
                ? str_pad($startHour, 2, '0', STR_PAD_LEFT).':'.$startMinute
                : '';
            $endTime = ($endHour !== '' && $endMinute !== '')
                ? str_pad($endHour, 2, '0', STR_PAD_LEFT).':'.$endMinute
                : '';

            $row['display_time'] = ($startTime || $endTime)
                ? trim($startTime.' - '.$endTime)
                : '-';

            $row['monday_teacher_name'] = $teacherLookup[$row['monday_teacher_id'] ?? null] ?? '';
            $row['tuesday_teacher_name'] = $teacherLookup[$row['tuesday_teacher_id'] ?? null] ?? '';
            $row['wednesday_teacher_name'] = $teacherLookup[$row['wednesday_teacher_id'] ?? null] ?? '';
            $row['thursday_teacher_name'] = $teacherLookup[$row['thursday_teacher_id'] ?? null] ?? '';
            $row['friday_teacher_name'] = $teacherLookup[$row['friday_teacher_id'] ?? null] ?? '';

            return $row;
        })->values()->all();
    }

    protected function canManageTimetables()
    {
        return in_array(Auth::user()->user_type, ['admin', 'administrator', 'teacher']);
    }

    protected function availableTimetableTitle($baseTitle, $ignoreId = null)
    {
        $title = $baseTitle;
        $suffix = 2;

        while (TimeTableRecord::where('name', $title)
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '<>', $ignoreId);
            })->exists()) {
            $title = $baseTitle.' ('.$suffix.')';
            $suffix++;
        }

        return $title;
    }

    protected function authorizeEdit(TimeTableRecord $record)
    {
        $user = Auth::user();
        $canEdit = in_array($user->user_type, ['admin', 'administrator'])
            || ($user->user_type === 'teacher' && (
                $record->target_type === 'class'
                || ((int) $record->owner_user_id === (int) $user->id)
            ));

        abort_unless($canEdit, 403);
    }

    protected function authorizeView(TimeTableRecord $record)
    {
        $user = Auth::user();
        if(in_array($user->user_type, ['admin', 'administrator', 'teacher'])) {
            return;
        }

        if($user->user_type === 'student') {
            $canViewClass = $record->target_type === 'class'
                && $this->classIdsForStudent($user->id)->contains((int) $record->my_class_id);
            $canViewStaff = $record->target_type === 'teacher'
                && in_array(optional($record->owner)->user_type, ['teacher', 'admin', 'administrator']);
            abort_unless($canViewClass || $canViewStaff, 403);
            return;
        }

        if($user->user_type === 'parent') {
            $childIds = $this->childrenForParent($user->id)->pluck('id');
            $classIds = $childIds->flatMap(function ($childId) {
                return $this->classIdsForStudent($childId);
            })->unique();

            abort_unless($record->target_type === 'class'
                && $classIds->contains((int) $record->my_class_id), 403);
            return;
        }

        abort(403);
    }

    protected function classIdsForStudent($studentId)
    {
        return StudentRecord::where('user_id', $studentId)
            ->where('grad', 0)
            ->whereNotNull('my_class_id')
            ->pluck('my_class_id')
            ->merge(StudentEnrollment::where('enrolled_student_user_id', $studentId)
                ->whereNotNull('my_class_id')
                ->pluck('my_class_id'))
            ->map(function ($classId) {
                return (int) $classId;
            })->unique()->values();
    }

    protected function childrenForParent($parentId)
    {
        $childIds = User::findOrFail($parentId)->children()->pluck('users.id')
            ->merge(StudentRecord::where('my_parent_id', $parentId)->pluck('user_id'));

        $enrollments = StudentEnrollment::whereHas('accounts', function ($query) use ($parentId) {
            $query->where('users.id', $parentId)
                ->where('student_enrollment_accounts.account_type', 'Parent');
        })->get(['user_id', 'enrolled_student_user_id']);

        $childIds = $childIds->merge($enrollments->pluck('user_id'))
            ->merge($enrollments->pluck('enrolled_student_user_id'))
            ->filter()->unique()->values();

        return User::whereIn('id', $childIds)->where('user_type', 'student')->orderBy('name')->get();
    }
}
