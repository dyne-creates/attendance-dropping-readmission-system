<div class="fixed inset-0 z-[80] flex items-center justify-center overflow-y-auto bg-gray-950/70 p-4"
     role="presentation">
    <section class="modal-panel relative w-full max-w-2xl p-6 shadow-2xl"
             role="dialog" aria-modal="true" aria-labelledby="policy-agreement-heading"
             aria-describedby="policy-agreement-copy">
        <div class="space-y-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-600">Required before continuing</p>
                <h2 id="policy-agreement-heading" class="mt-1 text-xl font-semibold">Attendance and readmission policy</h2>
            </div>

            <div id="policy-agreement-copy" class="max-h-[55vh] space-y-3 overflow-y-auto pr-2 text-sm leading-6 text-gray-700 dark:text-gray-300">
                <p>
                    In line with the University's Vision and Mission of a relevant and responsive education, timely
                    and accurate monitoring of student attendance can help improve the University's academic process.
                    The Office of Student Affairs operates the Online Attendance Monitoring and Readmission System to
                    support a timely, proper, and efficient response to factors affecting student attendance and
                    performance.
                </p>
                <p>
                    The University may coordinate with parents or guardians by notifying them of student absences and
                    providing an opportunity to share any concerns. Attendance monitoring is intended to encourage
                    responsibility and help students get the full benefit of their education.
                </p>
                <p>
                    Personal information will be handled in accordance with applicable data protection laws, securely
                    stored, and accessed only by authorized personnel involved in the process. It will not be
                    disclosed to third parties unless required by law or with explicit consent. For privacy concerns,
                    contact the Office of Student Affairs.
                </p>
                @if ($user->role === 'student')
                    <p>
                        By agreeing, you certify that information you provide is true and correct and understand that
                        false statements or concealment may lead to disciplinary sanctions. You consent to the
                        collection, processing, and storage of your data under RA 10173 (Data Privacy Act of 2012),
                        and authorize the Office of Student Affairs to use this information to notify your parents or
                        guardians about your absences and attendance.
                    </p>
                @else
                    <p>
                        By agreeing, you will use student information only for your assigned responsibilities and
                        handle it confidentially.
                    </p>
                @endif
            </div>

            <form method="POST" action="{{ route('policy.accept') }}" class="space-y-4">
                @csrf
                <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                    <input type="checkbox" name="accepted" value="1" required
                           class="mt-1 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                    <span>I have read and agree to this policy and the responsible use of attendance and readmission information.</span>
                </label>
                @error('accepted')
                    <p class="text-sm text-rose-600" role="alert">{{ $message }}</p>
                @enderror
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Agree and continue</button>
                </div>
            </form>
        </div>
    </section>
</div>
