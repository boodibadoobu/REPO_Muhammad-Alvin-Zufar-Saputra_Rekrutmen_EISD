<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="LaporKita membantu warga melaporkan permukiman kumuh dan infrastruktur rusak.">
    <title>@yield('title', 'LaporKita') · Kota Lebih Layak</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="site-header">
        <div class="container nav-wrap">
            <a href="{{ route('home') }}" class="brand" aria-label="LaporKita beranda">
                <span class="brand-mark" aria-hidden="true">L</span>
                <span>Lapor<span>Kita</span></span>
            </a>

            <nav class="desktop-nav" aria-label="Navigasi utama">
                <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>Beranda</a>
                <a href="{{ route('public-reports.index') }}" @class(['active' => request()->routeIs('public-reports.*')])>Laporan Publik</a>
                @auth
                    <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Dashboard</a>
                    <a href="{{ route('reports.index') }}" @class(['active' => request()->routeIs('reports.*')])>Laporan</a>
                    @if(auth()->user()->hasRole(\App\Enums\UserRole::Admin))
                        <a href="{{ route('admin.categories.index') }}" @class(['active' => request()->routeIs('admin.categories.*')])>Kategori</a>
                        <a href="{{ route('admin.users.index') }}" @class(['active' => request()->routeIs('admin.users.*')])>Pengguna</a>
                    @endif
                @endauth
            </nav>

            <div class="nav-actions">
                @guest
                    <a href="{{ route('login') }}" class="button button-ghost">Masuk</a>
                    <a href="{{ route('register') }}" class="button button-primary nav-register">Daftar</a>
                    <details class="mobile-menu">
                        <summary>Menu</summary>
                        <div>
                            <a href="{{ route('home') }}">Beranda</a>
                            <a href="{{ route('public-reports.index') }}">Laporan Publik</a>
                            <a href="{{ route('register') }}">Daftar sebagai warga</a>
                        </div>
                    </details>
                @else
                    <div class="user-chip">
                        <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span>
                            <strong>{{ auth()->user()->name }}</strong>
                            <small>{{ auth()->user()->role->label() }}</small>
                        </span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="button button-ghost">Keluar</button>
                    </form>
                    <details class="mobile-menu">
                        <summary>Menu</summary>
                        <div>
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                            <a href="{{ route('public-reports.index') }}">Laporan Publik</a>
                            <a href="{{ route('reports.index') }}">Laporan</a>
                            @if(auth()->user()->hasRole(\App\Enums\UserRole::Admin))
                                <a href="{{ route('admin.categories.index') }}">Kategori</a>
                                <a href="{{ route('admin.users.index') }}">Pengguna</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit">Keluar</button>
                            </form>
                        </div>
                    </details>
                @endguest
            </div>
        </div>
    </header>

    @if(session('success') || session('error') || $errors->any())
        <div class="container flash-stack" role="status">
            @if(session('success'))
                <div class="flash flash-success"><span>✓</span><p>{{ session('success') }}</p></div>
            @endif
            @if(session('error'))
                <div class="flash flash-error"><span>!</span><p>{{ session('error') }}</p></div>
            @endif
            @if($errors->any())
                <div class="flash flash-error">
                    <span>!</span>
                    <div>
                        <strong>Periksa kembali data yang kamu isi.</strong>
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <a href="{{ route('home') }}" class="brand brand-light">
                    <span class="brand-mark" aria-hidden="true">L</span>
                    <span>Lapor<span>Kita</span></span>
                </a>
                <p>Suara warga untuk lingkungan yang lebih aman, sehat, dan layak huni.</p>
            </div>
            <div>
                <strong>Selaras dengan SDG 11</strong>
                <p>Kota dan permukiman yang inklusif, aman, tangguh, dan berkelanjutan.</p>
                <a href="{{ route('public-reports.index') }}" class="footer-link">Lihat laporan publik →</a>
            </div>
            <p class="copyright">© {{ date('Y') }} LaporKita · Rekrutmen Aslab EISD</p>
        </div>
    </footer>
</body>
</html>
