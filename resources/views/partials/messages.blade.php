@php
    $flashStatuses = [
        'success' => 'success',
        'warning' => 'warning',
        'error' => 'danger',
        'message' => 'info',
        'info' => 'info',
    ];
@endphp

@if ($errors->any())
    <div class="container py-3">
        <div class="alert alert-danger shadow-sm border-0">
            <div class="fw-semibold mb-2">Please review the following issues:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@foreach ($flashStatuses as $key => $type)
    @if (session()->has($key))
        <div class="container py-3">
            <div class="alert alert-{{ $type }} shadow-sm border-0">
                {{ session($key) }}
            </div>
        </div>
    @endif
@endforeach
