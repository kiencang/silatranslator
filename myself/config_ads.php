<?php
// File: myself/config_ads.php

// --- Cấu hình Loại bỏ Quảng cáo ---
$removeAdsStatusFile = __DIR__ . '/remove_ads_status.php';
$isRemoveAdsEnabled = false; // Mặc định là tắt

if (file_exists($removeAdsStatusFile)) { // Kiểm tra xem file có tồn tại hay không
    // Cẩn thận khi include/require file có thể bị ghi đè bởi người dùng
    // Trong trường hợp này, file chỉ trả về true/false nên tương đối an toàn
    $status = include $removeAdsStatusFile; 
    if (is_bool($status)) {
        $isRemoveAdsEnabled = $status; // Gán cho biến
    } else {
        // Ghi log lỗi nếu file trạng thái không hợp lệ
        error_log("Lỗi: File remove_ads_status.php không trả về giá trị boolean hợp lệ.");
    }
} else {
    // Ghi log nếu file không tồn tại (có thể tạo file mặc định)
    error_log("Thông báo: File remove_ads_status.php không tìm thấy, sử dụng giá trị mặc định (false).");
    // Tạo file nếu chưa tồn tại, không cần, vì đã tạo sẵn rồi, nhưng nếu thích thì cứ dự phòng
    file_put_contents($removeAdsStatusFile, '<?php return false;');
}

// Định nghĩa hằng số dựa trên trạng thái đọc được
define('REMOVE_ADS_CONTENT', $isRemoveAdsEnabled); // Gán true hoặc false cho hàng số