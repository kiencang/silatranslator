<?php

// ---- Cấu hình Đường dẫn ----
define('BASE_PATH', dirname(__DIR__));
define('MARKDOWN_DIR', BASE_PATH . DIRECTORY_SEPARATOR . 'markdown_client_prompt');
// --------------------------

$markdownContent = '';
$errorMessage = '';
$fileName = '';

// 1. Lấy tên file từ tham số URL
if (isset($_GET['file'])) {
    // 2. **Quan trọng: Làm sạch tên file để tránh Directory Traversal**
    // Chỉ cho phép ký tự an toàn và đảm bảo nó nằm trong thư mục markdown
    $fileName = basename($_GET['file']); // Loại bỏ các phần đường dẫn như ../

    // Kiểm tra kỹ hơn: Chỉ cho phép chữ cái, số, dấu gạch dưới, dấu gạch ngang, và dấu chấm
    if (!preg_match('/^[a-zA-Z0-9_.\-]+$/', $fileName)) {
        $errorMessage = "Tên file không hợp lệ.";
        $fileName = ''; // Xóa tên file nếu không hợp lệ
    } else {
        // 3. Tạo đường dẫn tuyệt đối đến file markdown
        $filePath = MARKDOWN_DIR . DIRECTORY_SEPARATOR . $fileName;

        // 4. Kiểm tra xem file có tồn tại không
        if (file_exists($filePath)) {
            // 5. Đọc nội dung file
            $markdownContent = file_get_contents($filePath);
            if ($markdownContent === false) {
                $errorMessage = "Không thể đọc nội dung file.";
            }
        } else {
            $errorMessage = "File không tồn tại hoặc đã bị xóa.";
        }
    }

} else {
    $errorMessage = "Không có file nào được chỉ định để xem.";
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../images/apple-touch-icon.png">    
    <title>Xem <?php echo htmlspecialchars(pathinfo($fileName, PATHINFO_FILENAME) ?: 'Lỗi'); ?> | silaTranslator</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; }
        .container { max-width: 800px; margin: auto; border: 1px solid #eee; padding: 15px 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .error { color: red; font-weight: bold; text-align: center; padding: 20px; }
        /* Định dạng cơ bản cho markdown hiển thị trong <pre> */
        pre {
            white-space: pre-wrap;       /* Giữ khoảng trắng và xuống dòng */
            word-wrap: break-word;       /* Tự động ngắt từ dài */
            background-color: #f8f8f8; /* Màu nền nhẹ */
            border: 1px solid #ddd;    /* Viền xung quanh */
            padding: 15px;             /* Khoảng đệm bên trong */
            border-radius: 4px;        /* Bo góc nhẹ */
            font-family: monospace;      /* Font chữ đơn cách */
            font-size: 14px;           /* Cỡ chữ */
            line-height: 1.5;          /* Giãn dòng */
        }
    </style>
</head>
<body>

    <div class="container">
        <?php if ($errorMessage): ?>
            <p class="error"><?php echo $errorMessage; ?></p>
        <?php else: ?>
            <h1>Nội dung file: <?php echo htmlspecialchars(pathinfo($fileName, PATHINFO_FILENAME)); ?></h1>
            <hr>
            <!-- Hiển thị nội dung Markdown gốc trong thẻ <pre> -->
            <pre><?php echo htmlspecialchars($markdownContent); ?></pre>

            <!-- Tùy chọn Nâng cao: Nếu bạn muốn PHÂN TÍCH và HIỂN THỊ HTML từ Markdown -->
            <!-- Bạn cần cài một thư viện như Parsedown: composer require erusev/parsedown -->
            <?php
            /*
            if (class_exists('Parsedown')) {
                $parsedown = new Parsedown();
                echo "<h2>Xem trước dạng HTML:</h2>";
                echo "<div style='border:1px dashed blue; padding: 15px; margin-top: 20px;'>";
                echo $parsedown->text($markdownContent); // Chuyển markdown sang HTML
                echo "</div>";
            }
            */
            ?>

        <?php endif; ?>

        <p style="text-align: center; margin-top: 30px;">
            <a href="system_prompt.php">Quay lại trang để up file khác</a>
        </p>
    </div>

</body>
</html>