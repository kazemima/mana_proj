<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'لطفاً ایمیل خود را وارد کنید.';
    } else {
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
            $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)")->execute([$email, $token]);

            $resetUrl = ADMIN_URL . '/reset-password.php?token=' . $token;
            $subject = 'بازیابی رمز عبور - پنل مدیریت';
            $body = "سلام {$user['name']},\n\n";
            $body .= "برای بازیابی رمز عبور خود روی لینک زیر کلیک کنید:\n\n";
            $body .= $resetUrl . "\n\n";
            $body .= "این لینک تا ۱ ساعت معتبر است.\n";
            $body .= "اگر شما درخواست بازیابی رمز نداده‌اید، این ایمیل را نادیده بگیرید.\n\n";
            $body .= "با تشکر، پنل مدیریت";

            $headers = 'From: noreply@' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
            $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";

            @mail($email, $subject, $body, $headers);

            $message = 'لینک بازیابی رمز عبور به ایمیل شما ارسال شد.';
        } else {
            $error = 'کاربری با این ایمیل یافت نشد.';
        }
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بازیابی رمز عبور</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Vazirmatn', Tahoma; background: linear-gradient(135deg, #1a1a2e, #16213e); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { background: #fff; border-radius: 12px; padding: 40px; width: 400px; max-width: 95%; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h1 { font-size: 1.4rem; color: #333; margin-bottom: 5px; }
        .login-header p { color: #888; font-size: 0.9rem; }
        .login-header i { font-size: 3rem; color: #6dc051; margin-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; color: #333; }
        .form-group .input-wrapper { position: relative; }
        .form-group .input-wrapper i { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #aaa; }
        .form-group input { width: 100%; padding: 12px 40px 12px 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 0.95rem; direction: rtl; transition: border-color 0.3s; }
        .form-group input:focus { outline: none; border-color: #6dc051; box-shadow: 0 0 0 3px rgba(109,192,81,0.1); }
        .btn-login { width: 100%; padding: 12px; background: #6dc051; color: #fff; border: none; border-radius: 8px; font-family: inherit; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s; }
        .btn-login:hover { background: #5aa842; }
        .error { background: #f8d7da; color: #721c24; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; border: 1px solid #f5c6cb; }
        .success { background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; border: 1px solid #c3e6cb; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #6dc051; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <i class="fas fa-key"></i>
            <h1>بازیابی رمز عبور</h1>
            <p>ایمیل خود را وارد کنید تا لینک بازیابی دریافت کنید</p>
        </div>
        <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
        <div class="success"><?= $message ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>ایمیل</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" required placeholder="ایمیل خود را وارد کنید" autofocus>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-paper-plane"></i> ارسال لینک بازیابی
            </button>
        </form>
        <div class="back-link">
            <a href="<?= ADMIN_URL ?>/login.php"><i class="fas fa-arrow-right"></i> بازگشت به صفحه ورود</a>
        </div>
    </div>
</body>
</html>
