<?php
// File chứa cấu hình
$configFile = 'what_lang_translate.php';
$formPage = 'lang_settings_form.php'; // Trang form để chuyển hướng lại

// Kiểm tra phương thức gửi là POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $formPage . '?error=1'); // Chuyển hướng nếu không phải POST
    exit;
}

// Kiểm tra xem 'translation_mode' đã được gửi chưa
if (!isset($_POST['translation_mode'])) {
    header('Location: ' . $formPage . '?error=2'); // Lỗi thiếu dữ liệu
    exit;
}

$selectedMode = $_POST['translation_mode'];

// Kiểm tra giá trị hợp lệ
if ($selectedMode !== 'setEV' && $selectedMode !== 'setCV' && $selectedMode !== 'setJV' && $selectedMode !== 'setKV') {
    header('Location: ' . $formPage . '?error=3'); // Lỗi giá trị không hợp lệ
    exit;
}

// Kiểm tra file tồn tại và có quyền ghi
if (!file_exists($configFile)) {
    header('Location: ' . $formPage . '?error=4'); // Lỗi file không tồn tại
    exit;
}
if (!is_writable($configFile)) {
    header('Location: ' . $formPage . '?error=5'); // Lỗi không có quyền ghi
    exit;
}

// Đọc nội dung file hiện tại
$content = file_get_contents($configFile);

// Tạo dòng define mới
$newLine = "define('LANG_TRANSLATE', '$selectedMode');";

// Sử dụng regex để thay thế dòng define cũ bằng dòng mới
// - /define\('LANG_TRANSLATE',\s*'.*?'\);/i : Tìm dòng define, bỏ qua khoảng trắng, không phân biệt hoa thường
// - $newLine: Dòng thay thế
// - $content: Nội dung gốc
// - 1: Chỉ thay thế 1 lần
$newContent = preg_replace("/define\('LANG_TRANSLATE',\s*'.*?'\);/i", $newLine, $content, 1, $count);

// Kiểm tra xem việc thay thế có thành công không (tìm thấy dòng define)
if ($count > 0) {
    // Ghi lại nội dung mới vào file
    if (file_put_contents($configFile, $newContent) !== false) {
        // Chuyển hướng về trang form với thông báo thành công
        header('Location: ' . $formPage . '?success=1');
        exit;
    } else {
        // Lỗi khi ghi file
        header('Location: ' . $formPage . '?error=6');
        exit;
    }
} else {
    // Không tìm thấy dòng define trong file
    // Có thể thêm dòng đó vào cuối file nếu muốn
    /*
    $newContent = rtrim($content) . "\n" . $newLine . "\n"; // Thêm vào cuối
     if (file_put_contents($configFile, $newContent) !== false) {
         header('Location: ' . $formPage . '?success=1');
         exit;
     } else {
        header('Location: ' . $formPage . '?error=6');
        exit;
     }
    */
    // Hoặc báo lỗi không tìm thấy dòng
    header('Location: ' . $formPage . '?error=7');
    exit;
}