<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Gantian - Rental Management Platform</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased">
        <div class="min-h-screen">
            <header class="border-b border-slate-200 bg-white">
                <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8" aria-label="Primary navigation">
                    <a href="{{ url('/') }}" class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-950 text-base font-semibold text-white">G</span>
                        <span class="text-base font-semibold text-slate-950">Gantian</span>
                    </a>

                    <div class="flex items-center gap-2">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-slate-700 transition hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                Login
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </div>
                </nav>
            </header>

            <main>
                <section class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1fr)_28rem] lg:px-8 lg:py-16">
                    <div class="flex flex-col justify-center">
                        <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Enterprise rental management</p>
                        <h1 class="mt-4 max-w-3xl text-4xl font-semibold tracking-normal text-slate-950 sm:text-5xl">
                            Sistem peminjaman barang untuk katalog, booking, operasional rental, dan laporan.
                        </h1>
                        <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600">
                            Gantian membantu customer mengajukan booking, staff memproses check-out dan check-in, serta admin mengelola katalog dan revenue report dalam satu workflow yang terstruktur.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex justify-center rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                    Open dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex justify-center rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                    Login sebagai reviewer
                                </a>
                                <a href="{{ route('register') }}" class="inline-flex justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                    Buat akun customer
                                </a>
                            @endauth
                        </div>
                    </div>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="preview-title">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Live workflow</p>
                                <h2 id="preview-title" class="mt-1 text-lg font-semibold text-slate-950">Rental operations board</h2>
                            </div>
                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Available</span>
                        </div>

                        <div class="mt-5 space-y-3">
                            @foreach ([
                                ['label' => 'Pending request', 'detail' => 'Customer submits date range and item request.', 'status' => 'Queue'],
                                ['label' => 'Approved booking', 'detail' => 'Staff validates stock and reserves availability.', 'status' => 'Reserved'],
                                ['label' => 'Active rental', 'detail' => 'Item is checked out and tracked until return.', 'status' => 'In use'],
                            ] as $item)
                                <article class="rounded-md border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <h3 class="text-sm font-semibold text-slate-950">{{ $item['label'] }}</h3>
                                        <span class="text-xs font-medium text-slate-500">{{ $item['status'] }}</span>
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['detail'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </section>
                </section>

                <section class="border-y border-slate-200 bg-white">
                    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-8 sm:grid-cols-3 sm:px-6 lg:px-8">
                        @foreach ([
                            ['role' => 'Customer', 'summary' => 'Browse catalog, cek availability, dan submit booking request.'],
                            ['role' => 'Staff', 'summary' => 'Approve booking, proses check-out/check-in, dan input denda.'],
                            ['role' => 'Admin/Owner', 'summary' => 'Kelola item, pricing, stock, dan pantau revenue report.'],
                        ] as $role)
                            <article class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                                <h2 class="text-base font-semibold text-slate-950">{{ $role['role'] }}</h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $role['summary'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <div class="grid gap-4 md:grid-cols-4">
                        @foreach ([
                            'RBAC dengan actor inheritance',
                            'Availability hardening untuk stock rental',
                            'Atomic booking lifecycle',
                            'Denda dan revenue reporting',
                        ] as $feature)
                            <div class="rounded-md border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                                {{ $feature }}
                            </div>
                        @endforeach
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
