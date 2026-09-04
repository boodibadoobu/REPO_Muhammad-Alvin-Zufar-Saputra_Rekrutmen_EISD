@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <section class="auth-section">
        <div class="auth-card">
            <div class="auth-heading"><span class="eyebrow">Selamat datang kembali</span><h1>Masuk ke LaporKita</h1><p>Pantau laporanmu atau lanjutkan proses penanganan.</p></div>
            <form method="POST" action="{{ route('login') }}" class="form-stack">
                @csrf
                <label class="field"><span>Email</span><input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="nama@email.com">@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Kata sandi</span><input type="password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi">@error('password')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="check-line"><input type="checkbox" name="remember" value="1"><span>Ingat saya</span></label>
                <button type="submit" class="button button-primary button-block">Masuk</button>
            </form>
            <p class="auth-switch">Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai warga</a></p>
        </div>
    </section>
@endsection
