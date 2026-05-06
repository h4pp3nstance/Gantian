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
                            Gantian dirancang untuk organisasi yang perlu mengelola barang sewaan secara rapi: customer memilih item dan tanggal, staff memvalidasi ketersediaan, lalu admin memantau katalog, denda, dan performa revenue dari satu sistem.
                        </p>
                        <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                            Fokus utama platform ini adalah menjaga workflow rental tetap jelas dari awal sampai akhir, mengurangi risiko overbooking, dan memberi frontdesk informasi yang cukup saat barang keluar maupun kembali.
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

                        <div class="mt-8 grid max-w-2xl gap-3 sm:grid-cols-3">
                            @foreach ([
                                ['value' => '3 role', 'label' => 'Customer, Staff, Admin'],
                                ['value' => '5 status', 'label' => 'Lifecycle booking rental'],
                                ['value' => '1 stock rule', 'label' => 'Approved dan active reserve stock'],
                            ] as $metric)
                                <div class="rounded-md border border-slate-200 bg-white px-4 py-3 shadow-sm">
                                    <div class="text-sm font-semibold text-slate-950">{{ $metric['value'] }}</div>
                                    <div class="mt-1 text-xs leading-5 text-slate-500">{{ $metric['label'] }}</div>
                                </div>
                            @endforeach
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
                                ['label' => 'Pending request', 'detail' => 'Customer memilih item, tanggal mulai, tanggal selesai, lalu sistem menghitung estimasi total rental.', 'status' => 'Queue'],
                                ['label' => 'Approved booking', 'detail' => 'Staff menyetujui request setelah availability service memastikan stock belum habis untuk tanggal yang dipilih.', 'status' => 'Reserved'],
                                ['label' => 'Active rental', 'detail' => 'Barang sudah check-out, tercatat sebagai peminjaman berjalan, dan menunggu proses check-in serta inspeksi kondisi.', 'status' => 'In use'],
                                ['label' => 'Completed return', 'detail' => 'Staff mencatat kondisi barang, menambahkan denda bila perlu, lalu laporan admin ikut diperbarui.', 'status' => 'Closed'],
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
                            ['role' => 'Customer', 'summary' => 'Customer dapat melihat katalog barang, membaca status ketersediaan, memilih rentang tanggal, dan mengirim booking request tanpa perlu menghubungi frontdesk secara manual.'],
                            ['role' => 'Staff', 'summary' => 'Staff menangani validasi booking, approval, check-out, check-in, inspeksi kondisi barang, serta pencatatan denda untuk keterlambatan atau kerusakan.'],
                            ['role' => 'Admin/Owner', 'summary' => 'Admin mewarisi kemampuan Staff dan memiliki kontrol tambahan untuk mengelola item, harga harian, stock, status maintenance, serta laporan revenue.'],
                        ] as $role)
                            <article class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                                <h2 class="text-base font-semibold text-slate-950">{{ $role['role'] }}</h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $role['summary'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="mx-auto grid max-w-7xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-[22rem_minmax(0,1fr)] lg:px-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Operational clarity</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Masalah rental yang disederhanakan</h2>
                        <p class="mt-4 text-sm leading-6 text-slate-600">
                            Platform rental tidak cukup hanya menyimpan daftar barang. Sistem harus tahu kapan stock dianggap reserved, siapa yang boleh memproses transaksi, dan bagaimana kondisi barang dicatat setelah kembali.
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        @foreach ([
                            ['title' => 'Overbooking prevention', 'body' => 'Availability dihitung dari booking approved dan active, sehingga request baru tidak mengambil stock yang sudah benar-benar terpakai.'],
                            ['title' => 'Clear handoff', 'body' => 'Status booking bergerak dari pending, approved, active, completed, sampai cancelled dengan aturan transisi yang eksplisit.'],
                            ['title' => 'Return accountability', 'body' => 'Check-in mencatat inspeksi kondisi barang dan memberi ruang bagi staff untuk menambahkan denda operasional.'],
                        ] as $problem)
                            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                <h3 class="text-sm font-semibold text-slate-950">{{ $problem['title'] }}</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $problem['body'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="border-y border-slate-200 bg-white">
                    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1fr)_24rem] lg:px-8">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reviewer path</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-950">Alur demo yang bisa langsung diuji</h2>
                            <p class="mt-4 max-w-3xl text-sm leading-6 text-slate-600">
                                Untuk review capstone, mulai dari akun customer untuk membuat request, pindah ke staff untuk approval dan check-in, lalu masuk sebagai admin untuk melihat dampaknya pada katalog dan laporan.
                            </p>

                            <div class="mt-6 grid gap-3 md:grid-cols-3">
                                @foreach ([
                                    ['step' => '01', 'title' => 'Ajukan booking', 'body' => 'Customer memilih item available dan tanggal sewa.'],
                                    ['step' => '02', 'title' => 'Proses rental', 'body' => 'Staff approve, checkout, check-in, dan catat denda bila diperlukan.'],
                                    ['step' => '03', 'title' => 'Pantau laporan', 'body' => 'Admin melihat item, status booking, paid fines, dan revenue.'],
                                ] as $step)
                                    <article class="rounded-md border border-slate-200 bg-slate-50 p-4">
                                        <span class="text-xs font-semibold text-slate-500">{{ $step['step'] }}</span>
                                        <h3 class="mt-2 text-sm font-semibold text-slate-950">{{ $step['title'] }}</h3>
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $step['body'] }}</p>
                                    </article>
                                @endforeach
                            </div>
                        </div>

                        <aside class="rounded-lg border border-slate-200 bg-slate-950 p-5 text-white shadow-sm">
                            <h2 class="text-base font-semibold">Demo accounts</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-300">
                                Seeder menyediakan akun reviewer untuk setiap role. Semua akun menggunakan password default:
                            </p>
                            <div class="mt-4 rounded-md bg-white/10 px-3 py-2 font-mono text-sm">password</div>
                            <dl class="mt-5 space-y-3 text-sm">
                                <div>
                                    <dt class="text-slate-400">Admin</dt>
                                    <dd class="font-medium">admin@gantian.test</dd>
                                </div>
                                <div>
                                    <dt class="text-slate-400">Staff</dt>
                                    <dd class="font-medium">staff@gantian.test</dd>
                                </div>
                                <div>
                                    <dt class="text-slate-400">Customer</dt>
                                    <dd class="font-medium">customer@gantian.test</dd>
                                </div>
                            </dl>
                        </aside>
                    </div>
                </section>

                <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <div class="grid gap-4 md:grid-cols-4">
                        @foreach ([
                            'RBAC dengan actor inheritance untuk Admin dan Staff',
                            'Availability hardening untuk stock rental yang sedang reserved',
                            'Atomic booking lifecycle agar status tidak berubah setengah jalan',
                            'Denda, fine settlement, dan revenue reporting untuk Admin',
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
