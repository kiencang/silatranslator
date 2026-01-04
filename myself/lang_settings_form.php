<?php
// File chứa cấu hình
$configFile = 'what_lang_translate.php';
$currentMode = 'setEV'; // Giá trị mặc định nếu không đọc được file

// Kiểm tra file tồn tại và đọc nội dung
if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    // Tìm dòng define và trích xuất giá trị hiện tại
    if (preg_match("/define\('LANG_TRANSLATE', '(.*?)'\);/", $content, $matches)) {
        if (isset($matches[1]) && ($matches[1] === 'setEV' || $matches[1] === 'setCV' || $matches[1] === 'setJV' || $matches[1] === 'setKV')) {
             $currentMode = $matches[1];
        }
    }
} else {
    // Tùy chọn: Tạo file nếu chưa có với giá trị mặc định
    // file_put_contents($configFile, "<?php\n// Cấu hình dịch\ndefine('LANG_TRANSLATE', 'setEV');\n");
    // Hoặc hiển thị lỗi file không tồn tại
    $error_message = "Lỗi: File cấu hình '$configFile' không tồn tại.";
}

// Xử lý thông báo thành công (nếu có từ trang xử lý)
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Cập nhật cấu hình thành công!";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../images/apple-touch-icon.png"> 
    <link rel="stylesheet" href="../css/shared.css?v=2">
    <title>Cài đặt Chế độ Dịch</title>
    <style>
        body {
            font-family: "Be Vietnam Pro", 'Roboto', sans-serif;
            line-height: 1.6;
            padding: 20px;
            background-color: #f4f4f4;
            box-sizing: border-box; /* Nên có để padding không làm tăng kích thước tổng */
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 25px 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block; /* Làm cho label và input trên 2 dòng khác nhau */
            margin-bottom: 10px; /* Khoảng cách giữa các radio */
            cursor: pointer;
            font-weight: bold;
            color: #555;
            padding-left: 30px; /* Tạo không gian cho nút radio */
            position: relative; /* Cho phép định vị nút radio ảo */
        }
        .form-group input[type="radio"] {
             opacity: 0; /* Ẩn nút radio thật */
             position: absolute; /* Đưa ra khỏi luồng */
             left: 0;
        }
         /* Tạo nút radio giả */
        .form-group label::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            border: 2px solid #ccc;
            border-radius: 50%;
            background-color: #fff;
        }
        /* Hiển thị dấu chấm khi được chọn */
        .form-group input[type="radio"]:checked + label::after {
            content: '';
            position: absolute;
            left: 5px; /* Căn giữa trong vòng tròn */
            top: 50%;
            transform: translateY(-50%);
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #007bff;
        }
        /* Thay đổi viền khi focus (tab) */
         .form-group input[type="radio"]:focus + label::before {
             border-color: #007bff;
             box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
         }

        .submit-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .submit-btn:hover {
            background-color: #0056b3;
        }
        .message {
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }          
    </style>
</head>
<body> 
    <div class="container">
        <h1>Chọn Chế độ Dịch</h1>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form action="lang_update_config.php" method="post">
            <div class="form-group">
                <input type="radio" id="mode_ev" name="translation_mode" value="setEV" <?php echo ($currentMode === 'setEV') ? 'checked' : ''; ?>>
                <label for="mode_ev">Dịch từ tiếng Anh sang tiếng Việt</label>
            </div>
            <div class="form-group">
                <input type="radio" id="mode_cv" name="translation_mode" value="setCV" <?php echo ($currentMode === 'setCV') ? 'checked' : ''; ?>>
                <label for="mode_cv">Dịch từ tiếng Trung sang tiếng Việt</label>
            </div>            
            <div class="form-group">
                <input type="radio" id="mode_jv" name="translation_mode" value="setJV" <?php echo ($currentMode === 'setJV') ? 'checked' : ''; ?>>
                <label for="mode_jv">Dịch từ tiếng Nhật sang tiếng Việt</label>
            </div>
            <div class="form-group">
                <input type="radio" id="mode_kv" name="translation_mode" value="setKV" <?php echo ($currentMode === 'setKV') ? 'checked' : ''; ?>>
                <label for="mode_kv">Dịch từ tiếng Hàn sang tiếng Việt</label>
            </div>            
            <button type="submit" class="submit-btn">Lưu Cài Đặt</button>
        </form>
            <!-- ===================== Nút Tiện Ích Phụ ===================== -->
            <div class="utility-buttons">
                <a href="setting.php" class="utility-button">Chọn Model</a>
                <a href="../index.php" class="utility-button">Dịch trang web</a>
                <a href="../index.php?html=true" class="utility-button">Dịch HTML</a>
                <a href="small_settings.php" class="utility-button">Cài đặt Nhỏ</a>
            </div>
            <!-- ===================== E Nút Tiện Ích Phụ ===================== -->               
    </div>
</body>
</html>