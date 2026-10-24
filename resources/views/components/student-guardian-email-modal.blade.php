<div class="fixed inset-0 z-[80] flex items-center justify-center overflow-y-auto bg-gray-950/70 p-4"
     role="presentation">
    <section class="modal-panel relative w-full max-w-lg p-6 shadow-2xl"
             role="dialog" aria-modal="true" aria-labelledby="guardian-email-heading"
             aria-describedby="guardian-email-copy">
        <div class="space-y-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-600">Required to continue</p>
                <h2 id="guardian-email-heading" class="mt-1 text-xl font-semibold">Add a parent or guardian email</h2>
            </div>

            <p id="guardian-email-copy" class="text-sm leading-6 muted">
                This address will be saved to your student profile for parent or guardian contact. Saving it does not
                verify who owns the address or confirm your relationship.
            </p>

            <form method="POST" action="{{ route('student.guardian-email.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="guardian-email" class="field-label">Parent / guardian email</label>
                    <input id="guardian-email" class="field mt-1.5 w-full" type="email" name="email"
                           value="{{ old('email', $guardian?->email) }}" maxlength="255" required autofocus
                           autocomplete="email" aria-describedby="guardian-email-error">
                    @error('email')
                        <p id="guardian-email-error" class="field-error mt-1" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Save and continue</button>
                </div>
            </form>
        </div>
    </section>
</div>
