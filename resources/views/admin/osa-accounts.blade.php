<x-app-layout>
    <x-slot name="header">
        <x-page-header
            eyebrow="Administration"
            title="OSA Accounts"
            description="Create and maintain the staff accounts that can use the OSA workspace."
        />
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-flash-messages />

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="card overflow-hidden xl:col-span-1">
                <div class="card-header">
                    <h2 class="font-semibold">Add OSA account</h2>
                    <p class="mt-1 text-sm muted">The account will have access to readmissions, records, and reports.</p>
                </div>
                <form method="POST" action="{{ route('admin.osa-accounts.store') }}" class="card-body space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="field-label">Full name <span aria-hidden="true">*</span></label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="150" class="field mt-1" required>
                        @error('name')<p class="field-error mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="field-label">University email <span aria-hidden="true">*</span></label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" class="field mt-1" required>
                        @error('email')<p class="field-error mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="field-label">Temporary password <span aria-hidden="true">*</span></label>
                        <input id="password" name="password" type="password" class="field mt-1" minlength="8" required>
                        @error('password')<p class="field-error mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="field-label">Confirm temporary password <span aria-hidden="true">*</span></label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="field mt-1" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Create OSA account</button>
                </form>
            </section>

            <section class="card overflow-hidden xl:col-span-2">
                <div class="card-header flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">OSA staff directory</h2>
                        <p class="mt-1 text-sm muted">{{ $accounts->count() }} active OSA {{ Str::plural('account', $accounts->count()) }}.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Staff member</th>
                                <th scope="col">Email</th>
                                <th scope="col">OSA profile</th>
                                <th scope="col">Created</th>
                                <th scope="col" class="text-right">Manage</th>
                            </tr>
                        </thead>
                        <tbody x-data="{ editingAccount: null }">
                            @forelse ($accounts as $account)
                                <tr>
                                    <td class="font-medium">{{ $account->name }}</td>
                                    <td>{{ $account->email }}</td>
                                    <td>{{ $account->osaStaff ? 'Connected' : 'Will be created on update' }}</td>
                                    <td>{{ $account->created_at?->format('M d, Y') ?? '—' }}</td>
                                    <td class="text-right">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="editingAccount = editingAccount === {{ $account->user_id }} ? null : {{ $account->user_id }}" x-text="editingAccount === {{ $account->user_id }} ? 'Cancel' : 'Edit'"></button>
                                    </td>
                                </tr>
                                <tr x-show="editingAccount === {{ $account->user_id }}" x-cloak>
                                    <td colspan="5" class="surface-alt">
                                        <form method="POST" action="{{ route('admin.osa-accounts.update', $account) }}" class="grid grid-cols-1 gap-3 p-2 md:grid-cols-2">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="field-label">Full name</label>
                                                <input name="name" type="text" value="{{ $account->name }}" class="field mt-1" required>
                                            </div>
                                            <div>
                                                <label class="field-label">University email</label>
                                                <input name="email" type="email" value="{{ $account->email }}" class="field mt-1" required>
                                            </div>
                                            <div>
                                                <label class="field-label">New password <span class="muted">(optional)</span></label>
                                                <input name="password" type="password" minlength="8" class="field mt-1">
                                            </div>
                                            <div>
                                                <label class="field-label">Confirm new password</label>
                                                <input name="password_confirmation" type="password" minlength="8" class="field mt-1">
                                            </div>
                                            <div class="md:col-span-2 flex justify-end">
                                                <button type="submit" class="btn btn-primary btn-sm">Save account</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-12 text-center muted">No OSA accounts have been created yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
