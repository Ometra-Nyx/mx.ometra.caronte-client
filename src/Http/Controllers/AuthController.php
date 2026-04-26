<?php

namespace Ometra\Caronte\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Response as InertiaResponse;
use Ometra\Caronte\CaronteRequest;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseController
{
    public function loginForm(Request $request): View|InertiaResponse
    {
        $view = config('caronte.USE_2FA') ? 'auth.two-factor' : 'auth.login';

        return $this->toView($view, [
            'callback_url' => $request->query('callback_url'),
            'csrf_token' => csrf_token(),
            'branding' => $this->branding(),
            'routes' => [
                'login' => route('caronte.login'),
                'logout' => route('caronte.logout'),
                'twoFactorRequest' => route('caronte.2fa.request'),
                'passwordRecoverForm' => route('caronte.password.recover.form'),
            ],
        ]);
    }

    public function login(Request $request): Response
    {
        if (config('caronte.USE_2FA')) {
            return CaronteRequest::twoFactorTokenRequest($request);
        }

        return CaronteRequest::userPasswordLogin($request);
    }

    public function twoFactorTokenRequest(Request $request): Response
    {
        return CaronteRequest::twoFactorTokenRequest($request);
    }

    public function twoFactorTokenLogin(Request $request, string $token): Response
    {
        return CaronteRequest::twoFactorTokenLogin($request, $token);
    }

    public function passwordRecoverRequestForm(): View|InertiaResponse
    {
        return $this->toView('auth.password-recover-request', [
            'csrf_token' => csrf_token(),
            'branding' => $this->branding(),
            'routes' => [
                'passwordRecoverRequest' => route('caronte.password.recover.request'),
                'login' => route('caronte.login.form'),
            ],
        ]);
    }

    public function passwordRecoverRequest(Request $request): Response
    {
        return CaronteRequest::passwordRecoverRequest($request);
    }

    public function passwordRecoverTokenValidation(Request $request, string $token): Response|View|InertiaResponse
    {
        return CaronteRequest::passwordRecoverTokenValidation($token);
    }

    public function passwordRecover(Request $request, string $token): Response
    {
        return CaronteRequest::passwordRecover($request, $token);
    }

    public function logout(Request $request): Response
    {
        return CaronteRequest::logout($request->boolean('all'));
    }
}
