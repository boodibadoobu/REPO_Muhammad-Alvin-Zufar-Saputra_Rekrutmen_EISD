<div class="form-stack">
    <label class="field"><span>Nama kategori</span><input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required maxlength="100" placeholder="Contoh: Jalan Rusak">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
    <label class="field"><span>Deskripsi</span><textarea name="description" rows="4" maxlength="1000" placeholder="Jelaskan jenis masalah yang masuk kategori ini...">{{ old('description', $category->description ?? '') }}</textarea>@error('description')<small class="field-error">{{ $message }}</small>@enderror</label>
</div>
