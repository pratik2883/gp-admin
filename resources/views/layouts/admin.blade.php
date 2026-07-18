<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin</title>
    <link href="/css/filament/filament/app.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
<div class="min-h-screen flex">
    <aside class="hidden md:flex md:flex-col w-64 shrink-0 bg-white border-r">
        <div class="h-16 flex items-center px-6 border-b">
            <a href="/admin/dashboard" class="text-base font-semibold tracking-tight">Admin</a>
        </div>
        <nav class="p-2 space-y-1">
            <a href="/admin/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-gray-50">
                <span>Dashboard</span>
            </a>
            <a href="/admin/specialists" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-gray-50">
                <span>Specialists</span>
            </a>
            <a href="/admin/hospitals" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-gray-50">
                <span>Hospitals</span>
            </a>
            <a href="/admin/gps" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-gray-50">
                <span>GPs</span>
            </a>
            <a href="/admin/referrals" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-gray-50">
                <span>Referrals</span>
            </a>
        </nav>
    </aside>
    <div class="flex-1 flex flex-col">
        <header class="h-16 bg-white border-b flex items-center px-4 md:px-6">
            <div class="flex items-center gap-3">
                <button class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg border bg-white">
                    <span class="sr-only">Menu</span>
                </button>
                <div class="text-lg font-semibold tracking-tight">Admin Panel</div>
            </div>
            <div class="ml-auto">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Logout</button>
                </form>
            </div>
        </header>
        <main class="p-4 md:p-6">
            <div class="container-fluid">
                @yield('content')
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
