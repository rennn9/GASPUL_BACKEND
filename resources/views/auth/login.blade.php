<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - GASPUL</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            display: flex;
            width: 850px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0px 4px 20px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .login-left {
            background-color: #017787;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }
        .login-left img {
            max-width: 80%;
        }
        .login-right {
            flex: 1;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-right h3 {
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-control {
            border-radius: 50px;
            padding: 12px 20px;
        }
        .btn-login {
            background-color: #05A4AD;
            color: #fff;
            border-radius: 50px;
            padding: 10px;
            font-weight: bold;
        }
        .btn-login:hover {
            background-color: #04929A;
            color: #fff;
        }
    </style>
</head>
<body>

<div class="login-card">
    <!-- Kiri -->
    <div class="login-left">
        <img src="{{ asset('assets/images/logo-gaspul.png') }}" alt="Logo GASPUL">
    </div>

    <!-- Kanan -->
    <div class="login-right">
        <h3>Login Admin</h3>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="nip" class="form-label">NIP</label>
                <input type="text" class="form-control" id="nip" name="nip" placeholder="Masukkan NIP" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan Password" required>
            </div>
            <button type="submit" class="btn btn-login w-100">Login</button>
        </form>
    </div>
</div>

</body>
</html>
