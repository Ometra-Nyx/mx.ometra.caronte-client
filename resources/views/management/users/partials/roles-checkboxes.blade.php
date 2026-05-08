<div class="mb-3">
    @php($availableRoles = $roles ?? $configured_roles ?? [])
    <label class="form-label fw-semibold">Roles</label>
    <div class="row">
        @forelse ($availableRoles as $role)
            <div class="col-12 col-md-6">
                <div class="form-check">
                    <input class="form-check-input user-role-checkbox" type="checkbox" name="roles[]"
                        value="{{ $role['uri_applicationRole'] }}" id="role-{{ $idPrefix ?? 'role' }}-{{ $loop->index }}"
                        data-role-uri="{{ $role['uri_applicationRole'] }}">
                    <label class="form-check-label" for="role-{{ $idPrefix ?? 'role' }}-{{ $loop->index }}">
                        {{ $role['name'] }}
                        <small class="text-muted">{{ $role['description'] ?? '' }}</small>
                    </label>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-muted">No roles available.</div>
            </div>
        @endforelse
    </div>
</div>
