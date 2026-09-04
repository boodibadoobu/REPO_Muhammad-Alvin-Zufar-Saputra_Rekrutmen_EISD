@extends('layouts.app')

@section('title', 'Daftar')

@section('content')
    <section class="auth-section">
        <div class="auth-card auth-card-wide">
            <div class="auth-heading"><span class="eyebrow">Ambil bagian</span><h1>Buat akun warga</h1><p>Satu akun untuk melapor dan memantau perbaikan lingkungan.</p></div>
            <form method="POST" action="{{ route('register') }}" class="form-stack">
                @csrf
                <label class="field"><span>Nama lengkap</span><input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Nama lengkap">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Email</span><input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="nama@email.com">@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
                <div class="form-grid">
                    <label class="field"><span>Kata sandi</span><input type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter">@error('password')<small class="field-error">{{ $message }}</small>@enderror</label>
                    <label class="field"><span>Konfirmasi kata sandi</span><input type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi kata sandi"></label>
                </div>
                <p class="form-hint">Gunakan kombinasi huruf besar, huruf kecil, dan angka.</p>
                <button type="submit" class="button button-primary button-block">Buat akun warga</button>
            </form>
            <p class="auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>
        </div>
    </section>
@endsection
