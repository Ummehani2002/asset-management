<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Asset Management System - Tanseeq Investment</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --primary: #1F2A44;       /* Navy Blue */
            --secondary: #C6A87D;     /* Beige / Gold */
            --hover: #2C3E66;
            --bg-light: #F7F8FA;
            --white: #FFFFFF;
            --text-dark: #1F2A44;
            --border-light: #E5E7EB;
        }

        body {
            display: flex;
            min-height: 100vh;
            font-family: 'Inter', 'Roboto', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: var(--primary);
            padding-top: 20px;
            color: #fff;
        }

        .sidebar h4 {
            color: var(--secondary);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .sidebar a,
        .sidebar button {
            color: #EAECEF;
            text-decoration: none;
            display: block;
            padding: 10px 20px;
            width: 100%;
            text-align: left;
            border: none;
            background: transparent;
            transition: all 0.25s ease;
        }

        .sidebar a:hover,
        .sidebar button:hover {
            background-color: var(--hover);
            color: var(--secondary);
        }

        .collapse a {
            padding-left: 40px;
            font-size: 13px;
        }

        /* Content */
        .content {
            flex-grow: 1;
            padding: 24px;
            background-color: var(--bg-light);
            min-height: 100vh;
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
            font-size: 24px;
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
            font-size: 14px;
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
            font-size: 24px;
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
            font-size: 14px;
        }

        .master-form-card .form-control {
            font-size: 14px;
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
            font-size: 14px;
        }

        .master-table-card .table thead th {
            background-color: #f8f9fa;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 13px;
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
            font-size: 12px;
            margin-right: 4px;
        }
    </style>
</head>

<body>

    {{-- Sidebar --}}
    {{-- Sidebar --}}
<div class="sidebar">
    {{-- Logo Section --}}
    <div class="text-center mb-4 pb-3" style="border-bottom: 1px solid rgba(198, 168, 125, 0.3); padding: 15px 10px;">
        <div style="background: white; border-radius: 10px; padding: 10px 8px; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);">
            <img src="{{ asset('images/logo.png') }}" alt="Tanseeq Logo" 
                 style="max-width: 100px; width: 100%; height: auto; display: block; margin-left: auto; margin-right: auto;"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <div style="display:none;">
                <h4 style="color: #C6A87D; font-size: 14px; font-weight: 700; letter-spacing: 2px; margin: 0;">
                    TANSEEQ
                </h4>
            </div>
        </div>
 
    </div>
    <h4 class="text-center mb-3" style="font-size: 16px; color: rgba(255,255,255,0.9);">Menu</h4>
   <a href="{{ route('dashboard') }}" class="btn btn-outline-primary mb-2">
    <i class="bi bi-speedometer2"></i> Dashboard
</a>
    <a href="{{ route('users.index') }}" class="btn btn-outline-primary mb-2">
    <i class="bi bi-people"></i> Users
</a>

    <!-- Employee Master -->
   <button class="btn btn-outline-primary mb-2"
        data-bs-toggle="collapse"
        data-bs-target="#employeeMenu"
        aria-expanded="false"
        aria-controls="employeeMenu">
    <i class="bi bi-person-badge"></i> Employee Master ▾
</button>

<div class="collapse" id="employeeMenu">
    <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-person-plus"></i> New Employee
    </a>

    <a href="{{ url('/employee-assets') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-search"></i> Employee Asset Lookup
    </a>
</div>

  <!-- Project Master -->
    <button class="btn btn-outline-primary mb-2"
        data-bs-toggle="collapse"
        data-bs-target="#projectMenu"
        aria-expanded="false"
        aria-controls="projectMenu">
    <i class="bi bi-kanban"></i> Project Master ▾
</button>

<div class="collapse" id="projectMenu">
    <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-card-checklist"></i> Projects List
    </a>

    <a href="{{ route('projects.create') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-plus-square"></i> Create Project
    </a>
</div>

 <button class="btn btn-outline-primary mb-2"
        data-bs-toggle="collapse"
        data-bs-target="#locationMenu"
        aria-expanded="false"
        aria-controls="locationMenu">
    <i class="bi bi-geo-alt"></i> Location Master ▾
</button>

<div class="collapse" id="locationMenu">
    <a href="{{ route('location-master.store') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-geo"></i> New Location
    </a>

    <a href="{{ url('/location-assets') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-search"></i> Location Asset Lookup
    </a>
</div>

    <!-- Asset & Brand Management -->
<button class="btn btn-outline-primary mb-2"
        data-bs-toggle="collapse"
        data-bs-target="#assetBrandMenu"
        aria-expanded="false"
        aria-controls="assetBrandMenu">
    <i class="bi bi-pc-display"></i> Asset & Brand  ▾
</button>

<div class="collapse" id="assetBrandMenu">
    <a href="{{ route('categories.manage') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-tags"></i> Brand Management
    </a>

    <a href="{{ route('assets.create') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-pc-display"></i> Asset Master
    </a>
</div>

<button class="btn btn-outline-primary mb-2"
        data-bs-toggle="collapse"
        data-bs-target="#assetTransactionMenu"
        aria-expanded="false"
        aria-controls="assetTransactionMenu">
    <i class="bi bi-arrow-left-right"></i> Asset Transaction ▾
</button>
<div class="collapse" id="assetTransactionMenu">
    <a href="{{ route('asset-transactions.index') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-list-ul"></i> All Transactions
    </a>
    <a href="{{ route('asset-transactions.view') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-funnel"></i> View (Filter)
    </a>
    <a href="{{ route('asset-transactions.create') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-arrow-left-right"></i> Assign/Return
    </a>
    <a href="{{ route('asset-transactions.maintenance') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-tools"></i> System Maintenance
    </a>
</div>

<!-- Internet Services -->
<a href="{{ route('internet-services.index') }}" class="btn btn-outline-primary mb-2">
    <i class="bi bi-wifi"></i> Internet Services
</a>








<!-- Time Management -->
<a href="{{ route('time.index') }}"
   class="btn btn-outline-primary mb-2 {{ request()->routeIs('time.*') ? 'active' : '' }}">
    <i class="bi bi-clock-history"></i> Time Management
</a>

<!-- Budget Maintenance -->
<button class="btn btn-outline-primary mb-2"
        data-bs-toggle="collapse"
        data-bs-target="#budgetMenu"
        aria-expanded="false"
        aria-controls="budgetMenu">
    <i class="bi bi-wallet2"></i> Budget Maintenance ▾
</button>

<div class="collapse" id="budgetMenu">
    <a href="{{ route('entity_budget.create') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-plus-circle"></i>  Entity Budget
    </a>

    <a href="{{ route('budget-expenses.create') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-cash-coin"></i> Budget Expenses
    </a>
</div>

<!-- IT Forms -->
<button class="btn btn-outline-primary mb-2"
        data-bs-toggle="collapse"
        data-bs-target="#issueNoteMenu"
        aria-expanded="false"
        aria-controls="issueNoteMenu">
    <i class="bi bi-file-earmark-text"></i> IT Forms ▾
</button>

        <div class="collapse" id="issueNoteMenu">
            <a href="{{ route('issue-note.index') }}" class="btn btn-sm btn-outline-light mb-1">
                <i class="bi bi-list-ul"></i> View All Notes
            </a>
            <a href="{{ route('issue-note.create') }}" class="btn btn-sm btn-outline-light mb-1">
                <i class="bi bi-journal-plus"></i>  Issue Note
            </a>
            <a href="{{ route('issue-note.create-return') }}" class="btn btn-sm btn-outline-light mb-1">
                <i class="bi bi-arrow-return-left"></i> Return Note
            </a>

    <a href="{{ route('preventive-maintenance.create') }}" class="btn btn-sm btn-outline-light mb-1">
        <i class="bi bi-tools"></i> Preventive Maintenance
    </a>
</div>
</div>

    </div>

    {{-- Page Content --}}
    <div class="content">
        @yield('content')
    </div>

    <!-- ✅ Bootstrap JS Bundle (needed for collapse dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>