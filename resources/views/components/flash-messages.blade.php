@if (session('success'))
    <div class="alert alert-success mb-4" role="status">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger mb-4" role="alert">
        {{ session('error') }}
    </div>
@endif

@if (session('warning'))
    <div class="alert alert-warning mb-4" role="alert">
        {{ session('warning') }}
    </div>
@endif

@if (session('info'))
    <div class="alert alert-neutral mb-4" role="status">
        {{ session('info') }}
    </div>
@endif
