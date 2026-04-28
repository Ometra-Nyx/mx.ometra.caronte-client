@php
    $flashStatuses = [
        'success' => 'success',
        'warning' => 'warning',
        'message' => 'info',
        'info' => 'info',
    ];
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $hasErrors = $errors->any() || session()->has('error');
    $hasFlash = $hasErrors || collect(array_keys($flashStatuses))->contains(fn ($key) => session()->has($key));
@endphp

@if ($hasFlash)
<div class="caronte-flash">
@if ($hasErrors)
    @php
        $errorMessages = $errors->any() ? $errors->all() : [(string) session('error')];
    @endphp

    <div class="py-2">
        <div class="alert alert-danger shadow-sm border-0">
            @if (count($errorMessages) === 1)
                {{ $errorMessages[0] }}
            @else
                <div class="fw-semibold mb-2">Please review the following issues:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errorMessages as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif

@foreach ($flashStatuses as $key => $type)
    @if (! $hasErrors && session()->has($key))
        <div class="py-2">
            <div class="alert alert-{{ $type }} shadow-sm border-0">
                {{ session($key) }}
            </div>
        </div>
    @endif
@endforeach
</div>
@endif
