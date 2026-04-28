<?php

namespace Ometra\Caronte\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Inertia\Response as InertiaResponse;

class BaseController extends Controller
{
    protected function toView(string $viewPath, array $data = [], bool $management = false): View|InertiaResponse
    {
        $useInertia = $management
            ? (bool) config('caronte.management.use_inertia', false)
            : (bool) config('caronte.use_inertia', false);

        if ($useInertia) {
            return inertia(str_replace('.', '/', $viewPath), $data);
        }

        return view('caronte::' . $viewPath, $data);
    }

    /**
     * @return array<string, mixed>
     */
    protected function branding(): array
    {
        return (array) config('caronte.ui.branding', []);
    }
}
