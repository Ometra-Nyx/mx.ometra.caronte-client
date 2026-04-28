@extends('caronte::layouts.base')

@php($branding = $branding ?? config('caronte.ui.branding', []))

@section('content')
    <section class="caronte-auth">
        <div class="caronte-auth__panel">
            <span class="caronte-kicker">{{ data_get($branding, 'app_name', config('app.name')) }}</span>
            <h1 class="caronte-title">Choose a new password</h1>
            <p class="caronte-copy">Your reset token is valid. Set a new password to continue.</p>

            <div class="caronte-card">
                <div class="caronte-card__header">
                    <h2>Reset password</h2>
                    <p>Use at least eight characters.</p>
                </div>

                <form method="POST" action="{{ $routes['passwordRecoverSubmit'] }}" class="caronte-form">
                    @csrf
                    <div>
                        <label for="password" class="form-label">New password</label>
                        <input id="password" type="password" name="password" class="form-control" required>
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required>
                    </div>

                    <button type="submit" class="btn caronte-btn-primary">Update password</button>
                </form>

                <div class="caronte-card__footer">
                    <a href="{{ $routes['login'] }}">Back to sign in</a>
                </div>
            </div>
        </div>
    </section>
@endsection
