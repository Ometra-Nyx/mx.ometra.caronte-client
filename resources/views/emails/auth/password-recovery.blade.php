@php($branding = config('caronte.ui.branding', []))
<div style="font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto; color: #0f172a;">
    <h1 style="font-size: 24px; margin-bottom: 12px;">Reset your password</h1>
    <p style="margin-bottom: 16px;">Use the link below to choose a new password for {{ data_get($branding, 'app_name', config('app.name')) }}.</p>
    <p style="margin-bottom: 24px;">
        <a href="{{ $actionUrl }}" style="display: inline-block; padding: 12px 18px; background: {{ data_get($branding, 'accent', '#0f766e') }}; color: #ffffff; text-decoration: none; border-radius: 8px;">Reset password</a>
    </p>
    @if ($expiresAt)
        <p style="font-size: 14px; color: #475569;">This link expires at {{ $expiresAt }}.</p>
    @endif
</div>
