@php
    $selectedCategories = collect(old('category_ids', isset($report) ? $report->categories->modelKeys() : []))->map(fn ($id) => (int) $id);
    $selectedLatitude = old('latitude', $report->latitude ?? '');
    $selectedLongitude = old('longitude', $report->longitude ?? '');
@endphp

<div class="form-stack">
    <label class="field">
        <span>Judul laporan</span>
        <input type="text" name="title" value="{{ old('title', $report->title ?? '') }}" required maxlength="150" placeholder="Contoh: Jalan lingkungan rusak berat">
        <small>Tulis masalah utama secara singkat dan spesifik.</small>
        @error('title')<small class="field-error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Deskripsi kondisi</span>
        <textarea name="description" rows="6" required maxlength="3000" placeholder="Jelaskan kondisi, dampak bagi warga, dan sejak kapan masalah terjadi...">{{ old('description', $report->description ?? '') }}</textarea>
        <small>Jelaskan sejak kapan kondisi terjadi dan dampaknya bagi pengguna fasilitas. Contoh: lubang membuat pejalan kaki harus turun ke jalan. Minimal 20 karakter; hindari nama atau data pribadi orang lain.</small>
        @error('description')<small class="field-error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Alamat atau lokasi</span>
        <textarea name="address" rows="3" required maxlength="500" placeholder="Nama jalan, RT/RW, kelurahan, dan patokan terdekat">{{ old('address', $report->address ?? '') }}</textarea>
        @error('address')<small class="field-error">{{ $message }}</small>@enderror
    </label>

    <div class="field location-field" data-location-picker>
        <div class="field-heading">
            <div><strong>Titik lokasi</strong><small>Klik peta atau geser pin sampai tepat di lokasi masalah.</small></div>
            <button type="button" class="button button-ghost button-small" data-use-current-location>◎ Gunakan lokasi saya</button>
        </div>
        <div class="location-map location-map-picker" data-map-canvas aria-label="Peta untuk memilih titik lokasi"></div>
        <input type="hidden" name="latitude" value="{{ $selectedLatitude }}">
        <input type="hidden" name="longitude" value="{{ $selectedLongitude }}">
        <div class="coordinate-readout"><span>Koordinat</span><strong data-coordinate-text aria-live="polite">{{ filled($selectedLatitude) && filled($selectedLongitude) ? $selectedLatitude.', '.$selectedLongitude : 'Belum dipilih' }}</strong></div>
        <noscript><p class="field-error">JavaScript perlu diaktifkan untuk memilih titik lokasi pada peta.</p></noscript>
        @error('latitude')<small class="field-error">{{ $message }}</small>@enderror
        @error('longitude')<small class="field-error">{{ $message }}</small>@enderror
    </div>

    <fieldset class="field">
        <legend>Kategori masalah <small>(boleh lebih dari satu)</small></legend>
        <div class="choice-grid">
            @forelse($categories as $category)
                <label class="choice-card">
                    <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked($selectedCategories->contains($category->id))>
                    <span><strong>{{ $category->name }}</strong><small>{{ $category->description }}</small></span>
                </label>
            @empty
                <p class="form-hint">Belum ada kategori. Hubungi admin sebelum membuat laporan.</p>
            @endforelse
        </div>
        @error('category_ids')<small class="field-error">{{ $message }}</small>@enderror
    </fieldset>

    <label class="field">
        <span>{{ isset($report) ? 'Ganti foto bukti (opsional)' : 'Foto bukti' }}</span>
        <span class="upload-box"><b>↑</b><span><strong>Pilih foto dari perangkat</strong><small>JPG, PNG, atau WebP · maksimal 2 MB · 8 megapiksel</small></span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" @required(!isset($report))></span>
        @isset($report)<small>Biarkan kosong untuk mempertahankan foto saat ini.</small>@endisset
        @error('photo')<small class="field-error">{{ $message }}</small>@enderror
    </label>

    @isset($captchaQuestion)
        <div class="security-panel">
            <div><span class="security-icon" aria-hidden="true">✓</span><div><strong>Verifikasi keamanan</strong><p>Jawab pertanyaan sederhana untuk mencegah laporan otomatis.</p></div></div>
            <label class="field captcha-field"><span>Berapa hasil {{ $captchaQuestion }}?</span><input type="number" name="captcha_answer" required inputmode="numeric" autocomplete="off" placeholder="Jawaban"></label>
            @error('captcha_answer')<small class="field-error">{{ $message }}</small>@enderror
        </div>

        <label class="check-line duplicate-confirmation">
            <input type="checkbox" name="duplicate_confirmation" value="1" @checked(old('duplicate_confirmation'))>
            <span>Laporan ini bukan pengulangan dari masalah aktif yang sama di sekitar titik tersebut.</span>
        </label>
        <small class="form-hint">Sistem akan memeriksa laporan aktif dengan kategori sama dalam radius 150 meter selama 30 hari terakhir.</small>
        @error('duplicate_confirmation')<small class="field-error">{{ $message }}</small>@enderror
    @endisset
</div>
