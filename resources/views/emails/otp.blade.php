<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>OTP Verification</title>
</head>

<body style="font-family: Arial; background: #f4f4f4; padding: 20px;">

  <div style="max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 10px;">

    <h2 style="text-align: center;">Xác thực email</h2>

    <p>Xin chào 👋</p>

    <p>Mã OTP của bạn là:</p>

    <h1 style="text-align: center; color: #3490dc;">
      {{ $otp }}
    </h1>

    <p>Mã này sẽ hết hạn sau 5 phút.</p>

    <p>Nếu bạn không yêu cầu, hãy bỏ qua email này.</p>

    <hr>

    <p style="text-align: center; font-size: 12px; color: gray;">
      © DanFlix 2026
    </p>

  </div>

</body>

</html>