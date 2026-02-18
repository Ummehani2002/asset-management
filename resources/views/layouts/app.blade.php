<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="autocomplete" content="off">
    <meta name="format-detection" content="telephone=no">
    <title>Asset Management System - Tanseeq Investment</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Flatpickr – calendar for date inputs -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Tom Select – searchable dropdowns for employee/entity selects -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

    <style>
        :root {
            --primary: #1F2A44;       /* Navy Blue */
            --secondary: #C6A87D;     /* Beige / Gold */
            --hover: #2C3E66;
            --bg-light: #F7F8FA;
            --white: #FFFFFF;
            --text-dark: #1F2A44;
            --border-light: #E5E7EB;
            /* Sidebar: master one color, sub-menus different (darker) */
            --sidebar-bg: #0d2838;
            --sidebar-master-bg: rgba(30, 55, 70, 0.95);
            --sidebar-master-text: #c5e0e8;
            --sidebar-master-hover: rgba(50, 85, 105, 0.95);
            --sidebar-submenu-bg: rgba(8, 22, 32, 0.98);
            --sidebar-submenu-text: #b0d4dc;
            --sidebar-submenu-hover: rgba(25, 50, 65, 0.98);
            --sidebar-accent: #5ba3b8;
        }

        /* Disable browser autocomplete dropdowns - Aggressive */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px white inset !important;
            box-shadow: 0 0 0 30px white inset !important;
            -webkit-text-fill-color: #1F2A44 !important;
        }

        /* Hide autocomplete dropdown buttons */
        input::-webkit-contacts-auto-fill-button,
        input::-webkit-credentials-auto-fill-button,
        input::-webkit-strong-password-auto-fill-button {
            visibility: hidden !important;
            display: none !important;
            pointer-events: none !important;
            position: absolute !important;
            right: 0 !important;
            opacity: 0 !important;
            width: 0 !important;
            height: 0 !important;
        }

        /* Hide autocomplete suggestions */
        input::-webkit-list-button,
        input::-webkit-calendar-picker-indicator {
            display: none !important;
        }

        body {
            display: flex;
            min-height: 100vh;
            font-family: 'Inter', 'Roboto', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            font-size: 13px;
        }

        /* Sidebar: master items one color, sub-menus different (darker) – pattern like reference */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            padding-top: 20px;
            color: var(--sidebar-master-text);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1000;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(13, 40, 56, 0.5);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(168, 212, 224, 0.4);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(168, 212, 224, 0.7);
        }

        .sidebar h4 {
            color: var(--sidebar-master-text);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Master (top-level) items – one color */
        .sidebar .sidebar-nav-item,
        .sidebar .sidebar-nav-item:hover {
            color: var(--sidebar-master-text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            width: 100%;
            text-align: left;
            border: none;
            background: var(--sidebar-master-bg);
            transition: all 0.2s ease;
            font-size: 13px;
            border-radius: 0;
            border-left: 3px solid transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.15);
        }

        .sidebar .sidebar-nav-item:hover,
        .sidebar .sidebar-dropdown-toggle:hover {
            background: var(--sidebar-master-hover) !important;
            color: #ffffff !important;
        }

        .sidebar .sidebar-nav-item.active,
        .sidebar a.sidebar-nav-item.active {
            background: var(--sidebar-master-hover) !important;
            color: #ffffff !important;
            border-left-color: var(--sidebar-accent);
        }

        .sidebar .sidebar-dropdown-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 12px 20px;
            color: var(--sidebar-master-text);
            background: var(--sidebar-master-bg);
            border: none;
            border-bottom: 1px solid rgba(0, 0, 0, 0.15);
            text-align: left;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .sidebar .sidebar-dropdown-toggle .chevron {
            transition: transform 0.2s ease;
            font-size: 10px;
            opacity: 0.8;
        }

        .sidebar .sidebar-dropdown:hover .sidebar-dropdown-toggle .chevron {
            transform: rotate(180deg);
        }

        .sidebar .sidebar-dropdown {
            position: relative;
        }

        /* Sub-menu – different (darker) color, clearly nested */
        .sidebar .sidebar-dropdown-menu {
            display: none !important;
            background: var(--sidebar-submenu-bg);
            margin: 0;
            padding: 0;
            list-style: none;
            border-left: 3px solid rgba(91, 163, 184, 0.5);
            margin-left: 20px;
        }

        .sidebar .sidebar-dropdown:hover .sidebar-dropdown-menu {
            display: block !important;
        }

        .sidebar .sidebar-dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px 10px 20px;
            font-size: 12px;
            color: var(--sidebar-submenu-text) !important;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none !important;
            border-radius: 0;
            margin: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .sidebar .sidebar-dropdown-menu a:last-child {
            border-bottom: none;
        }

        .sidebar .sidebar-dropdown-menu a:hover {
            background: var(--sidebar-submenu-hover) !important;
            color: #ffffff !important;
        }

        .sidebar .sidebar-dropdown-menu a.active {
            background: var(--sidebar-submenu-hover) !important;
            color: #ffffff !important;
            border-left: 3px solid var(--sidebar-accent);
        }

        /* Plain links – master style */
        .sidebar .btn-outline-primary,
        .sidebar .btn-outline-light,
        .sidebar a.btn-outline-primary,
        .sidebar a.btn-outline-light {
            border: none !important;
            border-left: 3px solid transparent !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.15);
            color: var(--sidebar-master-text) !important;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            text-align: left;
            background: var(--sidebar-master-bg);
        }

        .sidebar .btn-outline-primary:hover,
        .sidebar .btn-outline-light:hover,
        .sidebar a.btn-outline-primary:hover,
        .sidebar a.btn-outline-light:hover {
            background: var(--sidebar-master-hover) !important;
            color: #ffffff !important;
        }

        .sidebar .btn-outline-danger:hover {
            background: rgba(220, 53, 69, 0.2) !important;
            color: #fff0f0 !important;
        }

        .sidebar .btn-outline-danger {
            border: none !important;
            color: #dc3545 !important;
        }

        .sidebar .btn-outline-danger:hover {
            border: none !important;
        }

        /* Content */
        .content {
            flex-grow: 1;
            padding: 24px;
            background-color: var(--bg-light);
            min-height: 100vh;
            margin-left: 260px; /* Account for fixed sidebar width */
        }

        /* Print: hide sidebar and nav, show only main content */
        @media print {
            .sidebar, .sidebar *, nav, .btn-print-hide, .no-print { display: none !important; }
            .content { margin-left: 0 !important; width: 100% !important; }
            body { background: #fff !important; }
            .master-page, .container, .container-fluid { max-width: 100% !important; }
            /* When printing only the "Set budget" form: hide everything except that form */
            body.printing-budget-form .page-header,
            body.printing-budget-form .alert,
            body.printing-budget-form .master-form-card:not(.budget-form-printable),
            body.printing-budget-form .master-table-card { display: none !important; }
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--white);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--hover);
            border-color: var(--hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(31, 42, 68, 0.2);
        }

        .btn-success {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: var(--primary);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background-color: #B8966A;
            border-color: #B8966A;
            color: var(--white);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(198, 168, 125, 0.3);
        }

        .btn-outline-primary {
            border-color: var(--secondary);
            color: var(--secondary);
            background-color: transparent;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background-color: var(--secondary);
            color: var(--primary);
            border-color: var(--secondary);
            transform: translateY(-1px);
        }

        .btn-outline-light {
            border-color: rgba(255,255,255,0.4);
            color: #fff;
            background-color: transparent;
            transition: all 0.3s ease;
        }

        .btn-outline-light:hover {
            background-color: var(--secondary);
            color: var(--primary);
            border-color: var(--secondary);
            transform: translateY(-1px);
        }

        /* Tables */
        table {
            background-color: var(--white);
            border-radius: 8px;
            overflow: hidden;
        }

        table thead {
            background-color: var(--primary);
            color: #fff;
        }

        table tbody tr:nth-child(even) {
            background-color: #F1F3F5;
        }

        table tbody tr:hover {
            background-color: #EFE7D8;
        }

        /* Cards */
        .card {
            border: 1px solid var(--border-light);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            background-color: var(--white);
        }

        .card-header {
            background-color: var(--primary);
            color: var(--white);
            border-bottom: 2px solid var(--secondary);
        }

        .card-header.bg-transparent {
            background-color: transparent !important;
            color: var(--primary);
            border-bottom: 1px solid var(--border-light);
        }

        /* Forms */
        .form-control {
            border: 1px solid var(--border-light);
            border-radius: 6px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.2rem rgba(198, 168, 125, 0.25);
            outline: none;
        }

        label {
            color: var(--text-dark);
            font-weight: 500;
            margin-bottom: 8px;
        }

        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 14px 18px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert i {
            font-size: 18px;
            flex-shrink: 0;
        }

        .alert-success {
            background: linear-gradient(135deg, #E8F5E9 0%, #D4EDDA 100%);
            color: #0F5132;
            border-left: 4px solid #28A745;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.15);
        }

        .alert-success i {
            color: #28A745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #FFEBEE 0%, #F8D7DA 100%);
            color: #721C24;
            border-left: 4px solid #DC3545;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.15);
        }

        .alert-danger i {
            color: #DC3545;
        }

        .alert-warning {
            background: linear-gradient(135deg, #FFF8E1 0%, #FFF3CD 100%);
            color: #856404;
            border-left: 4px solid var(--secondary);
            box-shadow: 0 2px 8px rgba(198, 168, 125, 0.2);
        }

        .alert-warning i {
            color: var(--secondary);
        }

        .alert-info {
            background: linear-gradient(135deg, #E3F2FD 0%, #D1ECF1 100%);
            color: #084298;
            border-left: 4px solid var(--primary);
            box-shadow: 0 2px 8px rgba(31, 42, 68, 0.15);
        }

        .alert-info i {
            color: var(--primary);
        }

        .alert-dismissible .btn-close {
            padding: 0.5rem 0.75rem;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .alert-dismissible .btn-close:hover {
            opacity: 1;
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }

        .badge.bg-secondary {
            background-color: var(--secondary) !important;
            color: var(--primary);
        }

        /* Headings */
        h1, h2, h3, h4, h5 {
            font-weight: 600;
            color: var(--primary);
        }

        /* Links */
        a {
            color: var(--secondary);
            transition: color 0.3s ease;
        }

        a:hover {
            color: var(--primary);
        }

        /* Bootstrap Color Overrides */
        .text-primary {
            color: var(--primary) !important;
        }

        .bg-primary {
            background-color: var(--primary) !important;
        }

        .border-primary {
            border-color: var(--primary) !important;
        }

        .text-secondary {
            color: var(--secondary) !important;
        }

        .bg-secondary {
            background-color: var(--secondary) !important;
            color: var(--primary) !important;
        }

        .text-muted {
            color: #6c757d !important;
        }

        /* Additional Button Styles */
        .btn-info {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: var(--primary);
        }

        .btn-info:hover {
            background-color: #B8966A;
            border-color: #B8966A;
            color: var(--white);
        }

        .btn-outline-info {
            border-color: var(--secondary);
            color: var(--secondary);
        }

        .btn-outline-info:hover {
            background-color: var(--secondary);
            color: var(--primary);
        }

        /* Pagination */
        .pagination .page-link {
            color: var(--secondary);
            border-color: var(--border-light);
        }

        .pagination .page-link:hover {
            background-color: var(--secondary);
            color: var(--primary);
            border-color: var(--secondary);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--white);
        }

        /* Asset Management Specific Styles */
        .page-header {
            background: white;
            padding: 20px 24px;
            border-radius: 8px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }

        .page-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
        }

        .page-header p {
            margin: 5px 0 0 0;
            color: #6c757d;
            font-size: 14px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-top: 3px solid var(--primary);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 12px;
        }

        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin: 8px 0;
        }

        .stat-card .stat-label {
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
        }

        .table-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .table-card .card-header {
            background: var(--primary);
            color: white;
            padding: 16px 20px;
            border: none;
        }

        .table-card .card-header h5 {
            margin: 0;
            font-weight: 600;
            color: white !important;
        }

        .table-card .card-header h5 i {
            color: var(--secondary);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-available {
            background-color: #d4edda;
            color: #155724;
        }

        .status-assigned {
            background-color: #cfe2ff;
            color: #084298;
        }

        .status-maintenance {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-active {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-suspend {
            background-color: #f8d7da;
            color: #842029;
        }

        .status-closed {
            background-color: #d3d3d3;
            color: #495057;
        }

        .action-buttons .btn {
            margin-right: 8px;
            margin-bottom: 4px;
        }

        .content-card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        /* Master Page Consistent Styling */
        .master-page {
            font-family: 'Inter', 'Roboto', sans-serif;
            font-size: 12px;
            line-height: 1.6;
        }

        .master-page .page-header {
            background: white;
            padding: 20px 24px;
            border-radius: 8px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }

        .master-page .page-header h1,
        .master-page .page-header h2,
        .master-page .page-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
        }

        .master-page .page-header p {
            margin: 5px 0 0 0;
            color: #6c757d;
            font-size: 14px;
        }

        .master-form-card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        .master-form-card .form-label {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 12px;
        }

        .master-form-card .form-control {
            font-size: 12px;
            padding: 10px 12px;
            border: 1px solid var(--border-light);
            border-radius: 6px;
        }

        .master-form-card .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(31, 42, 68, 0.1);
        }

        .master-table-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .master-table-card .card-header {
            background: var(--primary);
            color: white;
            padding: 16px 20px;
            border: none;
        }

        .master-table-card .card-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 16px;
            color: white !important;
        }

        .master-table-card .table {
            margin-bottom: 0;
            font-size: 12px;
        }

        .master-table-card .table thead th {
            background-color: #f8f9fa;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 2px solid var(--border-light);
        }

        .master-table-card .table tbody td {
            padding: 12px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        .master-table-card .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .master-table-card .table tbody tr:last-child td {
            border-bottom: none;
        }

        .master-table-card .btn-sm {
            padding: 6px 12px;
            font-size: 11px;
            margin-right: 4px;
        }
    </style>
</head>

<body>

    {{-- Sidebar --}}
    {{-- Sidebar --}}
<div class="sidebar">
    {{-- Logo Section (sidebar color) --}}
    <div class="text-center mb-4 pb-3" style="border-bottom: 1px solid rgba(168, 212, 224, 0.3); padding: 15px 10px;">
        <div style="background: white; border: 1px solid rgba(168, 212, 224, 0.5); border-radius: 10px; padding: 10px 8px; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);">
            <img src="{{ asset('images/logo.png') }}" alt="Tanseeq Logo" 
                 style="max-width: 100px; width: 100%; height: auto; display: block; margin-left: auto; margin-right: auto;"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <div style="display:none;">
                <h4 style="color: var(--sidebar-master-text); font-size: 14px; font-weight: 700; letter-spacing: 2px; margin: 0;">
                    TANSEEQ
                </h4>
            </div>
        </div>
 
    </div>
    <h4 class="text-center mb-3" style="font-size: 16px; color: var(--sidebar-master-text);">Menu</h4>
   <a href="{{ route('dashboard') }}" class="sidebar-nav-item mb-1 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
    <i class="bi bi-speedometer2"></i> Dashboard
</a>
    <a href="{{ route('users.index') }}" class="sidebar-nav-item mb-1 {{ request()->routeIs('users.*') ? 'active' : '' }}">
    <i class="bi bi-people"></i> Users
</a>
    @if(auth()->user() && auth()->user()->role === 'admin')
    <a href="{{ route('activity-logs.index') }}" class="sidebar-nav-item mb-1 {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
    <i class="bi bi-journal-text"></i> User Logs
</a>
    @endif

    <!-- Employee Master -->
<div class="sidebar-dropdown mb-1">
   <button class="sidebar-dropdown-toggle" type="button">
    <span><i class="bi bi-person-badge"></i> Employee Master</span>
    <span class="chevron">▾</span>
</button>
<div class="sidebar-dropdown-menu">
    <a href="{{ route('employees.index') }}">
        <i class="bi bi-person-plus"></i> New Employee
    </a>
    <a href="{{ route('employees.search') }}">
        <i class="bi bi-search"></i> Search Employees
    </a>
    <a href="{{ url('/employee-assets') }}">
        <i class="bi bi-box-seam"></i> Employee Asset Lookup
    </a>
</div>
</div>

  <!-- Project Master -->
<div class="sidebar-dropdown mb-1">
    <button class="sidebar-dropdown-toggle" type="button">
    <span><i class="bi bi-kanban"></i> Project Master</span>
    <span class="chevron">▾</span>
</button>
<div class="sidebar-dropdown-menu">
    <a href="{{ route('projects.index') }}">
        <i class="bi bi-card-checklist"></i> Projects List
    </a>
    <a href="{{ route('projects.create') }}">
        <i class="bi bi-plus-square"></i> Create Project
    </a>
</div>
</div>

 <div class="sidebar-dropdown mb-1">
    <button class="sidebar-dropdown-toggle" type="button">
    <span><i class="bi bi-geo-alt"></i> Location Master</span>
    <span class="chevron">▾</span>
</button>
<div class="sidebar-dropdown-menu">
    <a href="{{ route('location-master.index') }}">
        <i class="bi bi-geo"></i> New Location
    </a>
    <a href="{{ route('location-master.search') }}">
        <i class="bi bi-search"></i> Location Search
    </a>
    <a href="{{ url('/location-assets') }}">
        <i class="bi bi-pc-display"></i> Location Asset Lookup
    </a>
</div>
</div>

    <!-- Entity Master -->
<div class="sidebar-dropdown mb-1">
<button class="sidebar-dropdown-toggle" type="button">
    <span><i class="bi bi-building"></i> Entity Master</span>
    <span class="chevron">▾</span>
</button>
<div class="sidebar-dropdown-menu">
    <a href="{{ route('entity-master.index') }}">
        <i class="bi bi-building-add"></i> Entity Master
    </a>
</div>
</div>

    <!-- Asset Manager -->
<div class="sidebar-dropdown mb-1">
<button class="sidebar-dropdown-toggle" type="button">
    <span><i class="bi bi-person-gear"></i> Asset Manager</span>
    <span class="chevron">▾</span>
</button>
<div class="sidebar-dropdown-menu">
    <a href="{{ route('asset-manager.index') }}">
        <i class="bi bi-person-plus"></i> Assign Asset Manager
    </a>
</div>
</div>

    <!-- Asset & Brand Management -->
<div class="sidebar-dropdown mb-1">
<button class="sidebar-dropdown-toggle" type="button">
    <span><i class="bi bi-pc-display"></i> Asset & Brand</span>
    <span class="chevron">▾</span>
</button>
<div class="sidebar-dropdown-menu">
    <a href="{{ route('categories.manage') }}">
        <i class="bi bi-tags"></i> Brand Management
    </a>
    <a href="{{ route('assets.create') }}">
        <i class="bi bi-pc-display"></i> Asset Master
    </a>
    <a href="{{ route('assets.filter') }}">
        <i class="bi bi-funnel"></i> Filter Assets by Category
    </a>
</div>
</div>

<div class="sidebar-dropdown mb-1">
<button class="sidebar-dropdown-toggle" type="button">
    <span><i class="bi bi-arrow-left-right"></i> Asset Transaction</span>
    <span class="chevron">▾</span>
</button>
<div class="sidebar-dropdown-menu">
    <a href="{{ route('asset-transactions.index') }}">
        <i class="bi bi-list-ul"></i> All Transactions
    </a>
    <a href="{{ route('asset-transactions.create') }}">
        <i class="bi bi-arrow-left-right"></i> Assign/Return
    </a>
    <a href="{{ route('asset-transactions.maintenance') }}">
        <i class="bi bi-tools"></i> System Maintenance
    </a>
</div>
</div>

<!-- Internet Services -->
<a href="{{ route('internet-services.index') }}" class="sidebar-nav-item mb-1 {{ request()->routeIs('internet-services.*') ? 'active' : '' }}">
    <i class="bi bi-wifi"></i> Internet Services
</a>






<!-- Time Management -->
<a href="{{ route('time.index') }}"
   class="sidebar-nav-item mb-1 {{ request()->routeIs('time.*') ? 'active' : '' }}">
    <i class="bi bi-clock-history"></i> Time Management
</a>

<!-- Budget Maintenance -->
<div class="sidebar-dropdown mb-1">
<button class="sidebar-dropdown-toggle" type="button">
    <span><i class="bi bi-wallet2"></i> Budget Maintenance</span>
    <span class="chevron">▾</span>
</button>
<div class="sidebar-dropdown-menu">
    <a href="{{ route('entity_budget.create') }}">
        <i class="bi bi-plus-circle"></i> Entity Budget
    </a>
    <a href="{{ route('budget-expenses.create') }}">
        <i class="bi bi-cash-coin"></i> Budget Expenses
    </a>
</div>
</div>

<!-- IT Forms -->
<div class="sidebar-dropdown mb-1">
<button class="sidebar-dropdown-toggle" type="button">
    <span><i class="bi bi-file-earmark-text"></i> IT Forms</span>
    <span class="chevron">▾</span>
</button>
<div class="sidebar-dropdown-menu">
    <a href="{{ route('issue-note.index') }}">
        <i class="bi bi-list-ul"></i> View All Notes
    </a>
    <a href="{{ route('issue-note.create') }}">
        <i class="bi bi-journal-plus"></i> Issue Note
    </a>
    <a href="{{ route('issue-note.create-return') }}">
        <i class="bi bi-arrow-return-left"></i> Return Note
    </a>
    <a href="{{ route('preventive-maintenance.create') }}">
        <i class="bi bi-tools"></i> Preventive Maintenance
    </a>
</div>
</div>

{{-- Logout Button --}}
<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(198, 168, 125, 0.3);">
    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
        @csrf
        <button type="submit" class="btn btn-outline-danger w-100" style="border-color: #dc3545; color: #dc3545;">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </button>
    </form>
</div>
</div>

    </div>

    {{-- Page Content --}}
    <div class="content">
        {{-- Global Success/Error Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show auto-dismiss-3s" role="alert" style="margin-bottom: 20px;">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-bottom: 20px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Error!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-bottom: 20px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert" style="margin-bottom: 20px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Warning!</strong> {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- ✅ Bootstrap JS Bundle (needed for collapse dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Tom Select – searchable dropdowns -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.employee-select, .searchable-select').forEach(function(el) {
            if (el.tomselect) return;
            new TomSelect(el, {
                create: false,
                sortField: { field: 'text', direction: 'asc' },
                placeholder: el.getAttribute('data-placeholder') || 'Type to search...',
                allowEmptyOption: true
            });
        });
    });
    </script>
    <!-- Auto-dismiss all success messages after 3 seconds -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.content .alert-success').forEach(function(el) {
            setTimeout(function() {
                el.style.transition = 'opacity 0.3s ease';
                el.style.opacity = '0';
                setTimeout(function() { el.remove(); }, 300);
            }, 3000);
        });
    });
    </script>
    <!-- Flatpickr – calendar for all date inputs -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function initDatePickers() {
            document.querySelectorAll('input[type="date"]').forEach(function(el) {
                if (el._flatpickr) return;
                if (el.id === 'expiry_date' || el.hasAttribute('data-no-flatpickr')) return;
                var opts = {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd-m-y',
                    allowInput: false,
                    clickOpens: true
                };
                if (el.min) opts.minDate = el.min;
                if (el.max) opts.maxDate = el.max;
                if (el.readOnly) opts.clickOpens = true;
                if (el.hasAttribute('readonly')) opts.allowInput = false;
                // Default to today's date when field is empty (all date inputs)
                if (!el.value || el.value.trim() === '') {
                    opts.defaultDate = 'today';
                }
                window.flatpickr(el, opts);
            });
        }
        initDatePickers();
        var observer = new MutationObserver(function() { initDatePickers(); });
        observer.observe(document.body, { childList: true, subtree: true });
    });
    </script>

    <!-- Form Reset Function -->
    <script>
        function resetForm(button) {
            // Show confirmation first
            if (!confirm('Are you sure you want to cancel and clear all filled details?')) {
                return; // User cancelled
            }

            // Find the form containing this button
            const form = button.closest('form');
            if (!form) {
                alert('Form not found');
                return;
            }

            // Reset the form (clears all inputs)
            form.reset();

            // Clear file inputs
            const fileInputs = form.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.value = '';
                // Also clear any preview images
                const preview = input.nextElementSibling;
                if (preview && preview.tagName === 'IMG') {
                    preview.remove();
                }
            });

            // Clear signature pads if they exist
            if (typeof SignaturePad !== 'undefined') {
                // Try to find signature pads in the form
                const userPadCanvas = form.querySelector('#user-pad');
                const managerPadCanvas = form.querySelector('#manager-pad');
                
                if (userPadCanvas && window.userPad) {
                    window.userPad.clear();
                }
                if (managerPadCanvas && window.managerPad) {
                    window.managerPad.clear();
                }
                
                // Also try to find by container
                const userContainer = form.querySelector('#user-signature-container');
                const managerContainer = form.querySelector('#manager-signature-container');
                if (userContainer) {
                    const canvas = userContainer.querySelector('canvas');
                    if (canvas && canvas.signaturePad) {
                        canvas.signaturePad.clear();
                    }
                }
                if (managerContainer) {
                    const canvas = managerContainer.querySelector('canvas');
                    if (canvas && canvas.signaturePad) {
                        canvas.signaturePad.clear();
                    }
                }
                
                // Clear signature hidden inputs
                const userSigInput = form.querySelector('#user_signature');
                const managerSigInput = form.querySelector('#manager_signature');
                if (userSigInput) userSigInput.value = '';
                if (managerSigInput) managerSigInput.value = '';
            }

            // Clear readonly fields that are auto-filled
            const readonlyInputs = form.querySelectorAll('input[readonly]');
            readonlyInputs.forEach(input => {
                input.value = '';
            });

            // Reset select dropdowns to first option
            const selects = form.querySelectorAll('select');
            selects.forEach(select => {
                select.selectedIndex = 0;
                // Trigger change event for selects that have change handlers
                select.dispatchEvent(new Event('change'));
                // Sync Tom Select if present
                if (select.tomselect) select.tomselect.sync();
            });

            // Clear checkboxes
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });

            // Clear textareas
            const textareas = form.querySelectorAll('textarea');
            textareas.forEach(textarea => {
                textarea.value = '';
            });

            // Clear any dynamically loaded content
            const dynamicContainers = form.querySelectorAll('#feature-fields, #items-container, #assets-section, #employee_name, #department, #entity, #location, #system_code, #printer_code, #software_installed, #issued_date');
            dynamicContainers.forEach(container => {
                if (container.tagName === 'DIV' || container.tagName === 'INPUT') {
                    if (container.id === 'assets-section') {
                        container.style.display = 'none';
                    } else if (container.tagName === 'DIV') {
                        container.innerHTML = '';
                    } else {
                        container.value = '';
                    }
                }
            });

            // Reset any calculated fields
            const costInput = form.querySelector('#cost');
            if (costInput) {
                costInput.value = '';
                const costInfo = form.querySelector('#cost_info');
                if (costInfo) costInfo.textContent = 'Enter MRC and select end date to calculate cost';
            }
            
            const expenseAmount = form.querySelector('#expense_amount');
            if (expenseAmount) expenseAmount.value = '';

            // Reset date fields
            const dateInputs = form.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                if (!input.hasAttribute('required') || input.id !== 'expense_date') {
                    input.value = '';
                }
            });

            console.log('Form reset successfully');
        }
    </script>

<script>
// Simple autocomplete prevention - disabled complex script to fix navigation
document.addEventListener('DOMContentLoaded', function() {
    try {
        const inputs = document.querySelectorAll('input:not([type="password"]):not([type="submit"]):not([type="button"]), textarea');
        inputs.forEach(function(input) {
            input.setAttribute('autocomplete', 'off');
        });
    } catch(e) {
        console.warn('Autocomplete setup error:', e);
    }
});
</script>

</body>
</html>
