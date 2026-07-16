<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? '';
$error = '';
$message = '';

if (empty($token)) {
    redirect(ADMIN_URL . '/login.php');
}

$pdo = $db->getConnection();
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
$stmt->execute([$token]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    $error = 'لینک بازیابی نامعتبر است.';
} else {
    $created = new DateTime($reset['created_at']);
    $now = new DateTime();
    $diff = $now->diff($created);
    if ($diff->h >= 1 && $diff->i > 0) {
        $error = 'لینک بازیابی منقضی شده است.';
        $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 6) {
        $error = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'رمز عبور و تکرار آن مطابقت ندارند.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$reset['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            update('users', $user['id'], [
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ]);
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$reset['email']]);
            $message = 'رمز عبور با موفقیت تغییر کرد.';
        } else {
            $error = 'خطا در بازیابی رمز عبور.';
        }
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغییر رمز عبور</title>
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
            <i class="fas fa-lock"></i>
            <h1>تغییر رمز عبور</h1>
            <p>رمز عبور جدید خود را وارد کنید</p>
        </div>
        <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
        <div class="success"><?= $message ?></div>
        <div style="text-align:center; margin-top:15px;">
            <a href="<?= ADMIN_URL ?>/login.php" style="color:#6dc051; font-weight:600;">ورود با رمز جدید</a>
        </div>
        <?php endif; ?>
        <?php if (empty($error) && empty($message)): ?>
        <form method="POST">
            <div class="form-group">
                <label>رمز عبور جدید</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" required placeholder="حداقل ۶ کاراکتر" minlength="6">
                </div>
            </div>
            <div class="form-group">
                <label>تکرار رمز عبور</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password_confirm" required placeholder="تکرار رمز عبور">
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-save"></i> ذخیره رمز جدید
            </button>
        </form>
        <?php endif; ?>
        <div class="back-link">
            <a href="<?= ADMIN_URL ?>/login.php"><i class="fas fa-arrow-right"></i> بازگشت به صفحه ورود</a>
        </div>
    </div>
</body>
</html>
