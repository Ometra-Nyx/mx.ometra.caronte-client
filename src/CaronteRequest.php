<?php

/**
 * Handles requests to the Caronte server for authentication and user management.
 *
 * @author Gabriel Ruelas
 * @license MIT
 * @version 1.4.0
 */

namespace Ometra\Caronte;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\View\View;
use Inertia\Response as InertiaResponse;
use Equidna\Toolkit\Helpers\RouteHelper;
use Equidna\Toolkit\Helpers\ResponseHelper;
use Ometra\Caronte\Facades\Caronte;
use Equidna\Toolkit\Exceptions\BadRequestException;
use Equidna\Toolkit\Exceptions\UnauthorizedException;
use Exception;

/**
 * This class is responsible for making basic requests to the Caronte server.
 */

class CaronteRequest
{
    private function __construct()
    {
        //ONLY STATIC METHODS ALLOWED
    }

    /**
     * Log in a user with email and password.
     *
     * @param Request $request HTTP request with user credentials and callback URL.
     * @return JsonResponse|RedirectResponse API response or redirect response.
     * @throws UnauthorizedException If authentication fails.
     */
    public static function userPasswordLogin(Request $request): JsonResponse|RedirectResponse
    {
        $decoded_url  = base64_decode($request->callback_url);

        if (!empty($decoded_url) && $decoded_url !== '\\') {
            $callback_url = $decoded_url;
        } else {
            $callback_url = config('caronte.SUCCESS_URL');
        }

        try {
            /** @var \Illuminate\Http\Client\Response $caronte_response */
            $caronte_response = HTTP::withOptions(
                [
                    'verify' => !config('caronte.ALLOW_HTTP_REQUESTS')
                ]
            )->post(
                url: config('caronte.URL') . 'api/user/login',
                data: [
                    'email'    => $request->email,
                    'password' => $request->password,
                    'app_id'   => config('caronte.APP_ID')
                ]
            );

            if ($caronte_response->failed()) {
                throw new RequestException(response: $caronte_response);
            }

            $token  = CaronteToken::validateToken(raw_token: $caronte_response->body());
        } catch (RequestException | Exception $e) {
            throw new UnauthorizedException(
                message: $e->getMessage(),
                previous: $e
            );
        }

        if (RouteHelper::isWeb()) {
            Caronte::saveToken($token->toString());
        }

        return ResponseHelper::success(
            message: 'Login successful',
            data: [
                'token' => $token->toString(),
            ],
            forward_url: $callback_url
        );
    }

    /**
     * Send a two-factor authentication token request.
     *
     * @param Request $request HTTP request with email and callback URL.
     * @return JsonResponse|RedirectResponse API response or redirect response.
     * @throws UnauthorizedException If the request fails.
     */
    public static function twoFactorTokenRequest(Request $request): JsonResponse|RedirectResponse
    {
        try {
            /** @var \Illuminate\Http\Client\Response $caronte_response */
            $caronte_response = HTTP::withOptions(
                [
                    'verify' => !config('caronte.ALLOW_HTTP_REQUESTS')
                ]
            )->post(
                url: config('caronte.URL') . 'api/user/2fa',
                data: [
                    'email'     => $request->email,
                    'app_id'    => config('caronte.APP_ID'),
                    'app_url'   => config('app.url'),
                ]
            );

            if ($caronte_response->failed()) {
                throw new RequestException($caronte_response);
            }

            $response = $caronte_response->body();
        } catch (RequestException | Exception $e) {
            throw new UnauthorizedException(
                message: $e->getMessage(),
                previous: $e
            );
        }

        return ResponseHelper::success(
            message: $response,
            forward_url: config('caronte.LOGIN_URL')
        );
    }

    /**
     * Log in a user using a two-factor authentication token.
     *
     * @param Request $request HTTP request object.
     * @param string $token Two-factor authentication token.
     * @return JsonResponse|RedirectResponse API response or redirect response.
     * @throws UnauthorizedException If authentication fails.
     */
    public static function twoFactorTokenLogin(Request $request, string $token): JsonResponse|RedirectResponse
    {
        $decoded_url  = base64_decode($request->callback_url);

        if (!empty($decoded_url) && $decoded_url !== '\\') {
            $callback_url = $decoded_url;
        } else {
            $callback_url = config('caronte.SUCCESS_URL');
        }

        try {
            /** @var \Illuminate\Http\Client\Response $caronte_response */
            $caronte_response = HTTP::withOptions(
                [
                    'verify' => !config('caronte.ALLOW_HTTP_REQUESTS')
                ]
            )->post(
                url: config('caronte.URL') . 'api/user/2fa/' . $token,
                data: [
                    'app_id'    => config('caronte.APP_ID'),
                ]
            );

            if ($caronte_response->failed()) {
                throw new RequestException($caronte_response);
            }

            $token = CaronteToken::validateToken(raw_token: $caronte_response->body());
        } catch (RequestException | Exception $e) {
            throw new UnauthorizedException(
                message: $e->getMessage(),
                previous: $e
            );
        }

        if (RouteHelper::isWeb()) {
            Caronte::saveToken($token->toString());
        }

        return ResponseHelper::success(
            message: 'Login successful',
            data: [
                'token' => $token->toString(),
            ],
            forward_url: $callback_url
        );
    }

    /**
     * Initiate password recovery for a user.
     *
     * @param Request $request HTTP request with user email.
     * @return JsonResponse|RedirectResponse API response or redirect response.
     * @throws BadRequestException If the request fails.
     */
    public static function passwordRecoverRequest(Request $request): JsonResponse|RedirectResponse
    {
        try {
            /** @var \Illuminate\Http\Client\Response $caronte_response */
            $caronte_response = HTTP::withOptions(
                [
                    'verify' => !config('caronte.ALLOW_HTTP_REQUESTS')
                ]
            )->post(
                url: config('caronte.URL') . 'api/user/password/recover',
                data: [
                    'email'   => $request->email,
                    'app_id'  => config('caronte.APP_ID'),
                    'app_url' => config('app.url')
                ]
            );

            if ($caronte_response->failed()) {
                throw new RequestException(response: $caronte_response);
            }

            $response = $caronte_response->body();
        } catch (RequestException | Exception $e) {
            throw new BadRequestException(
                message: $e->getMessage(),
                previous: $e
            );
        }

        return ResponseHelper::success(
            message: $response,
            forward_url: config('caronte.LOGIN_URL')
        );
    }

    /**
     * Validate a password recovery token.
     *
     * @param string $token Password recovery token.
     * @return JsonResponse|View|InertiaResponse API response or view.
     * @throws UnauthorizedException If validation fails.
     */
    public static function passwordRecoverTokenValidation(string $token): JsonResponse|View|InertiaResponse
    {
        try {
            /** @var \Illuminate\Http\Client\Response $caronte_response */
            $caronte_response = HTTP::withOptions(
                [
                    'verify' => !config('caronte.ALLOW_HTTP_REQUESTS')
                ]
            )->get(
                url: config('caronte.URL') . 'api/user/password/recover/' . $token
            );

            if ($caronte_response->failed()) {
                throw new RequestException($caronte_response);
            }

            $response = $caronte_response->body();
        } catch (RequestException | Exception $e) {
            throw new UnauthorizedException(
                message: $e->getMessage(),
                previous: $e
            );
        }

        if (RouteHelper::isAPI()) {
            return ResponseHelper::success(
                message: 'Token validated successfully',
                data: $response
            );
        }

        $token_response = json_decode($response);

        if (config('caronte.USE_INERTIA')) {
            return inertia('auth/password-recover', [
                'user' => $token_response->user,
                'callback_url' => request()->query('callback_url'),
                'csrf_token' => csrf_token(),
                'routes' => [
                    'passwordRecoverSubmit' => url()->current(),
                ],
            ]);
        }

        return View('caronte::auth.password-recover')->with(['user' => $token_response->user]);
    }

    /**
     * Complete password recovery for a user.
     *
     * @param Request $request HTTP request with new password.
     * @param string $token Password recovery token.
     * @return JsonResponse|RedirectResponse API response or redirect response.
     * @throws UnauthorizedException If recovery fails.
     */
    public static function passwordRecover(Request $request, string $token): JsonResponse|RedirectResponse
    {
        try {
            /** @var \Illuminate\Http\Client\Response $caronte_response */
            $caronte_response = HTTP::withOptions(
                [
                    'verify' => !config('caronte.ALLOW_HTTP_REQUESTS')
                ]
            )->post(
                url: config('caronte.URL') . 'api/user/password/recover/' . $token,
                data: [
                    'password'              => $request->password,
                    'password_confirmation' => $request->password
                ]
            );

            if ($caronte_response->failed()) {
                throw new RequestException($caronte_response);
            }

            $response = $caronte_response->body();
        } catch (RequestException | Exception $e) {
            throw new UnauthorizedException(
                message: $e->getMessage(),
                previous: $e
            );
        }

        return ResponseHelper::success(
            message: 'Password recovered successfully',
            data: $response,
            forward_url: config('caronte.LOGIN_URL')
        );
    }

    /**
     * Log out the user and clear the token.
     *
     * @param bool $logout_all_sessions Whether to log out from all sessions (default: false).
     * @return JsonResponse|RedirectResponse API response or redirect response.
     * @throws BadRequestException If logout fails.
     */
    public static function logout(bool $logout_all_sessions = false): JsonResponse|RedirectResponse
    {
        try {
            /** @var \Illuminate\Http\Client\Response $caronte_response */
            $caronte_response = HTTP::withOptions(
                [
                    'verify' => !config('caronte.ALLOW_HTTP_REQUESTS')
                ]
            )->withHeaders(
                [
                    'Authorization' => "Bearer " . Caronte::getToken()->toString()
                ]
            )->get(
                url: config('caronte.URL') . 'api/user/logout' . ($logout_all_sessions ? 'All' : '')
            );

            if ($caronte_response->failed()) {
                throw new RequestException($caronte_response);
            }

            $response = $caronte_response->body();
        } catch (RequestException | Exception $e) {
            throw new BadRequestException(
                message: $e->getMessage(),
                previous: $e
            );
        }

        Caronte::clearToken();

        return ResponseHelper::success(
            message: 'Logout successful',
            data: $response,
            forward_url: config('caronte.LOGIN_URL')
        );
    }

    public static function setMetadata(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $token = base64_encode(sha1(config('caronte.APP_ID')) . ':' . config('caronte.APP_SECRET'));
            /** @var \Illuminate\Http\Client\Response $caronte_response */
            $caronte_response = HTTP::withOptions(
                [
                    'verify' => !config('caronte.ALLOW_HTTP_REQUESTS')
                ]
            )->withToken($token, 'Token')
                ->post(
                    url: config('caronte.URL') . 'api/app/users/' . Caronte::getUser()->uri_user . '/metadata',
                    data: $request->all()
                );
            if ($caronte_response->failed()) {
                throw new RequestException($caronte_response);
            }
        } catch (RequestException | Exception $e) {
            throw new BadRequestException(
                message: $e->getMessage(),
                previous: $e
            );
        }
        return ResponseHelper::success(
            message: 'Metadata updated successfully',
            data: json_decode($caronte_response->body())
        );
    }
}
