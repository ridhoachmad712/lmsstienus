@extends('layouts.guest')

@section('title', 'Pilih Sistem')
@section('body-class', 'portal-body')
@section('container-class', 'container-xl portal-container')

@push('styles')
<style>
    .portal-body {
        --portal-ink: #17243a;
        --portal-muted: #5f6f84;
        min-height: 100vh;
        background:
            radial-gradient(circle at 10% 12%, rgba(var(--tblr-primary-rgb), .13), transparent 29rem),
            radial-gradient(circle at 90% 88%, rgba(47, 179, 68, .11), transparent 28rem),
            linear-gradient(145deg, #f7faff 0%, #f3f7fb 48%, #f8fbf8 100%);
        color: var(--portal-ink);
    }
    .portal-body::before,
    .portal-body::after {
        content: "";
        position: fixed;
        z-index: 0;
        border-radius: 999px;
        pointer-events: none;
        filter: blur(2px);
    }
    .portal-body::before {
        width: 16rem;
        height: 16rem;
        top: -8rem;
        right: 12%;
        border: 1px solid rgba(var(--tblr-primary-rgb), .16);
    }
    .portal-body::after {
        width: 10rem;
        height: 10rem;
        bottom: -5rem;
        left: 8%;
        border: 1px solid rgba(47, 179, 68, .16);
    }
    .portal-body .page,
    .portal-body .portal-container {
        position: relative;
        z-index: 1;
    }
    .portal-body .page-center {
        justify-content: flex-start;
    }
    .portal-container {
        max-width: 1120px;
    }
    .portal-body .guest-brand {
        margin-bottom: 1.5rem !important;
    }
    .portal-body .guest-brand img {
        filter: drop-shadow(0 8px 16px rgba(23, 36, 58, .08));
    }
    .portal-hero {
        max-width: 780px;
        margin: 0 auto 2rem;
        text-align: center;
    }
    .portal-kicker {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        margin-bottom: .85rem;
        padding: .42rem .8rem;
        border: 1px solid rgba(var(--tblr-primary-rgb), .18);
        border-radius: 999px;
        background: rgba(255, 255, 255, .78);
        color: var(--tblr-primary);
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        box-shadow: 0 8px 24px rgba(23, 36, 58, .05);
        backdrop-filter: blur(10px);
    }
    .portal-title {
        margin: 0;
        font-size: clamp(2rem, 5vw, 3.35rem);
        font-weight: 800;
        line-height: 1.08;
        letter-spacing: -.045em;
        color: var(--portal-ink);
    }
    .portal-title span {
        color: var(--tblr-primary);
    }
    .portal-subtitle {
        max-width: 650px;
        margin: 1rem auto 0;
        color: var(--portal-muted);
        font-size: clamp(1rem, 2vw, 1.1rem);
        line-height: 1.65;
    }
    .portal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.25rem;
    }
    .system-card {
        --system-color: 32, 107, 196;
        position: relative;
        display: flex;
        min-height: 100%;
        overflow: hidden;
        border: 1px solid rgba(var(--system-color), .17);
        border-radius: 1.5rem;
        background: rgba(255, 255, 255, .9);
        color: var(--portal-ink);
        box-shadow: 0 18px 55px rgba(23, 36, 58, .09);
        text-decoration: none;
        isolation: isolate;
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        backdrop-filter: blur(12px);
    }
    .system-card::before {
        content: "";
        position: absolute;
        z-index: -1;
        width: 14rem;
        height: 14rem;
        top: -8rem;
        right: -5rem;
        border-radius: 50%;
        background: rgba(var(--system-color), .1);
        transition: transform .3s ease;
    }
    .system-card:hover,
    .system-card:focus-visible {
        color: var(--portal-ink);
        border-color: rgba(var(--system-color), .42);
        box-shadow: 0 24px 70px rgba(23, 36, 58, .15);
        transform: translateY(-5px);
        outline: none;
    }
    .system-card:hover::before,
    .system-card:focus-visible::before {
        transform: scale(1.18);
    }
    .system-card--lms {
        --system-color: 47, 179, 68;
    }
    .system-card__body {
        display: flex;
        flex: 1;
        flex-direction: column;
        padding: clamp(1.35rem, 4vw, 2rem);
    }
    .system-card__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.4rem;
    }
    .system-card__icon {
        display: grid;
        width: 4.2rem;
        height: 4.2rem;
        place-items: center;
        border: 1px solid rgba(var(--system-color), .14);
        border-radius: 1.2rem;
        background: rgba(var(--system-color), .1);
        color: rgb(var(--system-color));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .85);
    }
    .system-card__icon i {
        font-size: 2rem;
    }
    .system-card__label {
        padding: .36rem .62rem;
        border-radius: 999px;
        background: rgba(var(--system-color), .1);
        color: rgb(var(--system-color));
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .system-card__eyebrow {
        margin-bottom: .35rem;
        color: rgb(var(--system-color));
        font-size: .8rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
    }
    .system-card__title {
        margin: 0 0 .65rem;
        font-size: clamp(1.7rem, 3vw, 2.15rem);
        font-weight: 800;
        letter-spacing: -.035em;
    }
    .system-card__description {
        margin: 0;
        color: var(--portal-muted);
        line-height: 1.6;
    }
    .system-card__features {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin: 1.35rem 0 1.6rem;
    }
    .system-card__feature {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .4rem .62rem;
        border: 1px solid rgba(var(--system-color), .12);
        border-radius: .65rem;
        background: rgba(var(--system-color), .055);
        color: #44546a;
        font-size: .78rem;
        font-weight: 600;
    }
    .system-card__feature i {
        color: rgb(var(--system-color));
    }
    .system-card__action {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: auto;
        padding-top: 1rem;
        border-top: 1px solid rgba(98, 105, 118, .12);
        color: rgb(var(--system-color));
        font-weight: 700;
    }
    .system-card__arrow {
        display: grid;
        width: 2.2rem;
        height: 2.2rem;
        place-items: center;
        border-radius: 50%;
        background: rgb(var(--system-color));
        color: #fff;
        transition: transform .25s ease;
    }
    .system-card:hover .system-card__arrow,
    .system-card:focus-visible .system-card__arrow {
        transform: translateX(3px);
    }
    .portal-assurance {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .7rem;
        margin-top: 1.5rem;
        color: var(--portal-muted);
        font-size: .82rem;
        text-align: center;
    }
    .portal-assurance__icon {
        display: grid;
        flex: 0 0 auto;
        width: 2rem;
        height: 2rem;
        place-items: center;
        border-radius: 50%;
        background: rgba(var(--tblr-primary-rgb), .08);
        color: var(--tblr-primary);
    }
    .portal-body .text-secondary.mt-3.small {
        margin-top: 1.5rem !important;
        color: #77869a !important;
    }
    @media (max-width: 767.98px) {
        .portal-container {
            padding-right: 1rem;
            padding-left: 1rem;
        }
        .portal-body .guest-brand {
            margin-bottom: 1.1rem !important;
        }
        .portal-hero {
            margin-bottom: 1.5rem;
        }
        .portal-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .system-card__body {
            padding: 1.25rem;
        }
        .system-card__features {
            margin: 1.1rem 0 1.25rem;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .system-card,
        .system-card::before,
        .system-card__arrow {
            transition: none;
        }
    }
</style>
@endpush

@section('content')
<main>
    <header class="portal-hero">
        <div class="portal-kicker"><i class="ti ti-building-bank"></i> Portal Resmi STIE Nusantara Makassar</div>
        <h1 class="portal-title">Satu halaman untuk layanan <span>akademik</span> Anda</h1>
        <p class="portal-subtitle">Pilih layanan sesuai kebutuhan. Administrasi akademik dikelola melalui SIAKAD, sedangkan kegiatan pembelajaran berlangsung di LMS.</p>
    </header>

    <div class="portal-grid">
        <a href="{{ url('/siakad') }}" class="system-card system-card--siakad" aria-label="Buka Sistem Informasi Akademik">
            <div class="system-card__body">
                <div class="system-card__top">
                    <span class="system-card__icon"><i class="ti ti-building-bank"></i></span>
                    <span class="system-card__label">Administrasi</span>
                </div>
                <div class="system-card__eyebrow">Sistem Informasi Akademik</div>
                <h2 class="system-card__title">SIAKAD</h2>
                <p class="system-card__description">Kelola seluruh kebutuhan administrasi akademik dan pantau perkembangan studi Anda.</p>
                <div class="system-card__features" aria-label="Fitur SIAKAD">
                    <span class="system-card__feature"><i class="ti ti-check"></i>KRS &amp; Jadwal</span>
                    <span class="system-card__feature"><i class="ti ti-check"></i>Nilai &amp; KHS</span>
                    <span class="system-card__feature"><i class="ti ti-check"></i>Transkrip</span>
                </div>
                <div class="system-card__action">
                    <span>Masuk ke SIAKAD</span>
                    <span class="system-card__arrow"><i class="ti ti-arrow-right"></i></span>
                </div>
            </div>
        </a>

        <a href="{{ route('portal.lms') }}" class="system-card system-card--lms" aria-label="Buka Learning Management System">
            <div class="system-card__body">
                <div class="system-card__top">
                    <span class="system-card__icon"><i class="ti ti-device-laptop"></i></span>
                    <span class="system-card__label">Pembelajaran</span>
                </div>
                <div class="system-card__eyebrow">Learning Management System</div>
                <h2 class="system-card__title">LMS</h2>
                <p class="system-card__description">Akses ruang kelas digital untuk mengikuti proses pembelajaran secara lebih terarah.</p>
                <div class="system-card__features" aria-label="Fitur LMS">
                    <span class="system-card__feature"><i class="ti ti-check"></i>Materi &amp; Kelas</span>
                    <span class="system-card__feature"><i class="ti ti-check"></i>Tugas &amp; Kuis</span>
                    <span class="system-card__feature"><i class="ti ti-check"></i>Presensi</span>
                </div>
                <div class="system-card__action">
                    <span>Masuk ke LMS</span>
                    <span class="system-card__arrow"><i class="ti ti-arrow-right"></i></span>
                </div>
            </div>
        </a>
    </div>

    <div class="portal-assurance">
        <span class="portal-assurance__icon"><i class="ti ti-shield-check"></i></span>
        <span>SIAKAD dan LMS menggunakan akun, sesi, dan database yang terpisah.</span>
    </div>
</main>
@endsection
