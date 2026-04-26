@extends('caronte::layouts.base')

@section('content')
    <section class="caronte-auth">
        <div class="caronte-auth__panel">
            <span class="caronte-kicker">{{ data_get($branding, 'app_name', config('app.name')) }}</span>
            <h1 class="caronte-title">Recover access</h1>
            <p class="caronte-copy">Enter your email and we will send password reset instructions.</p>

            <div class="caronte-card">
                <div class="caronte-card__header">
                    <h2>Password recovery</h2>
                    <p>If the account exists, a recovery message will be sent immediately.</p>
                </div>

                <form method="POST" action="{{ $routes['passwordRecoverRequest'] }}" class="caronte-form">
                    @csrf
                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
                    </div>

                    <button type="submit" class="btn caronte-btn-primary">Send recovery instructions</button>
                </form>

                <div class="caronte-card__footer">
                    <a href="{{ $routes['login'] }}">Back to sign in</a>
                </div>
            </div>
        </div>
    </section>
@endsection
