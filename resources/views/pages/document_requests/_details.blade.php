<style>
    .document-request-readonly-option {
        display: flex;
        align-items: center;
        gap: 16px;
        min-height: 44px;
        padding: 4px 0;
    }

    .document-request-readonly-option-label {
        display: flex;
        align-items: center;
        gap: 14px;
        flex: 0 0 340px;
        max-width: 100%;
        margin: 0;
    }

    .document-request-readonly-option-label input {
        flex: 0 0 auto;
        margin: 0;
    }

    .document-request-readonly-option-fields {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 0 0 190px;
        min-height: 34px;
    }

    @media (max-width: 575.98px) {
        .document-request-readonly-option {
            align-items: flex-start;
            flex-direction: column;
            gap: 4px;
        }

        .document-request-readonly-option-label,
        .document-request-readonly-option-fields {
            flex-basis: auto;
        }

        .document-request-readonly-option-fields {
            margin-left: 32px;
        }
    }
</style>

<div class="form-group">
    <label>NAME</label>
    <div class="form-control bg-light">{{ $documentRequest->student_name }}</div>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label>School Year Last Attended/Graduated</label>
        <div class="form-control bg-light">{{ $documentRequest->school_year_last_attended }}</div>
    </div>
    <div class="form-group col-md-6">
        <label>Grade &amp; Section</label>
        <div class="form-control bg-light">{{ $documentRequest->grade_section }}</div>
    </div>
</div>

<h6 class="font-weight-semibold mb-2">A. Requesting for: (please check)</h6>
<fieldset class="border rounded px-3 py-1 mb-3">
    @foreach([
        'form_137' => ['a. Form 137 / SF 10', $documentRequest->form_137_grade ? 'Grade: '.$documentRequest->form_137_grade : ''],
        'form_138' => ['b. Form 138 / Report Card / SF', $documentRequest->form_138_grade ? 'Grade: '.$documentRequest->form_138_grade : ''],
        'moral' => ['c. Certification of Good Moral Character', ''],
        'transfer' => ['d. Certification of Transfer', ''],
        'graduate' => ['e. Certification of Graduate', ''],
        'other' => ['f. Others (specify)', $documentRequest->other_request],
    ] as $key => $option)
        <div class="document-request-readonly-option">
            <label class="document-request-readonly-option-label">
                <input type="checkbox" disabled {{ in_array($key, $documentRequest->request_items ?? [], true) ? 'checked' : '' }}>
                <span>{{ $option[0] }}</span>
            </label>
            <div class="document-request-readonly-option-fields">{{ $option[1] }}</div>
        </div>
    @endforeach
</fieldset>

<h6 class="font-weight-semibold mb-2">B. Purpose</h6>
<div class="form-group">
    <div class="form-control bg-light" style="height:auto; min-height:38px; white-space:pre-wrap;">{{ $documentRequest->purpose }}</div>
</div>

<h6 class="font-weight-semibold mb-2">C. Information</h6>
<fieldset class="border rounded p-3 mb-3">
    <div class="form-group">
        <label>a. Name</label>
        <div class="form-control bg-light">{{ $documentRequest->information_name }}</div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>b. Date of Birth</label>
            <div class="form-control bg-light">{{ $documentRequest->date_of_birth->format('m/d/Y') }}</div>
        </div>
        <div class="form-group col-md-6">
            <label>c. Place of Birth</label>
            <div class="form-control bg-light">{{ $documentRequest->place_of_birth }}</div>
        </div>
    </div>
    <div class="form-group">
        <label>d. CP Number / Messenger</label>
        <div class="form-control bg-light">{{ $documentRequest->contact_messenger }}</div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>e. Father</label>
            <div class="form-control bg-light mb-2">{{ $documentRequest->father_name }}</div>
            <label>Occupation</label>
            <div class="form-control bg-light">{{ $documentRequest->father_occupation }}</div>
        </div>
        <div class="form-group col-md-6">
            <label>Mother</label>
            <div class="form-control bg-light mb-2">{{ $documentRequest->mother_name }}</div>
            <label>Occupation</label>
            <div class="form-control bg-light">{{ $documentRequest->mother_occupation }}</div>
        </div>
    </div>
    <div class="form-group mb-0">
        <label>f. Address of Parents</label>
        <div class="form-control bg-light" style="height:auto; min-height:38px; white-space:pre-wrap;">{{ $documentRequest->parents_address }}</div>
    </div>
</fieldset>