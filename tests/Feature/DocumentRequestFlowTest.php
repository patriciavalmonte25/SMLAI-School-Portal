<?php

namespace Tests\Feature;

use App\Models\DocumentRequest;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentRecord;
use App\User;
use Tests\TestCase;

class DocumentRequestFlowTest extends TestCase
{
    private function makeUser($type, $name)
    {
        return User::create([
            'name' => $name,
            'username' => $type . '-' . uniqid(),
            'email' => $type . '-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => $type,
            'code' => strtoupper($type) . uniqid(),
        ]);
    }

    private function requestData($student)
    {
        return [
            'student_user_id' => $student->id,
            'student_name' => $student->name,
            'school_year_last_attended' => '2024-2025',
            'grade_section' => 'Grade 8 - A',
            'request_items' => ['form_137', 'form_138', 'other'],
            'form_137_grade' => 'Grade 8',
            'form_138_grade' => 'Grade 8',
            'other_request' => 'Enrollment certification',
            'purpose' => 'Transfer to another school',
            'information_name' => $student->name,
            'date_of_birth' => '01/02/2012',
            'place_of_birth' => 'Sta. Maria',
            'contact_messenger' => '09170000000',
            'father_name' => 'Juan Student',
            'father_occupation' => 'Farmer',
            'mother_name' => 'Maria Student',
            'mother_occupation' => 'Teacher',
            'parents_address' => 'Sta. Maria, Laguna',
        ];
    }

    public function test_parent_student_document_request_approval_rejection_and_collection_flow()
    {
        $parent = $this->makeUser('parent', 'Parent Example');
        $student = $this->makeUser('student', 'Student Example');
        $accountant = $this->makeUser('accountant', 'Accountant Example');
        $schoolClass = MyClass::create(['name' => 'Grade 8 ' . uniqid(), 'class_type_id' => null]);
        $section = Section::create(['name' => 'A ' . uniqid(), 'my_class_id' => $schoolClass->id, 'active' => 1]);
        StudentRecord::create([
            'user_id' => $student->id,
            'my_class_id' => $schoolClass->id,
            'section_id' => $section->id,
            'my_parent_id' => $parent->id,
            'session' => '2026',
            'grad' => 0,
        ]);
        $parent->children()->syncWithoutDetaching([$student->id]);

        $this->actingAs($parent)
            ->get('http://localhost/document-requests')
            ->assertOk()
            ->assertSee('Request Documents')
            ->assertSee('Sent Requests')
            ->assertSee('MM/DD/YYYY')
            ->assertSee('id="request-student" name="student_user_id" class="form-control"', false)
            ->assertDontSee('<span class="text-danger">*</span>', false)
            ->assertSee('name="student_name" type="text" class="form-control" value="" required', false)
            ->assertSee('<h6 class="font-weight-semibold mb-2">A. Requesting for: (please check)</h6>', false)
            ->assertSee('<h6 class="font-weight-semibold mb-2">B. Purpose', false)
            ->assertSee('<h6 class="font-weight-semibold mb-2">C. Information</h6>', false)
            ->assertSee('fieldset class="border rounded px-3 py-1 mb-3"', false)
            ->assertSee('.document-request-option {', false)
            ->assertSee('flex: 0 0 340px;', false)
            ->assertSee('.document-request-option-fields input {', false)
            ->assertSee('width: 90px;', false);

        $incomplete = $this->requestData($student);
        unset($incomplete['father_occupation']);
        $requestsBeforeInvalidSubmission = DocumentRequest::count();
        $this->actingAs($parent)
            ->from('http://localhost/document-requests')
            ->post('http://localhost/document-requests', $incomplete)
            ->assertSessionHasErrors('father_occupation');
        $this->assertSame($requestsBeforeInvalidSubmission, DocumentRequest::count());

        $this->actingAs($parent)
            ->post('http://localhost/document-requests', $this->requestData($student))
            ->assertRedirect(route('document_requests.index') . '#sent-requests');
        $approvedRequest = DocumentRequest::latest('id')->firstOrFail();
        $this->assertSame('pending', $approvedRequest->status);
        $this->assertSame('2012-01-02', $approvedRequest->date_of_birth->format('Y-m-d'));

        $parentWithoutSelection = $this->requestData($student);
        unset($parentWithoutSelection['student_user_id']);
        $parentWithoutSelection['school_year_last_attended'] = 'Parent without selected student ' . uniqid();
        $this->actingAs($parent)
            ->post('http://localhost/document-requests', $parentWithoutSelection)
            ->assertRedirect(route('document_requests.index') . '#sent-requests');
        $unassignedRequest = DocumentRequest::where('school_year_last_attended', $parentWithoutSelection['school_year_last_attended'])->firstOrFail();
        $this->assertNull($unassignedRequest->student_user_id);
        $this->assertSame($parent->id, $unassignedRequest->requester_user_id);

        $studentSubmission = $this->requestData($student);
        $studentSubmission['school_year_last_attended'] = 'Student submitted ' . uniqid();
        $this->actingAs($student)
            ->post('http://localhost/document-requests', $studentSubmission)
            ->assertRedirect(route('document_requests.index') . '#sent-requests');
        $studentRequest = DocumentRequest::where('school_year_last_attended', $studentSubmission['school_year_last_attended'])->firstOrFail();
        $this->assertSame($student->id, $studentRequest->requester_user_id);

        $this->actingAs($student)
            ->get('http://localhost/document-requests')
            ->assertOk()
            ->assertSee('Request Sent')
            ->assertSee('A. Requesting for: (please check)')
            ->assertSee('B. Purpose')
            ->assertSee('C. Information')
            ->assertSee('Form 137 / SF 10')
            ->assertSee('e. Father')
            ->assertSee('f. Address of Parents')
            ->assertSee('class="card border-danger mb-3"', false);

        $this->actingAs($accountant)
            ->get('http://localhost/accountant/document-requests')
            ->assertOk()
            ->assertSee('Incoming Requests')
            ->assertSee('Approved Requests')
            ->assertSee('Rejected Requests')
            ->assertSee('View');

        $reviewUrl = 'http://localhost/accountant/document-requests/' . $approvedRequest->id;
        $this->actingAs($accountant)
            ->get($reviewUrl)
            ->assertOk()
            ->assertSee('Approve')
            ->assertSee('Reject')
            ->assertSee('Form 137 / SF 10')
            ->assertSee('Purpose');

        $this->actingAs($accountant)
            ->from($reviewUrl)
            ->post($reviewUrl . '/approve', ['release_date' => '', 'fee' => ''])
            ->assertSessionHasErrors(['release_date', 'fee']);
        $this->assertSame('pending', $approvedRequest->fresh()->status);

        $this->actingAs($accountant)
            ->post($reviewUrl . '/approve', ['release_date' => '10/20/2026', 'fee' => '25.00'])
            ->assertRedirect(route('accountant.document_requests.index') . '#approved-requests');
        $approvedRequest->refresh();
        $this->assertSame('approved', $approvedRequest->status);
        $this->assertSame('2026-10-20', $approvedRequest->release_date->format('Y-m-d'));
        $this->actingAs($accountant)
            ->get('http://localhost/accountant/document-requests')
            ->assertOk()
            ->assertSee('Mark Collected')
            ->assertSee('collection-confirmation-modal', false)
            ->assertSee('Confirm Collection');

        $this->actingAs($parent)
            ->get('http://localhost/document-requests#sent-requests')
            ->assertOk()
            ->assertSee('class="card border-primary mb-3"', false)
            ->assertSee('Approved. Request may be collected on 10/20/2026. Fee: 25.00.');

        $collectUrl = 'http://localhost/accountant/document-requests/' . $approvedRequest->id . '/collect';
        $this->actingAs($accountant)
            ->from('http://localhost/accountant/document-requests')
            ->post($collectUrl, ['request_received_by' => 'Parent'])
            ->assertSessionHasErrors(['request_prepared_by', 'request_released_by']);
        $this->assertSame('approved', $approvedRequest->fresh()->status);

        $collectionSubmittedAt = now()->startOfSecond();
        $this->actingAs($accountant)
            ->post($collectUrl, [
                'request_received_by' => 'Parent Example',
                'request_prepared_by' => 'Staff One',
                'request_released_by' => 'Staff Two',
            ])
            ->assertRedirect(route('accountant.document_requests.index') . '#approved-requests');
        $approvedRequest->refresh();
        $this->assertSame('collected', $approvedRequest->status);
        $this->assertNotNull($approvedRequest->collection_date);
        $this->assertTrue($approvedRequest->collection_date->greaterThanOrEqualTo($collectionSubmittedAt));

        $collectedListResponse = $this->actingAs($accountant)
            ->get('http://localhost/accountant/document-requests#approved-requests')
            ->assertOk()
            ->assertSee('Collected')
            ->assertSee(route('accountant.document_requests.show', $approvedRequest->id), false);
        $collectedReviewResponse = $this->actingAs($accountant)
            ->get(route('accountant.document_requests.show', $approvedRequest->id))
            ->assertOk()
            ->assertSee('Collection Details')
            ->assertSee('Request Received by: Parent Example')
            ->assertSee($approvedRequest->collection_date->format('m/d/Y h:i A'));
        $this->assertLessThan(
            strpos($collectedReviewResponse->getContent(), '<label>NAME</label>'),
            strpos($collectedReviewResponse->getContent(), 'Collection Details')
        );

        $this->actingAs($parent)
            ->get('http://localhost/document-requests#sent-requests')
            ->assertOk()
            ->assertDontSee('School Year: 2024-2025; Grade & Section:', false)
            ->assertSee('class="card border-success mb-3"', false)
            ->assertSee('Approved. Request may be collected on 10/20/2026. Fee: 25.00.')
            ->assertSee('Collected on ' . $approvedRequest->collection_date->format('m/d/Y h:i A') . '. Received by Parent Example; prepared by Staff One; released by Staff Two.');

        $rejectedData = $this->requestData($student);
        $rejectedData['school_year_last_attended'] = '2023-2024';
        $this->actingAs($parent)
            ->post('http://localhost/document-requests', $rejectedData)
            ->assertRedirect(route('document_requests.index') . '#sent-requests');
        $rejectedRequest = DocumentRequest::latest('id')->firstOrFail();

        $rejectUrl = 'http://localhost/accountant/document-requests/' . $rejectedRequest->id . '/reject';
        $this->actingAs($accountant)
            ->from('http://localhost/accountant/document-requests/' . $rejectedRequest->id)
            ->post($rejectUrl, ['rejection_reason' => 'Incomplete purpose details'])
            ->assertRedirect(route('accountant.document_requests.index') . '#rejected-requests');
        $this->assertSame('rejected', $rejectedRequest->fresh()->status);
        $this->actingAs($parent)
            ->get('http://localhost/document-requests#sent-requests')
            ->assertOk()
            ->assertSee('class="card border-dark mb-3"', false)
            ->assertSee('Rejected. Reason: Incomplete purpose details');
    }
}
