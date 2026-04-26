@extends('caronte::layouts.base')

@section('body_class', 'caronte-management-shell')

@section('content')
    <div class="container py-4 py-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <a href="{{ $routes['dashboard'] }}" class="caronte-backlink">&larr; Back to user management</a>
                <h1 class="caronte-title mt-2 mb-2">{{ $user['name'] ?? 'User' }}</h1>
                <p class="caronte-copy mb-0">{{ $user['email'] ?? '' }}</p>
            </div>

            <form method="POST" action="{{ $routes['delete'] }}" onsubmit="return confirm('Delete this user?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Delete user</button>
            </form>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <div class="caronte-card h-100">
                    <div class="caronte-card__header">
                        <h2>User profile</h2>
                        <p>Update the display name without leaving the current management context.</p>
                    </div>

                    <form method="POST" action="{{ $routes['update'] }}" class="caronte-form">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="name" class="form-label">Display name</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $user['name'] ?? '') }}" class="form-control" required>
                        </div>

                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" value="{{ $user['email'] ?? '' }}" class="form-control" disabled>
                        </div>

                        <button type="submit" class="btn caronte-btn-primary">Save profile</button>
                    </form>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="caronte-card h-100">
                    <div class="caronte-card__header">
                        <h2>Application roles</h2>
                        <p>Roles are sourced from local config and synchronized to the user as a final set.</p>
                    </div>

                    <form method="POST" action="{{ $routes['syncRoles'] }}" class="caronte-form">
                        @csrf
                        @method('PUT')

                        <div class="caronte-checkbox-list">
                            @foreach ($configured_roles as $role)
                                <label class="caronte-checkbox">
                                    <input type="checkbox" name="roles[]" value="{{ $role['uri_applicationRole'] }}"
                                        @checked(in_array($role['uri_applicationRole'], $assigned_role_uris, true))>
                                    <span>
                                        <strong>{{ $role['name'] }}</strong>
                                        <small>{{ $role['description'] }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <button type="submit" class="btn caronte-btn-primary">Synchronize role set</button>
                    </form>
                </div>
            </div>

            @if (data_get($features, 'metadata', false))
                <div class="col-12">
                    <div class="caronte-card">
                        <div class="caronte-card__header">
                            <h2>Metadata</h2>
                            <p>Attach application-scoped metadata entries to this user.</p>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-lg-6">
                                <form method="POST" action="{{ $routes['storeMetadata'] }}" class="caronte-form">
                                    @csrf
                                    <div>
                                        <label for="key" class="form-label">Key</label>
                                        <input id="key" type="text" name="key" class="form-control" required>
                                    </div>

                                    <div>
                                        <label for="value" class="form-label">Value</label>
                                        <textarea id="value" name="value" rows="4" class="form-control"></textarea>
                                    </div>

                                    <button type="submit" class="btn caronte-btn-primary">Store metadata</button>
                                </form>
                            </div>

                            <div class="col-12 col-lg-6">
                                <h3 class="h6 mb-3">Current entries</h3>
                                @forelse (($user['metadata'] ?? []) as $item)
                                    <form method="POST" action="{{ $routes['deleteMetadata'] }}" class="d-flex justify-content-between align-items-start gap-3 border rounded p-3 mb-3">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="key" value="{{ $item['key'] ?? '' }}">
                                        <div>
                                            <div class="fw-semibold">{{ $item['key'] ?? '' }}</div>
                                            <small class="text-muted">{{ $item['value'] ?? '' }}</small>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @empty
                                    <p class="text-muted mb-0">No metadata entries are currently stored for this user.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
