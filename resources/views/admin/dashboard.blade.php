@push('head')
    @vite('resources/js/admin-dashboard.js')
    <script>
        window.adminDashboardData = {{ Illuminate\Support\Js::from([
            'expenses' => $expenseChart,
            'maintenance' => $maintenanceChart,
        ]) }};
    </script>
@endpush

@php
    $approvalStatusLabels = [
        'MENUNGGU' => ['label' => 'Menunggu', 'class' => 'text-bg-warning'],
        'DISETUJUI' => ['label' => 'Disetujui', 'class' => 'text-bg-success'],
        'DITOLAK' => ['label' => 'Ditolak', 'class' => 'text-bg-danger'],
        'DIBATALKAN' => ['label' => 'Dibatalkan', 'class' => 'text-bg-secondary'],
    ];
@endphp

<x-app-layout>
    <div class="admin-dashboard container-fluid px-3 px-lg-4 py-4 py-xl-5">
        <div class="admin-dashboard__container mx-auto">
            <header class="align-items-start align-items-md-center d-flex flex-column flex-md-row gap-3 justify-content-between mb-4">
                <div>
                    <p class="admin-dashboard__eyebrow mb-2">Fleet Management PLN</p>
                    <h1 class="admin-dashboard__title mb-1">Dashboard Admin</h1>
                    <p class="mb-2 text-secondary">Selamat datang, {{ $admin->name }}. Ringkasan armada dan aktivitas maintenance kendaraan.</p>
                    <p class="mb-0 small text-secondary">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
                </div>
                <div class="align-items-center d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('laporan-pengeluaran.index') }}">
                        Laporan pengeluaran
                    </a>
                    <a class="btn btn-primary" href="{{ route('kendaraan.create') }}">
                        Tambah kendaraan
                    </a>
                    <a aria-label="Profil pengguna" class="btn btn-light border" href="{{ route('profile.edit') }}">
                        {{ $admin->name }}
                    </a>
                </div>
            </header>

            <section aria-label="Statistik utama" class="mb-4">
                <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3">
                    <div class="col">
                        <article class="admin-dashboard__card h-100 p-3 p-lg-4">
                            <div class="align-items-center d-flex justify-content-between mb-3">
                                <span class="admin-dashboard__stat-label">Total kendaraan</span>
                                <span class="admin-dashboard__stat-icon bg-primary-subtle text-primary">
                                    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l1.5-4.5A2 2 0 018.4 5h7.2a2 2 0 011.9 1.5L19 11m-14 0h14m-14 0a2 2 0 00-2 2v4h18v-4a2 2 0 00-2-2m-12 6v2m10-2v2M7 14h.01M17 14h.01" />
                                    </svg>
                                </span>
                            </div>
                            <p class="admin-dashboard__stat-value mb-1">{{ number_format($stats['totalVehicles'], 0, ',', '.') }}</p>
                            <p class="mb-0 small text-secondary">Unit kendaraan terdaftar</p>
                        </article>
                    </div>

                    <div class="col">
                        <article class="admin-dashboard__card h-100 p-3 p-lg-4">
                            <div class="align-items-center d-flex justify-content-between mb-3">
                                <span class="admin-dashboard__stat-label">Maintenance aktif</span>
                                <span class="admin-dashboard__stat-icon bg-primary-subtle text-primary">
                                    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a4 4 0 00-5.4 5.4l-5.6 5.6a2 2 0 102.8 2.8l5.6-5.6a4 4 0 005.4-5.4l-2.3 2.3-2.8-2.8 2.3-2.3z" />
                                    </svg>
                                </span>
                            </div>
                            <p class="admin-dashboard__stat-value mb-1">{{ number_format($stats['activeMaintenance'], 0, ',', '.') }}</p>
                            <p class="mb-0 small text-secondary">Disetujui dan belum tercatat selesai</p>
                        </article>
                    </div>

                    <div class="col">
                        <article class="admin-dashboard__card h-100 p-3 p-lg-4">
                            <div class="align-items-center d-flex justify-content-between mb-3">
                                <span class="admin-dashboard__stat-label">Maintenance selesai</span>
                                <span class="admin-dashboard__stat-icon bg-success-subtle text-success">
                                    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13H4V6a1 1 0 011-1zm3 9l2 2 5-5" />
                                    </svg>
                                </span>
                            </div>
                            <p class="admin-dashboard__stat-value mb-1">{{ number_format($stats['completedMaintenance'], 0, ',', '.') }}</p>
                            <p class="mb-0 small text-secondary">Dalam 30 hari terakhir</p>
                        </article>
                    </div>

                    <div class="col">
                        <article class="admin-dashboard__card h-100 p-3 p-lg-4">
                            <div class="align-items-center d-flex justify-content-between mb-3">
                                <span class="admin-dashboard__stat-label">Service Reminder</span>
                                <span class="admin-dashboard__stat-icon bg-warning-subtle text-warning">
                                    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
                                    </svg>
                                </span>
                            </div>
                            <p class="admin-dashboard__stat-value mb-1">{{ number_format($stats['serviceReminders'], 0, ',', '.') }}</p>
                            <p class="mb-0 small text-secondary">Kendaraan mendekati atau melewati jadwal servis</p>
                        </article>
                    </div>
                </div>
            </section>

            <section aria-label="Grafik maintenance" class="mb-4">
                <div class="row g-3">
                    <div class="col-12 col-xl-6">
                        <article class="admin-dashboard__card h-100 p-3 p-lg-4">
                            <div class="mb-3">
                                <h2 class="admin-dashboard__card-heading mb-1">Tren biaya maintenance</h2>
                                <p class="mb-0 small text-secondary">Biaya servis dan perbaikan, enam bulan terakhir</p>
                            </div>
                            @if ($hasExpenseData)
                                <div class="admin-dashboard__chart">
                                    <canvas id="maintenance-expense-chart" aria-label="Grafik tren biaya maintenance per bulan" role="img"></canvas>
                                </div>
                            @else
                                <div class="admin-dashboard__empty-state align-items-center d-flex justify-content-center text-center">
                                    Belum ada data biaya maintenance pada periode ini.
                                </div>
                            @endif
                        </article>
                    </div>

                    <div class="col-12 col-xl-6">
                        <article class="admin-dashboard__card h-100 p-3 p-lg-4">
                            <div class="mb-3">
                                <h2 class="admin-dashboard__card-heading mb-1">Maintenance selesai</h2>
                                <p class="mb-0 small text-secondary">Jumlah berdasarkan tanggal servis</p>
                            </div>
                            @if (array_sum($maintenanceChart['values']) > 0)
                                <div class="admin-dashboard__chart">
                                    <canvas id="monthly-maintenance-chart" aria-label="Jumlah maintenance selesai per bulan" role="img"></canvas>
                                </div>
                            @else
                                <div class="admin-dashboard__empty-state align-items-center d-flex justify-content-center text-center">
                                    Belum ada riwayat servis pada periode ini.
                                </div>
                            @endif
                        </article>
                    </div>
                </div>
            </section>

            <section aria-label="Pengajuan dan aktivitas terbaru" class="row g-3">
                <div class="col-12 col-xl-8">
                    <article class="admin-dashboard__card h-100 overflow-hidden">
                        <div class="align-items-center d-flex flex-wrap gap-2 justify-content-between p-3 p-lg-4">
                            <div>
                                <h2 class="admin-dashboard__card-heading mb-1">Pengajuan maintenance terbaru</h2>
                                <p class="mb-0 small text-secondary">Maksimal lima pengajuan terakhir</p>
                            </div>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('maintenance.index') }}">Lihat semua</a>
                        </div>
                        <div class="table-responsive">
                            <table class="admin-dashboard__table table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 ps-lg-4" scope="col">Nomor</th>
                                        <th scope="col">Kendaraan</th>
                                        <th scope="col">Jenis</th>
                                        <th scope="col">Pengaju</th>
                                        <th scope="col">Tanggal</th>
                                        <th class="pe-3 pe-lg-4" scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($latestRequests as $requestItem)
                                        @php
                                            $status = $approvalStatusLabels[$requestItem->status_persetujuan] ?? [
                                                'label' => $requestItem->status_persetujuan,
                                                'class' => 'text-bg-secondary',
                                            ];
                                        @endphp
                                        <tr>
                                            <td class="ps-3 ps-lg-4">
                                                <a class="fw-semibold link-primary text-decoration-none" href="{{ route('maintenance.show', $requestItem) }}">
                                                    #{{ $requestItem->id }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="d-block fw-semibold">{{ $requestItem->kendaraan?->merk_tipe ?? '-' }}</span>
                                                <span class="small text-secondary">{{ $requestItem->kendaraan?->plat_nomor ?? '-' }}</span>
                                            </td>
                                            <td>{{ $requestItem->jenis_pengajuan === 'DARURAT' ? 'Darurat' : 'Rutin' }}</td>
                                            <td>{{ $requestItem->pengaju?->name ?? '-' }}</td>
                                            <td class="text-nowrap">{{ $requestItem->dibuat_pada?->translatedFormat('d M Y') ?? '-' }}</td>
                                            <td class="pe-3 pe-lg-4">
                                                <span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="admin-dashboard__empty-state text-center py-5" colspan="6">
                                                Belum ada pengajuan maintenance.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="d-flex flex-column gap-3 h-100">
                        <article class="admin-dashboard__card p-3 p-lg-4">
                            <div class="align-items-start d-flex justify-content-between mb-3">
                                <div>
                                    <h2 class="admin-dashboard__card-heading mb-1">Ringkasan persetujuan</h2>
                                    <p class="mb-0 small text-secondary">Status seluruh pengajuan</p>
                                </div>
                                <a aria-label="Buka daftar persetujuan" class="small link-primary text-decoration-none" href="{{ route('maintenance.index', ['tab' => 'pengajuan']) }}">Buka</a>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a class="align-items-center d-flex justify-content-between link-dark text-decoration-none" href="{{ route('maintenance.index', ['tab' => 'pengajuan']) }}">
                                    <span class="align-items-center d-flex gap-2">
                                        <span class="admin-dashboard__legend-dot bg-warning"></span>
                                        Menunggu
                                    </span>
                                    <strong>{{ number_format($approvalSummary['MENUNGGU'], 0, ',', '.') }}</strong>
                                </a>
                                <a class="align-items-center d-flex justify-content-between link-dark text-decoration-none" href="{{ route('maintenance.index', ['tab' => 'proses']) }}">
                                    <span class="align-items-center d-flex gap-2">
                                        <span class="admin-dashboard__legend-dot bg-success"></span>
                                        Disetujui
                                    </span>
                                    <strong>{{ number_format($approvalSummary['DISETUJUI'], 0, ',', '.') }}</strong>
                                </a>
                                <a class="align-items-center d-flex justify-content-between link-dark text-decoration-none" href="{{ route('maintenance.index', ['tab' => 'ditolak']) }}">
                                    <span class="align-items-center d-flex gap-2">
                                        <span class="admin-dashboard__legend-dot bg-danger"></span>
                                        Ditolak
                                    </span>
                                    <strong>{{ number_format($approvalSummary['DITOLAK'], 0, ',', '.') }}</strong>
                                </a>
                            </div>
                        </article>

                        <article class="admin-dashboard__card flex-grow-1 overflow-hidden" id="latest-notifications">
                            <div class="align-items-start d-flex justify-content-between p-3 p-lg-4">
                                <div>
                                    <h2 class="admin-dashboard__card-heading mb-1">Notifikasi terbaru</h2>
                                    <p class="mb-0 small text-secondary">Notifikasi untuk akun admin ini</p>
                                </div>
                                @if ($unreadNotificationsCount > 0)
                                    <span class="badge rounded-pill text-bg-primary">{{ number_format($unreadNotificationsCount, 0, ',', '.') }} belum dibaca</span>
                                @endif
                            </div>
                            @if ($latestNotifications->isEmpty())
                                <div class="admin-dashboard__empty-state align-items-center d-flex justify-content-center px-3 pb-4 text-center">
                                    Belum ada notifikasi.
                                </div>
                            @else
                                <ul class="list-unstyled mb-0 px-3 px-lg-4">
                                    @foreach ($latestNotifications as $notification)
                                        <li class="admin-dashboard__notification py-3">
                                            <div class="align-items-start d-flex gap-2 justify-content-between">
                                                <h3 class="mb-1 small fw-semibold">{{ $notification->judul }}</h3>
                                                @if (! $notification->sudah_dibaca)
                                                    <span class="admin-dashboard__legend-dot bg-primary mt-1" aria-label="Belum dibaca"></span>
                                                @endif
                                            </div>
                                            <p class="mb-1 small text-secondary">{{ $notification->pesan }}</p>
                                            <time class="small text-secondary" datetime="{{ $notification->dibuat_pada?->toIso8601String() }}">
                                                {{ $notification->dibuat_pada?->translatedFormat('d M Y, H:i') ?? '-' }}
                                            </time>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </article>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
