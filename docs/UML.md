# Analisis dan Perancangan Sistem LaporKita

Dokumen ini adalah sumber UML yang harus tetap sinkron dengan migration dan kode
Laravel. Diagram menggunakan Mermaid dan dirender otomatis oleh GitHub.

## 1. Use Case Diagram

```mermaid
flowchart LR
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
    end

    W --> UC1 & UC2 & UC3 & UC4 & UC7 & UC8
    UC4 -. include .-> UC5
    UC4 -. include .-> UC6
    P --> UC2 & UC3 & UC9 & UC10 & UC11 & UC12 & UC13
    A --> UC2 & UC3 & UC9 & UC10 & UC11 & UC12 & UC13 & UC14 & UC15
```

### Matriks hak akses

| Fitur | Warga | Petugas | Admin |
|---|:---:|:---:|:---:|
| Registrasi publik | Ya, selalu role warga | Tidak | Tidak |
| Membuat laporan | Ya | Tidak | Tidak |
| Melihat laporan | Milik sendiri | Semua | Semua |
| Edit/hapus laporan | Milik sendiri, hanya `diajukan` | Tidak | Tidak |
| Ubah status laporan | Tidak | Ya | Ya |
| Kelola kategori | Tidak | Tidak | Ya |
| Kelola pengguna/role | Tidak | Tidak | Ya |

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
        +varchar title
        +text description
        +varchar address
        +varchar photo_path
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
        +create() View
        +store(request) RedirectResponse
        +show(report) View
        +edit(report) View
        +update(request, report) RedirectResponse
        +destroy(report) RedirectResponse
    }

    class ReportStatusController {
        +update(request, report, action) RedirectResponse
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

    class TransitionReportStatus {
        +handle(report, nextStatus, officer, note) Report
    }

    User "1" --> "0..*" Report : reporter / user_id
    User "0..1" --> "0..*" Report : officer / officer_id
    Report "1" --> "1..*" CategoryReport : report_id
    Category "1" --> "0..*" CategoryReport : category_id
    Report "0..*" -- "0..*" Category : category_report
    ReportController ..> Report : manages
    ReportController ..> Category : reads
    ReportStatusController ..> TransitionReportStatus : invokes
    TransitionReportStatus ..> Report : transitions
    CategoryController ..> Category : manages
    UserController ..> User : manages
```

### Kecocokan migration dan tabel domain

| Tabel | Kolom migration | Constraint/index |
|---|---|---|
| `users` | `id`, `name`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at` | PK `id`, unique `email`, index `role` |
| `reports` | `id`, `user_id`, `officer_id`, `title`, `description`, `address`, `photo_path`, `status`, `officer_note`, empat timestamp proses, Laravel timestamps | FK reporter cascade, FK officer null-on-delete, index status dan pasangan user/status |
| `categories` | `id`, `name`, `slug`, `description`, Laravel timestamps | PK `id`, unique `name`, unique `slug` |
| `category_report` | `id`, `category_id`, `report_id`, Laravel timestamps | Dua FK cascade dan unique pasangan kategori/laporan |

Tabel framework `migrations`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`,
`jobs`, `job_batches`, dan `failed_jobs` berasal dari migration bawaan Laravel.
Tabel tersebut mendukung autentikasi/sesi, cache, dan antrean, tetapi bukan entitas
domain pada relasi bisnis di atas.

## 3. Activity Diagram — Proses Laporan Utama

```mermaid
flowchart TD
    A([Warga membuka form laporan]) --> B[Isi judul, deskripsi, alamat]
    B --> C[Pilih satu atau lebih kategori]
    C --> D[Unggah foto bukti]
    D --> E{Validasi server berhasil?}
    E -- Tidak --> F[Tampilkan pesan kesalahan]
    F --> B
    E -- Ya --> G[Simpan laporan berstatus diajukan]
    G --> H[Simpan relasi kategori pada pivot]
    H --> I[Tampilkan flash message berhasil]
    I --> J[Petugas membuka detail laporan]
    J --> K{Laporan valid?}
    K -- Tidak --> L[Isi alasan penolakan]
    L --> M[Status ditolak]
    M --> Z([Proses berakhir])
    K -- Ya --> N[Status diverifikasi]
    N --> O[Petugas mulai penanganan]
    O --> P[Status diproses]
    P --> Q{Penanganan selesai?}
    Q -- Belum --> O
    Q -- Ya --> R[Status selesai]
    R --> Z
```

Aturan gagal utama: input tidak valid kembali ke form dengan error; penolakan
tanpa catatan gagal validasi; lompatan status seperti `diajukan` langsung ke
`selesai` ditolak oleh `TransitionReportStatus`.

## 4. Sequence Diagram — Membuat dan Memproses Laporan

```mermaid
sequenceDiagram
    actor W as Warga
    participant R as Web Route
    participant RC as ReportController
    participant FR as StoreReportRequest
    participant FS as Public Storage
    participant RM as Report Model
    participant DB as Supabase PostgreSQL
    actor P as Petugas
    participant SC as ReportStatusController
    participant SR as UpdateReportStatusRequest
    participant TS as TransitionReportStatus

    W->>R: POST /reports + data + foto + kategori[]
    R->>FR: autentikasi, policy, validasi
    alt input tidak valid
        FR-->>W: redirect kembali + flash error
    else input valid
        FR->>RC: validated request
        RC->>FS: simpan foto bukti
        RC->>RM: buat laporan via relasi user
        RM->>DB: INSERT reports (status=diajukan)
        RC->>DB: INSERT category_report (satu atau lebih)
        DB-->>RC: commit transaksi
        RC-->>W: redirect detail + flash berhasil
    end

    P->>R: PATCH /reports/{report}/status
    R->>SR: autentikasi, policy, validasi
    SR->>SC: validated status dan catatan
    SC->>TS: handle(report, status, petugas, catatan)
    TS->>TS: periksa allowedTransitions()
    alt transisi tidak sah
        TS-->>P: validation error + status lama
    else transisi sah
        TS->>RM: set status, officer_id, timestamp
        RM->>DB: UPDATE reports
        DB-->>P: redirect detail + flash berhasil
    end
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
