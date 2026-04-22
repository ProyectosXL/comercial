<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Solicitudes de Transferencias'; ?> - XL</title>
    <meta name="description" content="Sistema de gestión de solicitudes de transferencias - XL Extra Large">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --primary-bg: #eef2ff;
            --success: #10b981;
            --success-dark: #059669;
            --success-bg: #d1fae5;
            --warning: #f59e0b;
            --warning-dark: #d97706;
            --warning-bg: #fef3c7;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --danger-bg: #fee2e2;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --radius: 12px;
            --shadow: 0 1px 3px 0 rgba(0,0,0,.1), 0 1px 2px -1px rgba(0,0,0,.1);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -4px rgba(0,0,0,.1);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,.1), 0 8px 10px -6px rgba(0,0,0,.1);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        /* ===== NAVBAR ===== */
        .app-navbar {
            background: linear-gradient(135deg, var(--gray-900) 0%, #1a1a2e 50%, #16213e 100%);
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0,0,0,.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .app-navbar .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-weight: 700;
            font-size: 16px;
            text-decoration: none;
            letter-spacing: -0.3px;
        }

        .app-navbar .brand .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(99,102,241,.4);
        }

        .app-navbar .nav-links {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .app-navbar .nav-link {
            color: rgba(255,255,255,.65);
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all .2s;
        }

        .app-navbar .nav-link:hover,
        .app-navbar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.1);
        }

        .app-navbar .user-badge {
            color: rgba(255,255,255,.8);
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .app-navbar .user-badge .avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            padding: 28px 32px;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }

        /* ===== CARDS ===== */
        .card-modern {
            background: #fff;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: box-shadow .2s, transform .2s;
        }

        .card-modern:hover {
            box-shadow: var(--shadow-md);
        }

        .card-modern .card-header-modern {
            padding: 20px 24px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
        }

        .card-modern .card-header-modern h2 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: var(--gray-900);
            letter-spacing: -0.3px;
        }

        .card-modern .card-body-modern {
            padding: 24px;
        }

        /* ===== BUTTONS ===== */
        .btn-modern {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
            line-height: 1.2;
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(99,102,241,.35);
        }
        .btn-primary-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(99,102,241,.45);
            color: #fff;
            text-decoration: none;
        }

        .btn-success-modern {
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(16,185,129,.35);
        }
        .btn-success-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(16,185,129,.45);
            color: #fff;
            text-decoration: none;
        }

        .btn-warning-modern {
            background: linear-gradient(135deg, var(--warning) 0%, var(--warning-dark) 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(245,158,11,.35);
        }

        .btn-danger-modern {
            background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(239,68,68,.35);
        }
        .btn-danger-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(239,68,68,.45);
            color: #fff;
            text-decoration: none;
        }

        .btn-outline-modern {
            background: #fff;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }
        .btn-outline-modern:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
            text-decoration: none;
            color: var(--gray-700);
        }

        .btn-sm-modern {
            padding: 6px 14px;
            font-size: 12px;
            border-radius: 8px;
        }

        /* ===== FORMS ===== */
        .form-group-modern {
            margin-bottom: 20px;
        }

        .form-group-modern label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
            letter-spacing: -0.1px;
        }

        .form-control-modern {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--gray-200);
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: var(--gray-800);
            background: #fff;
            transition: all .2s;
            outline: none;
        }

        .form-control-modern:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,.15);
        }

        .form-control-modern::placeholder {
            color: var(--gray-400);
        }

        select.form-control-modern {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 36px;
        }

        textarea.form-control-modern {
            resize: vertical;
            min-height: 80px;
        }

        /* ===== BADGES ===== */
        .badge-modern {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .badge-pendiente {
            background: var(--warning-bg);
            color: #92400e;
        }

        .badge-finalizada {
            background: var(--success-bg);
            color: #065f46;
        }

        /* ===== TABLE ===== */
        .table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-modern thead th {
            background: var(--gray-50);
            border-bottom: 2px solid var(--gray-200);
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-modern tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--gray-100);
            font-size: 13px;
            vertical-align: middle;
        }

        .table-modern tbody tr {
            transition: background .15s;
        }

        .table-modern tbody tr:hover {
            background: var(--gray-50);
        }

        /* ===== AUTOCOMPLETE ===== */
        .autocomplete-wrapper {
            position: relative;
        }

        .autocomplete-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            box-shadow: var(--shadow-lg);
            max-height: 240px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            margin-top: 4px;
        }

        .autocomplete-list.show {
            display: block;
            animation: slideDown .2s ease;
        }

        .autocomplete-item {
            padding: 10px 14px;
            cursor: pointer;
            transition: background .15s;
            border-bottom: 1px solid var(--gray-100);
            font-size: 13px;
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-item:hover {
            background: var(--primary-bg);
        }

        .autocomplete-item .item-code {
            font-weight: 700;
            color: var(--primary);
            font-size: 12px;
        }

        .autocomplete-item .item-desc {
            color: var(--gray-700);
            margin-top: 2px;
        }

        .autocomplete-item .item-rubro {
            color: var(--gray-400);
            font-size: 11px;
        }

        /* ===== ARTICLE TABLE ===== */
        .article-row {
            animation: fadeIn .3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== PAGE HEADER ===== */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-900);
            margin: 0;
            letter-spacing: -0.5px;
        }

        .page-header .breadcrumb-modern {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--gray-400);
        }

        .page-header .breadcrumb-modern a {
            color: var(--primary);
            text-decoration: none;
        }

        /* ===== STATS CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #fff;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow);
            transition: all .2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-card .stat-info .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1;
        }

        .stat-card .stat-info .stat-label {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 500;
            margin-top: 4px;
        }

        /* ===== TOAST ===== */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .toast-modern {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--shadow-xl);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 320px;
            max-width: 440px;
            animation: slideInRight .3s ease;
            border-left: 4px solid transparent;
        }

        .toast-modern.toast-success { border-left-color: var(--success); }
        .toast-modern.toast-error { border-left-color: var(--danger); }
        .toast-modern.toast-warning { border-left-color: var(--warning); }

        .toast-modern .toast-icon {
            font-size: 18px;
            flex-shrink: 0;
        }

        .toast-modern .toast-message {
            font-size: 13px;
            color: var(--gray-700);
            flex: 1;
        }

        .toast-modern .toast-close {
            cursor: pointer;
            color: var(--gray-400);
            font-size: 14px;
            padding: 4px;
            transition: color .2s;
        }
        .toast-modern .toast-close:hover { color: var(--gray-600); }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(40px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* ===== MODAL ===== */
        .modal-backdrop-modern {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,.5);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            animation: fadeIn .2s ease;
        }

        .modal-backdrop-modern.show {
            display: flex;
        }

        .modal-modern {
            background: #fff;
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 480px;
            animation: slideDown .3s ease;
        }

        .modal-modern .modal-header-modern {
            padding: 20px 24px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-modern .modal-header-modern h3 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        .modal-modern .modal-body-modern {
            padding: 24px;
        }

        .modal-modern .modal-footer-modern {
            padding: 16px 24px;
            border-top: 1px solid var(--gray-100);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        /* ===== LOADING SPINNER ===== */
        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ===== STOCK INDICATOR ===== */
        .stock-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .stock-ok { background: var(--success-bg); color: #065f46; }
        .stock-low { background: var(--warning-bg); color: #92400e; }
        .stock-none { background: var(--danger-bg); color: #991b1b; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .main-content { padding: 16px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        /* ===== FOOTER ===== */
        .app-footer {
            background: #fff;
            border-top: 1px solid var(--gray-200);
            padding: 16px;
            text-align: center;
        }

        .app-footer p {
            color: var(--gray-400);
            font-size: 12px;
            margin: 0;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
        }

        .empty-state .empty-icon {
            font-size: 48px;
            color: var(--gray-300);
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 13px;
            color: var(--gray-400);
        }

        /* DataTables overrides */
        .dataTables_wrapper .dataTables_filter input {
            border: 1.5px solid var(--gray-200);
            border-radius: 8px;
            padding: 6px 12px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1.5px solid var(--gray-200);
            border-radius: 6px;
            padding: 4px 8px;
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body>

<nav class="app-navbar">
    <a href="<?php echo (isset($isLogistica) && $isLogistica) ? '/comercial/SolicitudesTransferencias/public/logistica' : '/comercial/SolicitudesTransferencias/'; ?>" class="brand">
        <span class="brand-icon"><i class="fas fa-exchange-alt"></i></span>
        Solicitudes de Transferencias
    </a>
    <div class="nav-links">
        <?php if (!isset($isLogistica) || !$isLogistica): ?>
            <a href="/comercial/SolicitudesTransferencias/" class="nav-link"><i class="fas fa-home mr-1"></i>Inicio</a>
            <a href="/comercial/SolicitudesTransferencias/public/solicitud" class="nav-link"><i class="fas fa-list mr-1"></i>Solicitudes</a>
            <a href="/comercial/SolicitudesTransferencias/public/solicitud/crear" class="nav-link"><i class="fas fa-plus mr-1"></i>Nueva</a>
            <a href="/comercial/SolicitudesTransferencias/public/config" class="nav-link"><i class="fas fa-cog mr-1"></i>Configuración</a>
        <?php else: ?>
            <a href="/comercial/SolicitudesTransferencias/public/logistica" class="nav-link"><i class="fas fa-list mr-1"></i>Gestión Logística</a>
        <?php endif; ?>
    </div>
</nav>

<main class="main-content">
<div id="toast-container" class="toast-container"></div>
