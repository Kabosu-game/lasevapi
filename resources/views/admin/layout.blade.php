<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Admin') - LASEV</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #265533;
            --primary-light: #4CAF50;
            --primary-dark: #1B5E20;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px 0;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h4 {
            margin: 0;
            color: white;
            font-weight: 600;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--primary-light);
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            min-height: 100vh;
        }

        .content-header {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .content-header h1 {
            margin: 0;
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 600;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 14px;
            margin: 0;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .table {
            background: white;
        }

        .table thead {
            background-color: var(--primary-color);
            color: white;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        /* Styles pour la pagination Bootstrap 5 */
        .pagination {
            margin-bottom: 0;
        }

        .pagination .page-link {
            color: var(--primary-color);
            border-color: #dee2e6;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .pagination .page-link:hover {
            color: var(--primary-dark);
            background-color: #e9ecef;
            border-color: #dee2e6;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
            border-color: #dee2e6;
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-flower1"></i> LASEV Admin</h4>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.meditations.index') }}" class="{{ request()->routeIs('admin.meditations.*') ? 'active' : '' }}">
                    <i class="bi bi-music-note-list"></i>
                    <span>Méditations</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.affirmations.index') }}" class="{{ request()->routeIs('admin.affirmations.*') ? 'active' : '' }}">
                    <i class="bi bi-quote"></i>
                    <span>Affirmations</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.affirmation-categories.index') }}" class="{{ request()->routeIs('admin.affirmation-categories.*') ? 'active' : '' }}">
                    <i class="bi bi-tags"></i>
                    <span>Catégories affirmations</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.events.index') }}" class="{{ request()->routeIs('admin.events.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-event"></i>
                    <span>Événements</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.blogs.index') }}" class="{{ request()->routeIs('admin.blogs.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i>
                    <span>Blogs</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i>
                    <span>Utilisateurs</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.retreat-plans.index') }}" class="{{ request()->routeIs('admin.retreat-plans.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-check"></i>
                    <span>Plans de retraite</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.payments.index') }}" class="{{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                    <i class="bi bi-credit-card"></i>
                    <span>Paiements</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.dishes.index') }}" class="{{ request()->routeIs('admin.dishes.*') ? 'active' : '' }}">
                    <i class="bi bi-egg-fried"></i>
                    <span>Plats (Cuisine)</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.chefs.index') }}" class="{{ request()->routeIs('admin.chefs.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i>
                    <span>Chefs cuisiniers</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.food-comfort-forms.index') }}" class="{{ request()->routeIs('admin.food-comfort-forms.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-medical"></i>
                    <span>Formulaires Retraite</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.home-menu-items.index') }}" class="{{ request()->routeIs('admin.home-menu-items.*') ? 'active' : '' }}">
                    <i class="bi bi-grid-3x3-gap"></i>
                    <span>Menus page d'accueil</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.daily-quotes.index') }}" class="{{ request()->routeIs('admin.daily-quotes.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-heart"></i>
                    <span>Phrases du Jour</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.cms.index') }}" class="{{ request()->routeIs('admin.cms.*') ? 'active' : '' }}">
                    <i class="bi bi-gear-fill"></i>
                    <span>CMS</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.payment-settings.index') }}" class="{{ request()->routeIs('admin.payment-settings.*') ? 'active' : '' }}">
                    <i class="bi bi-credit-card-2-back"></i>
                    <span>Configuration Paiements</span>
                </a>
            </li>
            <li style="margin-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 10px;">
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-link text-white text-decoration-none w-100 text-start px-3 py-2" style="border: none; background: none;">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Déconnexion</span>
                    </button>
                </form>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>

