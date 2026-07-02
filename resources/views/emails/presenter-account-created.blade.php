<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Akun BONUSKU Presenter</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #334155;">
    <p>Halo <strong>{{ $presenterName }}</strong>,</p>

    <p>Akun Anda untuk aplikasi {{ $appName }} {{ $institutionName }} telah dibuat.</p>

    <p>Silakan login menggunakan data berikut:</p>

    <ul>
        <li><strong>URL Login:</strong> <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></li>
        <li><strong>Email:</strong> {{ $email }}</li>
        <li><strong>Password Sementara:</strong> {{ $plainPassword }}</li>
    </ul>

    <p>Demi keamanan, Anda wajib mengganti password setelah login pertama.</p>

    <p>Terima kasih.</p>

    <p>
        Admin {{ $appName }}<br>
        {{ $institutionName }}
    </p>
</body>
</html>
