<!DOCTYPE html>
<html>
<head>
    <title>Asset Management</title>
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
            font-family: 'Inter', 'Roboto', sans-serif;
            background: linear-gradient(135deg, var(--bg-light) 0%, #E8EAF0 100%);
            min-height: 100vh;
            color: var(--text-dark);
            padding: 20px;
        }

        .container {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(31, 42, 68, 0.1);
            padding: 40px;
            max-width: 450px;
            margin: 50px auto;
        }

        h3 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 30px;
        }

        .form-control {
            border: 1px solid var(--border-light);
            border-radius: 6px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.2rem rgba(198, 168, 125, 0.25);
        }

        label {
            color: var(--text-dark);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--white);
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 6px;
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
            padding: 10px 20px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background-color: #B8966A;
            border-color: #B8966A;
            color: var(--white);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(198, 168, 125, 0.3);
        }

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

        a {
            color: var(--secondary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        /* Bootstrap Color Overrides */
        .text-primary {
            color: var(--primary) !important;
        }

        .bg-primary {
            background-color: var(--primary) !important;
        }

        .text-secondary {
            color: var(--secondary) !important;
        }

        .bg-secondary {
            background-color: var(--secondary) !important;
            color: var(--primary) !important;
        }
    </style>
</head>
<body>

@yield('content')

</body>
</html>
