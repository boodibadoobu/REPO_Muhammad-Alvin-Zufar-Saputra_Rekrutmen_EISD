# Analisis dan Perancangan Sistem LaporKita

Dokumen ini adalah sumber UML yang harus tetap sinkron dengan migration dan kode
Laravel. Diagram menggunakan Mermaid dan dirender otomatis oleh GitHub.

## 1. Use Case Diagram

```mermaid
flowchart LR
    V([Pengunjung Publik])
    W([Warga])
    P([Petugas])
    A([Admin])

    subgraph S[Platform LaporKita]
        UC1((Registrasi akun warga))
        UC2((Login dan logout))
        UC3((Melihat dashboard))
        UC4((Membuat laporan))
        UC5((Mengunggah foto bukti))
        UC6((Memilih banyak kategori))
        UC7((Melihat laporan sendiri))
        UC8((Mengedit atau menghapus\nlaporan berstatus diajukan))
        UC9((Melihat seluruh laporan))
        UC10((Memverifikasi laporan))
        UC11((Menolak laporan\ndengan catatan))
        UC12((Memproses laporan))
        UC13((Menyelesaikan laporan))
        UC14((Mengelola kategori))
        UC15((Mengelola pengguna\ndan role))
        UC16((Memilih titik lokasi\nGPS atau peta))
        UC17((Lolos CAPTCHA dan\npemeriksaan duplikat))
        UC18((Melihat dashboard\nlaporan publik))
        UC19((Melihat detail, progres,\ndan bukti penyelesaian))
        UC20((Mengunggah foto\nbukti penyelesaian))
    end

    V --> UC18 & UC19
    W --> UC1 & UC2 & UC3 & UC4 & UC7 & UC8
    UC4 -. include .-> UC5
    UC4 -. include .-> UC6
    UC4 -. include .-> UC16
    UC4 -. include .-> UC17
    W --> UC18 & UC19
    P --> UC2 & UC3 & UC9 & UC10 & UC11 & UC12 & UC13 & UC18 & UC19 & UC20
    A --> UC2 & UC3 & UC9 & UC10 & UC11 & UC12 & UC13 & UC14 & UC15 & UC18 & UC19 & UC20
```

### Matriks hak akses

| Fitur | Publik | Warga | Petugas | Admin |
|---|:---:|:---:|:---:|:---:|
| Registrasi publik | Ya, menjadi warga | Ya, selalu role warga | Tidak | Tidak |
| Membuat laporan | Tidak | Ya, dengan peta dan CAPTCHA | Tidak | Tidak |
| Melihat dashboard publik | Ya | Ya | Ya | Ya |
| Melihat laporan internal | Tidak | Milik sendiri | Semua | Semua |
| Edit/hapus laporan | Tidak | Milik sendiri, hanya `diajukan` | Tidak | Tidak |
| Ubah status laporan | Tidak | Tidak | Ya | Ya |
| Unggah bukti penyelesaian | Tidak | Tidak | Ya | Ya |
| Kelola kategori | Tidak | Tidak | Tidak | Ya |
| Kelola pengguna/role | Tidak | Tidak | Tidak | Ya |

## 2. Class Diagram

```mermaid
classDiagram
    class User {
        +bigint id
        +varchar name
        +varchar email
        +timestamp? email_verified_at
        +varchar password
        +varchar role
        +varchar? remember_token
        +timestamps
        +reports() HasMany
        +handledReports() HasMany
        +hasRole(role) bool
        +hasAnyRole(roles) bool
    }

    class Report {
        +bigint id
        +bigint user_id
        +bigint? officer_id
        +varchar? demo_key
        +varchar title
        +text description
        +varchar address
        +decimal? latitude
        +decimal? longitude
        +varchar photo_path
        +varchar? resolution_photo_path
        +varchar status
        +text? officer_note
        +timestamp? verified_at
        +timestamp? processed_at
        +timestamp? resolved_at
        +timestamp? rejected_at
        +timestamps
        +reporter() BelongsTo
        +officer() BelongsTo
        +categories() BelongsToMany
    }

    class Category {
        +bigint id
        +varchar name
        +varchar slug
        +text? description
        +timestamps
        +reports() BelongsToMany
    }

    class CategoryReport {
        +bigint id
        +bigint category_id
        +bigint report_id
        +timestamps
    }

    class ReportController {
        +index(request) View
        +create(request) View
        +store(request, photos) RedirectResponse
        +show(report) View
        +edit(report) View
        +update(request, report, photos) RedirectResponse
        +destroy(report) RedirectResponse
    }

    class ReportStatusController {
        +update(request, report, transition, photos) RedirectResponse
    }

    class PublicReportController {
        +index(request) View
        +show(report) View
    }

    class CategoryController {
        +index() View
        +create() View
        +store(request) RedirectResponse
        +edit(category) View
        +update(request, category) RedirectResponse
        +destroy(category) RedirectResponse
    }

    class UserController {
        +index(request) View
        +edit(user) View
        +update(request, user) RedirectResponse
    }

    class StoreSanitizedPhoto {
        +handle(photo, directory, field) string
    }

    ReportController ..> StoreSanitizedPhoto : sanitizes uploads
    ReportStatusController ..> StoreSanitizedPhoto : sanitizes evidence

    class TransitionReportStatus {
        +handle(report, nextStatus, officer, note, resolutionPhotoPath) Report
    }

    class FindPotentialDuplicateReports {
        +handle(latitude, longitude, categoryIds) Collection
    }

    User "1" --> "0..*" Report : reporter / user_id
    User "0..1" --> "0..*" Report : officer / officer_id
    Report "1" --> "1..*" CategoryReport : report_id
    Category "1" --> "0..*" CategoryReport : category_id
    Report "0..*" -- "0..*" Category : category_report
    ReportController ..> Report : manages
    ReportController ..> Category : reads
    ReportController ..> FindPotentialDuplicateReports : validates duplicate
    PublicReportController ..> Report : publishes verified reports
    PublicReportController ..> Category : filters
    ReportStatusController ..> TransitionReportStatus : invokes
    TransitionReportStatus ..> Report : transitions
    CategoryController ..> Category : manages
    UserController ..> User : manages
```

### Kecocokan migration dan tabel domain

| Tabel | Kolom migration | Constraint/index |
|---|---|---|
| `users` | `id`, `name`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at` | PK `id`, unique `email`, index `role` |
| `reports` | `id`, `user_id`, `officer_id`, `title`, `description`, `address`, `latitude`, `longitude`, `photo_path`, `resolution_photo_path`, `demo_key`, `status`, `officer_note`, empat timestamp proses, Laravel timestamps | unique nullable demo_key (identitas seed), FK reporter cascade, FK officer null-on-delete, index status, pasangan user/status, officer/status, dan latitude/longitude |
| `categories` | `id`, `name`, `slug`, `description`, Laravel timestamps | PK `id`, unique `name`, unique `slug` |
| `category_report` | `id`, `category_id`, `report_id`, Laravel timestamps | Dua FK cascade dan unique pasangan kategori/laporan |

Tabel framework `migrations`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`,
`jobs`, `job_batches`, dan `failed_jobs` berasal dari migration bawaan Laravel.
Tabel tersebut mendukung autentikasi/sesi, cache, dan antrean, tetapi bukan entitas
domain pada relasi bisnis di atas.

## 3. Activity Diagram — Proses Laporan Utama

```mermaid
flowchart TD
    A([Warga membuka form laporan]) --> B[Isi judul, deskripsi, dan alamat]
    B --> C[Pilih titik melalui GPS, klik peta, atau geser pin]
    C --> D[Pilih satu atau lebih kategori dan unggah foto]
    D --> E[Jawab CAPTCHA]
    E --> F{Validasi server berhasil?}
    F -- Tidak --> G[Tampilkan pesan kesalahan]
    G --> B
    F -- Ya --> H{Ada laporan aktif serupa\ndalam radius 150 meter?}
    H -- Ya --> I{Warga mengonfirmasi\nbukan duplikat?}
    I -- Tidak --> J[Tampilkan peringatan dan ID kandidat]
    J --> B
    I -- Ya --> IMG[Betulkan orientasi dan hapus metadata foto]
    IMG --> OK{Pemrosesan foto berhasil?}
    OK -- Tidak --> G
    OK -- Ya --> K[Simpan laporan berstatus diajukan]
    H -- Tidak --> IMG
    K --> L[Simpan relasi kategori pada pivot]
    L --> M[Tampilkan flash message berhasil]
    M --> N[Petugas membuka detail laporan]
    N --> O{Laporan valid?}
    O -- Tidak --> P[Isi alasan penolakan]
    P --> Q[Status ditolak]
    Q --> Z([Proses berakhir])
    O -- Ya --> R[Status diverifikasi dan tampil di dashboard publik]
    R --> S[Petugas mulai penanganan]
    S --> T[Status diproses]
    T --> U{Penanganan selesai?}
    U -- Belum --> S
    U -- Ya --> V[Unggah foto bukti penyelesaian]
    V --> W{Foto valid dan metadata berhasil dibersihkan?}
    W -- Tidak --> X[Tampilkan pesan kesalahan]
    X --> V
    W -- Ya --> Y[Status selesai dan tiket ditutup]
    Y --> Z
```

Aturan gagal utama: input atau CAPTCHA tidak valid kembali ke form dengan error;
kandidat duplikat perlu konfirmasi eksplisit; penolakan tanpa catatan gagal
validasi; penyelesaian tanpa foto bukti gagal; lompatan status seperti
`diajukan` langsung ke `selesai` ditolak oleh request dan
`TransitionReportStatus`.

## 4. Sequence Diagram — Membuat dan Memproses Laporan

```mermaid
sequenceDiagram
    actor W as Warga
    participant B as Browser + Leaflet
    participant R as Web Route
    participant RC as ReportController
    participant FR as StoreReportRequest
    participant DD as FindPotentialDuplicateReports
    participant IM as StoreSanitizedPhoto
    participant FS as Public Storage
    participant RM as Report Model
    participant DB as Supabase PostgreSQL
    actor P as Petugas
    participant SC as ReportStatusController
    participant SR as UpdateReportStatusRequest
    participant TS as TransitionReportStatus
    actor V as Pengunjung Publik
    participant PC as PublicReportController

    W->>B: Pilih GPS, klik peta, atau geser pin
    B-->>W: Isi latitude dan longitude
    W->>R: POST /reports + data + koordinat + foto + kategori[] + CAPTCHA
    R->>FR: autentikasi, policy, CAPTCHA, validasi
    alt input tidak valid
        FR-->>W: redirect kembali + flash error
    else input valid
        FR->>DD: cari kategori sama dalam 150 m dan 30 hari
        alt duplikat potensial tanpa konfirmasi
            DD-->>W: redirect + ID kandidat + minta konfirmasi
        else tidak ada atau sudah dikonfirmasi
        FR->>RC: validated request
        RC->>IM: betulkan orientasi dan encode tanpa metadata
        IM->>FS: simpan hanya foto bersih
        RC->>RM: buat laporan via relasi user
        RM->>DB: INSERT reports + koordinat (status=diajukan)
        RC->>DB: INSERT category_report (satu atau lebih)
        DB-->>RC: commit transaksi
        RC-->>W: redirect detail + flash berhasil
        end
    end

    P->>R: PATCH /reports/{report}/status + catatan + foto penyelesaian
    R->>SR: autentikasi, policy, validasi status dan file
    SR->>SC: validated status, catatan, dan file
    opt target status selesai
        SC->>IM: betulkan orientasi dan encode tanpa metadata
        IM->>FS: simpan hanya bukti bersih
    end
    SC->>TS: handle(report, status, petugas, catatan, path bukti)
    TS->>TS: periksa allowedTransitions()
    alt transisi tidak sah
        TS-->>P: validation error + status lama
    else transisi sah
        TS->>RM: set status, officer_id, timestamp, bukti
        RM->>DB: UPDATE reports
        DB-->>P: redirect detail + flash berhasil
    end

    V->>R: GET /laporan-publik atau detail
    R->>PC: filter hanya diverifikasi, diproses, selesai
    PC->>DB: SELECT laporan, kategori, koordinat, progres, bukti
    DB-->>PC: data tanpa akun pelapor
    PC-->>V: peta, daftar, detail, timeline, bukti penyelesaian
```

## 5. State Diagram Status Laporan

```mermaid
stateDiagram-v2
    [*] --> Diajukan
    Diajukan --> Diverifikasi: laporan valid
    Diajukan --> Ditolak: laporan tidak valid + catatan
    Diverifikasi --> Diproses: penanganan dimulai
    Diproses --> Selesai: penanganan tuntas
    Selesai --> [*]
    Ditolak --> [*]
```

Pemrosesan foto yang gagal mengembalikan validation error sebelum transaksi
laporan/status. Foto original tidak disimpan permanen. Penggantian foto memakai
action yang sama; file lama dihapus hanya setelah pembaruan database berhasil.
Tidak ada perubahan schema untuk pembersihan metadata.
