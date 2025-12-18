<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanseeq Investment - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a4d5c 0%, #1F2A44 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-wrapper {
            background: white;
            border-radius: 15px;
            padding: 25px 30px;
            display: inline-block;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
        }

        .logo-large {
            max-width: 400px;
            width: 100%;
            height: auto;
            display: block;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .logo-placeholder {
            width: 350px;
            height: 140px;
            background: linear-gradient(135deg, #1F2A44 0%, #1a4d5c 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #C6A87D;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .login-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
        }

        .form-control:focus {
            border-color: #C6A87D;
            box-shadow: 0 0 0 0.2rem rgba(198, 168, 125, 0.25);
        }

        .btn-primary {
            background: #1F2A44;
            border: none;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
        }

        .btn-primary:hover {
            background: #2C3E66;
        }

        .alert {
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <div class="logo-wrapper">
                <img src="{{ asset('images/logo.png') }}" alt="Tanseeq Logo" class="logo-large" 
                     style="display: block; margin: 0 auto;"
                     onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="logo-placeholder" style="display:none;">
                    <div style="position: relative; z-index: 1; color: #C6A87D; font-size: 32px; font-weight: 700; letter-spacing: 4px;">TANSEEQ</div>
                </div>
            </div>
        </div>

        <div class="login-card">
            @yield('content')
        </div>
    </div>
</body>
</html>
