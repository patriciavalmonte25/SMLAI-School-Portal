<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Models\DocumentRequest as StudentDocumentRequest;
use App\Models\StudentEnrollment;
use App\Models\StudentRecord;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DocumentRequestController extends Controller
{
    public function index()
    {
        $actor = Auth::user();
        abort_unless(in_array($actor->user_type, ['parent', 'student']), 403);

        if ($actor->user_type === 'student') {
            $students = collect([$actor]);
            $requests = StudentDocumentRequest::with(['student', 'requester'])
                ->where('student_user_id', $actor->id)
                ->latest()
                ->get();
        } else {
            $students = $this->linkedStudents($actor);
            $studentIds = $students->pluck('id');
            $requests = StudentDocumentRequest::with(['student', 'requester'])
                ->where(function ($query) use ($actor, $studentIds) {
                    $query->where('requester_user_id', $actor->id);
                    if ($studentIds->isNotEmpty()) {
                        $query->orWhereIn('student_user_id', $studentIds);
                    }
                })
                ->latest()
                ->get();
        }

        return view('pages.document_requests.index', compact('actor', 'students', 'requests'));
    }

    public function store(Request $request)
    {
        $actor = Auth::user();
        abort_unless(in_array($actor->user_type, ['parent', 'student']), 403);

        $data = $request->validate([
            'student_user_id' => $actor->user_type === 'student'
                ? 'required|integer|exists:users,id'
                : 'nullable|integer|exists:users,id',
            'student_name' => 'required|string|max:150',
            'school_year_last_attended' => 'required|string|max:50',
            'grade_section' => 'required|string|max:150',
            'request_items' => 'required|array|min:1',
            'request_items.*' => 'required|string|in:form_137,form_138,moral,transfer,graduate,other',
            'form_137_grade' => 'nullable|string|max:50',
            'form_138_grade' => 'nullable|string|max:50',
            'other_request' => 'nullable|string|max:250',
            'purpose' => 'required|string|max:2000',
            'information_name' => 'required|string|max:150',
            'date_of_birth' => 'required|date_format:m/d/Y',
            'place_of_birth' => 'required|string|max:200',
            'contact_messenger' => 'required|string|max:100',
            'father_name' => 'required|string|max:150',
            'father_occupation' => 'required|string|max:150',
            'mother_name' => 'required|string|max:150',
            'mother_occupation' => 'required|string|max:150',
            'parents_address' => 'required|string|max:500',
        ]);

        $student = !empty($data['student_user_id'])
            ? User::where('user_type', 'student')->findOrFail($data['student_user_id'])
            : null;
        if ($actor->user_type === 'student') {
            abort_unless((int) $student->id === (int) $actor->id, 403);
        } elseif ($student) {
            abort_unless($this->isLinkedParent($actor, $student->id), 403);
        }

        $extraErrors = [];
        if (in_array('form_137', $data['request_items'], true) && empty($data['form_137_grade'])) {
            $extraErrors['form_137_grade'] = 'Enter the grade for Form 137 / SF 10.';
        }
        if (in_array('form_138', $data['request_items'], true) && empty($data['form_138_grade'])) {
            $extraErrors['form_138_grade'] = 'Enter the grade for Form 138 / Report Card / SF.';
        }
        if (in_array('other', $data['request_items'], true) && empty($data['other_request'])) {
            $extraErrors['other_request'] = 'Specify the other document requested.';
        }
        if ($extraErrors) {
            throw ValidationException::withMessages($extraErrors);
        }

        $data['requester_user_id'] = $actor->id;
        $data['date_of_birth'] = Carbon::createFromFormat('m/d/Y', $data['date_of_birth'])->format('Y-m-d');
        $data['status'] = 'pending';
        unset($data['_token']);
        StudentDocumentRequest::create($data);

        return redirect()->to(route('document_requests.index') . '#sent-requests')
            ->with('flash_success', 'Document request submitted.');
    }

    public function accountantIndex()
    {
        $this->ensureAccountant();
        $incomingRequests = StudentDocumentRequest::with(['student', 'requester'])
            ->where('status', 'pending')->latest()->get();
        $approvedRequests = StudentDocumentRequest::with(['student', 'requester', 'reviewedBy'])
            ->whereIn('status', ['approved', 'collected'])->latest()->get();
        $rejectedRequests = StudentDocumentRequest::with(['student', 'requester', 'reviewedBy'])
            ->where('status', 'rejected')->latest()->get();

        return view('pages.accountant.document_requests', compact('incomingRequests', 'approvedRequests', 'rejectedRequests'));
    }

    public function show($request_id)
    {
        $this->ensureAccountant();
        $documentRequest = StudentDocumentRequest::with(['student', 'requester'])->findOrFail($request_id);
        abort_unless(in_array($documentRequest->status, ['pending', 'approved', 'collected'], true), 404);

        return view('pages.accountant.document_request_review', compact('documentRequest'));
    }

    public function approve(Request $request, $request_id)
    {
        $this->ensureAccountant();
        $data = $request->validate([
            'release_date' => 'required|date_format:m/d/Y',
            'fee' => 'required|string|max:50',
        ]);
        $documentRequest = StudentDocumentRequest::where('status', 'pending')->findOrFail($request_id);
        $documentRequest->release_date = Carbon::createFromFormat('m/d/Y', $data['release_date'])->format('Y-m-d');
        $documentRequest->fee = $data['fee'];
        $documentRequest->reviewed_by = Auth::id();
        $documentRequest->status = 'approved';
        $documentRequest->save();

        return redirect()->to(route('accountant.document_requests.index') . '#approved-requests')
            ->with('flash_success', 'Document request approved.');
    }

    public function reject(Request $request, $request_id)
    {
        $this->ensureAccountant();
        $data = $request->validate([
            'rejection_reason' => 'required|string|min:5|max:2000',
        ]);
        $documentRequest = StudentDocumentRequest::where('status', 'pending')->findOrFail($request_id);
        $documentRequest->rejection_reason = $data['rejection_reason'];
        $documentRequest->reviewed_by = Auth::id();
        $documentRequest->status = 'rejected';
        $documentRequest->save();

        return redirect()->to(route('accountant.document_requests.index') . '#rejected-requests')
            ->with('flash_success', 'Document request rejected.');
    }

    public function collect(Request $request, $request_id)
    {
        $this->ensureAccountant();
        $data = $request->validate([
            'request_received_by' => 'required|string|max:150',
            'request_prepared_by' => 'required|string|max:150',
            'request_released_by' => 'required|string|max:150',
        ]);
        $documentRequest = StudentDocumentRequest::where('status', 'approved')->findOrFail($request_id);
        $documentRequest->request_received_by = $data['request_received_by'];
        $documentRequest->request_prepared_by = $data['request_prepared_by'];
        $documentRequest->request_released_by = $data['request_released_by'];
        $documentRequest->collection_date = now();
        $documentRequest->status = 'collected';
        $documentRequest->save();

        return redirect()->to(route('accountant.document_requests.index') . '#approved-requests')
            ->with('flash_success', 'Document request marked collected.');
    }

    private function linkedStudents(User $parent)
    {
        $studentIds = $parent->children()->pluck('users.id')
            ->merge(StudentRecord::where('my_parent_id', $parent->id)->pluck('user_id'));

        $enrollments = StudentEnrollment::where(function ($query) use ($parent) {
            $query->where('user_id', $parent->id)
                ->orWhereHas('accounts', function ($accountQuery) use ($parent) {
                    $accountQuery->where('users.id', $parent->id)
                        ->where('student_enrollment_accounts.account_type', 'Parent');
                });
        })->get();
        $studentIds = $studentIds->merge($enrollments->pluck('enrolled_student_user_id'))
            ->filter()->unique()->values();

        return User::whereIn('id', $studentIds)
            ->where('user_type', 'student')
            ->with(['student_record.my_class', 'student_enrollment.my_class'])
            ->orderBy('name')
            ->get();
    }

    private function isLinkedParent(User $parent, $studentId)
    {
        if ($parent->children()->where('users.id', $studentId)->exists()
            || StudentRecord::where('user_id', $studentId)->where('my_parent_id', $parent->id)->exists()) {
            return true;
        }

        return StudentEnrollment::where(function ($query) use ($studentId) {
            $query->where('enrolled_student_user_id', $studentId)
                ->orWhere('user_id', $studentId);
        })->whereHas('accounts', function ($query) use ($parent) {
            $query->where('users.id', $parent->id)
                ->where('student_enrollment_accounts.account_type', 'Parent');
        })->exists();
    }

    private function ensureAccountant()
    {
        abort_unless(Auth::user() && Auth::user()->user_type === 'accountant', 403);
    }
}