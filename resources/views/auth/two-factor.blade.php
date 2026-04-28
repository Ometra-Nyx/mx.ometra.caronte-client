@extends('caronte::layouts.base')

@php($branding = $branding ?? config('caronte.ui.branding', []))

@section('content')
    <section class="caronte-auth">
        <div class="caronte-auth__panel">
            <span class="caronte-kicker">{{ data_get($branding, 'app_name', config('app.name')) }}</span>
            <h1 class="caronte-title">Two-factor sign in</h1>
            <p class="caronte-copy">We will send a secure login link to the email address registered in Caronte.</p>

            <div class="caronte-card">
                <div class="caronte-card__header">
                    <h2>Request a sign-in link</h2>
                    <p>Open the email on any device and follow the secure link.</p>
                </div>

                <form method="POST" action="{{ $routes['twoFactorRequest'] }}" class="caronte-form">
                    @csrf
                    @if ($callback_url)
                        <input type="hidden" name="callback_url" value="{{ $callback_url }}">
                    @endif

                    <div>
                        <label for="email" class="form-label">Registered email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
                    </div>

                    <button type="submit" class="btn caronte-btn-primary">Send sign-in link</button>
                </form>

                <div class="caronte-card__footer">
                    <a href="{{ $routes['passwordRecoverForm'] }}">Need to reset your password first?</a>
                </div>
            </div>
        </div>
    </section>
@endsection
