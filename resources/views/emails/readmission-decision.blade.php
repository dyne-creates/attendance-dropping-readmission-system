<x-mail::message>
# {{ $recipientRole === 'faculty' ? 'Readmission decision' : 'Your readmission request' }}

Hello {{ $recipientRole === 'faculty' ? ($readmission->faculty_name ?? 'Faculty member') : trim(($readmission->first_name ?? '').' '.($readmission->last_name ?? '')) }},

OSA has processed the readmission request for **{{ $readmission->first_name }} {{ $readmission->last_name }}**
({{ $readmission->subject_code ?? 'course' }}{{ ! empty($readmission->course_name) ? ' — '.$readmission->course_name : '' }}).

**Decision: {{ $decisionLabel }}**

@if ($outcome === 'approved')
The student has been readmitted to this course.
@elseif ($outcome === 'excused')
The student has been readmitted to this course. Absences from
{{ \Carbon\Carbon::parse($readmission->excused_from)->format('F j, Y') }} through
{{ \Carbon\Carbon::parse($readmission->excused_through)->format('F j, Y') }} were excused.
{{ number_format((float) $readmission->excused_hours_deducted, 2) }} absence hours were deducted.
@elseif ($outcome === 'not_excused')
The request was not excused. The student remains inactive for this course and may submit a corrected request.
@else
The request was rejected. The student remains inactive and cannot enroll in this course during the term.
@endif

@if (! empty($readmission->rejection_reason))
**OSA feedback:** {{ $readmission->rejection_reason }}
@endif

Thanks,<br>
Office of Student Affairs
</x-mail::message>
