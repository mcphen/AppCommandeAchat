<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; }
        body {
            display: flex;
            min-height: 100%;
            align-items: center;
            justify-content: center;
            background-color: #f9fafb;
            color: #111827;
            padding: 2rem;
        }
        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1.5rem;
            padding: 3rem 2.5rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
        }
        .code {
            font-size: 5rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, @yield('code-color-from', '#6366f1'), @yield('code-color-to', '#4f46e5'));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            border-radius: 1rem;
            margin-bottom: 1.25rem;
            background-color: @yield('icon-bg', '#eef2ff');
        }
        h1 { font-size: 1.375rem; font-weight: 700; margin-top: 0.75rem; }
        p { color: #6b7280; margin-top: 0.625rem; font-size: 0.9375rem; line-height: 1.6; }
        .actions { margin-top: 2rem; display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        a.btn-primary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background-color: #4f46e5; color: #fff; font-weight: 600;
            padding: 0.625rem 1.5rem; border-radius: 0.75rem;
            text-decoration: none; font-size: 0.9375rem;
            transition: background-color 0.15s;
        }
        a.btn-primary:hover { background-color: #4338ca; }
        a.btn-outline {
            display: inline-flex; align-items: center; gap: 0.5rem;
            border: 1px solid #e5e7eb; color: #374151; font-weight: 600;
            padding: 0.625rem 1.5rem; border-radius: 0.75rem;
            text-decoration: none; font-size: 0.9375rem;
            transition: background-color 0.15s;
        }
        a.btn-outline:hover { background-color: #f3f4f6; }
        .divider { height: 1px; background: #f3f4f6; margin: 1.5rem 0; }
        .hint { font-size: 0.8125rem; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">@yield('code')</div>

        <div class="icon-wrap">
            @yield('icon')
        </div>

        <h1>@yield('title')</h1>
        <p>@yield('description')</p>

        <div class="actions">
            @yield('actions')
        </div>

        <div class="divider"></div>
        <p class="hint">{{ config('app.name') }} &mdash; Si le problème persiste, contactez votre administrateur.</p>
    </div>
</body>
</html>
