@php
    $selectedCategories = collect(old('category_ids', isset($report) ? $report->categories->modelKeys() : []))->map(fn ($id) => (int) $id);
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
        <small>Minimal 20 karakter. Jangan mencantumkan data pribadi orang lain.</small>
        @error('description')<small class="field-error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Alamat atau lokasi</span>
        <textarea name="address" rows="3" required maxlength="500" placeholder="Nama jalan, RT/RW, kelurahan, dan patokan terdekat">{{ old('address', $report->address ?? '') }}</textarea>
        @error('address')<small class="field-error">{{ $message }}</small>@enderror
    </label>

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
        <span class="upload-box"><b>↑</b><span><strong>Pilih foto dari perangkat</strong><small>JPG, PNG, atau WebP · maksimal 2 MB</small></span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" @required(!isset($report))></span>
        @isset($report)<small>Biarkan kosong untuk mempertahankan foto saat ini.</small>@endisset
        @error('photo')<small class="field-error">{{ $message }}</small>@enderror
    </label>
</div>
