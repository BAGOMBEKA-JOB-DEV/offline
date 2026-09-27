<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css'])
        @else
            <style>
                body { font-family: ui-sans-serif, system-ui, sans-serif; }
            </style>
        @endif
    </head>
    <body>
        <div class="max-w-4xl mx-auto p-8">
            <h1 class="text-3xl font-bold">Offline-First Sync Simulator</h1>
            <p class="mt-2 text-gray-600">Backend API is running. Visit the frontend at <a href="http://localhost:5173" class="text-indigo-600">localhost:5173</a>.</p>
            <div class="mt-6 bg-gray-50 rounded-lg p-6">
                <h2 class="text-lg font-semibold mb-2">API Endpoints</h2>
                <ul class="list-disc list-inside text-sm text-gray-700">
                    <li><code>POST /api/v1/sync</code> — Flush a device's offline queue</li>
                    <li><code>GET /api/v1/submissions</code> — List committed records</li>
                    <li><code>GET /api/v1/sync-runs</code> — Flush history</li>
                </ul>
            </div>
        </div>
    </body>
</html>