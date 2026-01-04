<?php
// File: myself/is_remove_ads_content.php
session_start(); // Sử dụng session để lưu thông báo

// Đường dẫn đến các file cấu hình
$statusFile = __DIR__ . '/remove_ads_status.php'; // Trạng thái xóa hay không xóa các thông tin nghi ngờ là không liên quan
$markerFile = __DIR__ . '/marker_remove.php'; // Các thông tin được cho là không liên quan, xóa ngay khi nó còn là đầu vào tiếng Anh

// --- Hàm trợ giúp ---



/**
 * Tải trạng thái Bật/Tắt từ file.
 * @param string $filePath Đường dẫn file trạng thái.
 * @return bool Trạng thái hiện tại (mặc định là false).
 */
function loadRemoveAdsStatus(string $filePath): bool
{
    if (file_exists($filePath)) {
        $status = include $filePath;
        return is_bool($status) ? $status : false;
    }
    return false;
}



/**
 * Lưu trạng thái Bật/Tắt vào file.
 * @param string $filePath Đường dẫn file trạng thái.
 * @param bool $isEnabled Trạng thái cần lưu.
 * @return bool True nếu lưu thành công, False nếu thất bại.
 */
function saveRemoveAdsStatus(string $filePath, bool $isEnabled): bool
{
    $content = '<?php' . PHP_EOL . '// Trạng thái Bật/Tắt tính năng loại bỏ quảng cáo' . PHP_EOL . 'return ' . ($isEnabled ? 'true' : 'false') . ';' . PHP_EOL;
    // Sử dụng LOCK_EX để tránh ghi đè đồng thời (dù ít khả năng xảy ra ở trang admin)
    return file_put_contents($filePath, $content, LOCK_EX) !== false;
}



/**
 * Tải cấu hình marker từ file.
 * @param string $filePath Đường dẫn file marker.
 * @return array Mảng cấu hình marker (mặc định là mảng rỗng).
 */
function loadMarkerConfig(string $filePath): array
{
    if (file_exists($filePath)) {
        $markers = include $filePath;
        // Đảm bảo trả về một mảng hợp lệ
        return is_array($markers) ? $markers : [];
    }
    return []; // Trả về mảng rỗng nếu file không tồn tại
}



/**
 * Lưu cấu hình marker vào file.
 * @param string $filePath Đường dẫn file marker.
 * @param array $markers Mảng cấu hình marker cần lưu.
 * @return bool True nếu lưu thành công, False nếu thất bại.
 */
function saveMarkerConfig(string $filePath, array $markers): bool
{
    // Sử dụng var_export để tạo mã PHP hợp lệ
    $content = '<?php' . PHP_EOL . '// Cấu hình các marker để loại bỏ nội dung' . PHP_EOL . 'return ' . var_export($markers, true) . ';' . PHP_EOL;
    return file_put_contents($filePath, $content, LOCK_EX) !== false;
}



// --- Xử lý dữ liệu POST khi form được gửi ---
$message = $_SESSION['flash_message'] ?? null; // Lấy thông báo từ lần redirect trước
unset($_SESSION['flash_message']); // Xóa thông báo sau khi hiển thị

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Xử lý bật/tắt tính năng
    $isEnabled = isset($_POST['remove_ads_enabled']) && $_POST['remove_ads_enabled'] === '1';
    if (!saveRemoveAdsStatus($statusFile, $isEnabled)) {
        $message = ['type' => 'danger', 'text' => 'Lỗi: Không thể lưu trạng thái Bật/Tắt.'];
    } else {
        // 2. Xử lý danh sách marker (chỉ khi tính năng được bật)
        $currentMarkers = [];
        if ($isEnabled) {
            // Xử lý các marker hiện có (cập nhật hoặc xóa)
            if (isset($_POST['markers']) && is_array($_POST['markers'])) {
                foreach ($_POST['markers'] as $index => $markerData) {
                    if (isset($markerData['action']) && $markerData['action'] === 'delete') {
                        continue; // Bỏ qua marker bị đánh dấu xóa
                    }

                    $markerText = trim(htmlspecialchars($markerData['marker'] ?? '', ENT_QUOTES, 'UTF-8'));
                    if (empty($markerText)) {
                        continue; // Bỏ qua marker rỗng
                    }

                    $maxLength = isset($markerData['maxLength']) && $markerData['maxLength'] !== ''
                                 ? filter_var($markerData['maxLength'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 3, 'max_range' => 100]])
                                 : null;
                     // Nếu filter trả về false (không hợp lệ) hoặc null (rỗng), đặt là null
                    if ($maxLength === false) $maxLength = null;

                    $caseSensitive = isset($markerData['caseSensitive']); // Checkbox được check là true

                    $currentMarkers[] = [
                        'marker' => $markerText,
                        'maxLength' => $maxLength,
                        'caseSensitive' => $caseSensitive,
                    ];
                }
            }

            // Xử lý các marker mới được thêm
            if (isset($_POST['new_markers']) && is_array($_POST['new_markers'])) {
                foreach ($_POST['new_markers'] as $newMarkerData) {
                     $markerText = trim(htmlspecialchars($newMarkerData['marker'] ?? '', ENT_QUOTES, 'UTF-8'));
                    if (empty($markerText)) {
                        continue; // Bỏ qua marker rỗng
                    }
                    $maxLength = isset($newMarkerData['maxLength']) && $newMarkerData['maxLength'] !== ''
                                 ? filter_var($newMarkerData['maxLength'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 3, 'max_range' => 100]])
                                 : null;
                    if ($maxLength === false) $maxLength = null;
                    $caseSensitive = isset($newMarkerData['caseSensitive']);

                     $currentMarkers[] = [
                        'marker' => $markerText,
                        'maxLength' => $maxLength,
                        'caseSensitive' => $caseSensitive,
                    ];
                }
            }

            // Lưu danh sách marker đã cập nhật
            if (!saveMarkerConfig($markerFile, $currentMarkers)) {
                 $message = ['type' => 'danger', 'text' => 'Lỗi: Không thể lưu danh sách marker.'];
            } else if (!$message) { // Chỉ đặt thông báo thành công nếu chưa có lỗi trước đó
                 $message = ['type' => 'success', 'text' => 'Cấu hình đã được lưu thành công.'];
            }
        } else {
            // Nếu tính năng bị tắt, có thể cân nhắc xóa file marker hoặc giữ lại tùy ý
            // Hiện tại: Giữ lại file marker để khi bật lại có sẵn cấu hình cũ
             if (!$message) {
                 $message = ['type' => 'success', 'text' => 'Tính năng loại bỏ quảng cáo đã được TẮT. Cấu hình marker được giữ lại.'];
             }
        }
    }

    // Lưu thông báo vào session và chuyển hướng để tránh gửi lại form
    $_SESSION['flash_message'] = $message;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// --- Tải dữ liệu hiện tại để hiển thị form ---
$currentStatus = loadRemoveAdsStatus($statusFile);
$currentMarkers = loadMarkerConfig($markerFile);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu hình Loại bỏ Nội dung Quảng cáo</title>
    <!-- Sử dụng Bootstrap 5 để làm đẹp nhanh -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro&display=swap" rel="stylesheet">  
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../images/apple-touch-icon.png">    
    <style>
        body { 
            padding-top: 20px; 
            font-family: 'Be Vietnam Pro', 'Roboto', sans-serif;
        }
        
        .marker-item { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-bottom: 15px; 
            border-radius: 5px; 
            background-color: #f9f9f9; 
        }
        
        .marker-item label { 
            font-weight: 500; 
        }
        
        /* Ẩn các trường marker khi tính năng bị tắt */
        .markers-section { 
            display: <?php echo $currentStatus ? 'block' : 'none'; ?>; 
        }
        
        /* CSS Sidebar không đổi */
        #sticky-left-sidebar { 
            position: fixed; 
            right: 0; 
            top: 40%; 
            transform: translateY(-50%); 
            width: 55px; 
            background-color: rgba(200, 200, 200, 0.5); 
            padding: 10px 0; 
            border-radius: 0 8px 8px 0; 
            box-shadow: 2px 0px 8px rgba(0, 0, 0, 0.15); 
            z-index: 999; 
            transition: background-color 0.3s ease; 
        }
        
        #sticky-left-sidebar:hover { 
            background-color: rgba(222, 226, 230, 0.8); 
        }
        
        #sticky-left-sidebar ul { 
            list-style: none; 
            padding: 0; 
            margin: 0; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
        }
        
        #sticky-left-sidebar li { 
            margin-bottom: 10px; 
        }
        
        #sticky-left-sidebar li:last-child { 
            margin-bottom: 0; 
        }
        
        #sticky-left-sidebar a { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            width: 40px; 
            height: 40px; 
            background-color: #ffffff; 
            color: #495057; 
            text-decoration: none; 
            font-size: 1.2em; 
            border-radius: 50%; 
            border: 1px solid #dee2e6; 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); 
            transition: all 0.2s ease-in-out; 
        }
        
        #sticky-left-sidebar a:hover { 
            background-color: #e9ecef; 
            color: #212529; 
            transform: scale(1.1); 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
        }
        
        #sticky-left-sidebar a:active { 
            transform: scale(1.05); 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); 
        }

        @media (max-width: 850px) { 
            #sticky-left-sidebar { 
                display: none; 
            } 
        }         
    </style>
</head>
<body>
    <!-- === Thanh Bên Trái Cố Định === -->
    <aside id="sticky-left-sidebar">
        <ul>
            <li>
                <a href="../index.php" title="Trang dịch web">
                    <svg width="24" height="24" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                      <polygon 
                        points="25,2  30,18  48,18  34,30  40,48  25,38  10,48  16,30  2,18  20,18"
                        fill="#777" 
                      />
                    </svg>
                </a>
            </li>
            <li><a href="../search.php" title="Tìm kiếm (từ khóa tiếng Việt chuyển thành từ khóa tiếng Anh)">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="search-icon"
                        >
                    <title>Tìm kiếm (từ khóa tiếng Việt chuyển thành từ khóa tiếng Anh)</title>
                    <circle cx="11" cy="11" r="5"></circle>
                    <line x1="21" y1="21" x2="15.65" y2="15.65"></line>
                    </svg>
                </a>
            </li>
            <li><a href="small_settings.php" title="Xóa một số nội dung quảng cáo còn sót lại">
                    <svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                      <title>Cài đặt Nhỏ</title>
                      <circle cx="10" cy="10" r="4" fill="currentColor"/>
                    </svg>
                </a>
            </li>
        </ul>     
    </aside>
    <!-- === Kết Thúc Thanh Bên Trái === -->      
<div class="container">
    <h1><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-shield-slash" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M1.094 12.84A14.58 14.58 0 0 0 2.31 14.72a1 1 0 0 0 .708.28H13a1 1 0 0 0 .707-.28 14.58 14.58 0 0 0 1.217-1.88l-.004-.005a4.985 4.985 0 0 0 .85-2.115c.13-1.34-.207-2.694-.839-3.836l.016-.022L8.707 1.077a1 1 0 0 0-1.414 0L1.077 7.293l.016.022C.46 8.45-.077 9.8.05 11.141c.115.974.46 1.848.85 2.583l-.004.005.188.112zM2.16 13.28A13.58 13.58 0 0 1 8 14.933a13.58 13.58 0 0 1 5.84-1.653 3.985 3.985 0 0 1-.66-1.83c-.126-1.06-.45-2.012-.99-2.865l.01-.015-4.89-4.89-.01.015c-.852.54-1.804.864-2.864.99A3.985 3.985 0 0 1 2.16 13.28z"/>
        <path d="M13.879 2.121a.5.5 0 1 1 .707.707l-12 12a.5.5 0 0 1-.707-.707l12-12z"/>
      </svg> Cấu hình Loại bỏ Nội dung Quảng cáo
    </h1>
    <hr>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($message['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message['text']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div class="mb-3 form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="removeAdsEnabled" name="remove_ads_enabled" value="1" <?php echo $currentStatus ? 'checked' : ''; ?> onchange="toggleMarkersSection()">
            <label class="form-check-label" for="removeAdsEnabled">
                <strong><?php echo $currentStatus ? 'Đang BẬT' : 'Đang TẮT'; ?></strong> tính năng tự động loại bỏ nội dung quảng cáo/không mong muốn.
            </label>
            <div class="form-text">Nếu bật, hệ thống sẽ tìm và xóa các dòng chứa nội dung khớp với các marker bên dưới.</div>
        </div>

        <div class="markers-section mt-4">
            <h2><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list-task" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M2 2.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5V3a.5.5 0 0 0-.5-.5H2zM3 3H2v1h1V3z"/>
                <path d="M5 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM5.5 7a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zM1.5 7a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5V7zM2 7h1v1H2V7zm0 3.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H2zm1 .5H2v1h1v-1z"/>
                <path d="M5.5 11a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9z"/>
              </svg> Danh sách Nội dung cần xóa (Markers)</h2>
            <p class="form-text">Các dòng chứa nội dung này sẽ bị loại bỏ khỏi kết quả cuối cùng (chỉ áp dụng khi không phải là chế độ dịch HTML đầy đủ hoặc MathJax).</p>

            <div id="marker-list">
                <?php if (empty($currentMarkers)): ?>
                    <p id="no-markers-message" class="text-muted">Chưa có nội dung nào được cấu hình để xóa.</p>
                <?php endif; ?>
                <?php foreach ($currentMarkers as $index => $config): ?>
                    <div class="marker-item" id="marker-item-<?php echo $index; ?>">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label for="marker-text-<?php echo $index; ?>" class="form-label">Nội dung cần tìm:</label>
                                <input type="text" class="form-control form-control-sm" id="marker-text-<?php echo $index; ?>" name="markers[<?php echo $index; ?>][marker]" value="<?php echo htmlspecialchars($config['marker'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="marker-length-<?php echo $index; ?>" class="form-label">Xóa nếu dòng ngắn hơn (ký tự):</label>
                                <input type="number" class="form-control form-control-sm" id="marker-length-<?php echo $index; ?>" name="markers[<?php echo $index; ?>][maxLength]" value="<?php echo htmlspecialchars($config['maxLength'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" min="3" max="100" placeholder="Bỏ trống = không giới hạn">
                                <div class="form-text">(Từ 3 đến 100)</div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="marker-case-<?php echo $index; ?>" name="markers[<?php echo $index; ?>][caseSensitive]" value="1" <?php echo ($config['caseSensitive'] ?? true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="marker-case-<?php echo $index; ?>">
                                        Phân biệt hoa/thường
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <input type="hidden" name="markers[<?php echo $index; ?>][action]" value="update"> <!-- Mặc định là cập nhật -->
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteMarker(<?php echo $index; ?>)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                                      <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5ZM11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H2.506a.58.58 0 0 0-.01 0H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1h-.995a.59.59 0 0 0-.01 0H11Zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5h9.916Zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47ZM8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5Z"/>
                                    </svg> Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="new-marker-container" class="mt-3">
                <!-- Các trường marker mới sẽ được thêm vào đây bằng JavaScript -->
            </div>

            <button type="button" class="btn btn-success mt-3" onclick="addMarkerField()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                  <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                </svg> Thêm Nội dung cần xóa mới
            </button>
        </div>

        <hr>
        <button type="submit" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save" viewBox="0 0 16 16">
              <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v7.293l-2.146-2.147a.5.5 0 0 0-.708 0l-2 2a.5.5 0 0 0 .708.708L7.5 9.207V14a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V8.5a1 1 0 0 0-1-1h-3.5a.5.5 0 0 1-.5-.5v-4a.5.5 0 0 0-.5-.5H2zm8 2a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0v-1a.5.5 0 0 1 .5-.5z"/>
            </svg> Lưu Thay Đổi
        </button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let newMarkerCounter = 0;

    function toggleMarkersSection() {
        const isEnabled = document.getElementById('removeAdsEnabled').checked;
        const markersSection = document.querySelector('.markers-section');
        markersSection.style.display = isEnabled ? 'block' : 'none';
        // Cập nhật text trạng thái Bật/Tắt ngay lập tức (không cần chờ F5)
        const label = document.querySelector('label[for="removeAdsEnabled"] strong');
         if (label) {
             label.textContent = isEnabled ? 'Đang BẬT' : 'Đang TẮT';
         }
    }

    function addMarkerField() {
        newMarkerCounter++;
        const container = document.getElementById('new-marker-container');
        const markerIndex = `new_${newMarkerCounter}`; // Chỉ số duy nhất cho marker mới

        const newField = document.createElement('div');
        newField.classList.add('marker-item', 'border-success'); // Thêm class để phân biệt
        newField.id = `marker-item-${markerIndex}`;
        newField.innerHTML = `
            <div class="row g-3 align-items-end">
                <div class="col-12 text-end">
                     <button type="button" class="btn-close btn-sm" aria-label="Xóa trường mới" onclick="removeNewMarkerField('${markerIndex}')"></button>
                </div>
                <div class="col-md-5">
                    <label for="marker-text-${markerIndex}" class="form-label">Nội dung cần tìm (Mới):</label>
                    <input type="text" class="form-control form-control-sm" id="marker-text-${markerIndex}" name="new_markers[${markerIndex}][marker]" required>
                </div>
                <div class="col-md-3">
                    <label for="marker-length-${markerIndex}" class="form-label">Xóa nếu dòng ngắn hơn:</label>
                    <input type="number" class="form-control form-control-sm" id="marker-length-${markerIndex}" name="new_markers[${markerIndex}][maxLength]" min="3" max="100" placeholder="Bỏ trống = không giới hạn">
                     <div class="form-text">(3-100)</div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="marker-case-${markerIndex}" name="new_markers[${markerIndex}][caseSensitive]" value="1" checked>
                        <label class="form-check-label" for="marker-case-${markerIndex}">
                            Phân biệt hoa/thường
                        </label>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(newField);
        // Hiển thị thông báo nếu chưa có marker nào
        const noMarkersMsg = document.getElementById('no-markers-message');
        if (noMarkersMsg) {
            noMarkersMsg.style.display = 'none';
        }
         // Focus vào ô input đầu tiên của marker mới
         document.getElementById(`marker-text-${markerIndex}`).focus();
    }

     function removeNewMarkerField(markerIndex) {
         const fieldToRemove = document.getElementById(`marker-item-${markerIndex}`);
         if (fieldToRemove) {
             fieldToRemove.remove();
         }
         // Kiểm tra lại xem còn marker nào không để hiển thị thông báo
         checkIfMarkersExist();
     }


    function deleteMarker(index) {
        if (confirm('Bạn có chắc chắn muốn xóa nội dung này khỏi danh sách cần loại bỏ?')) {
            const markerItem = document.getElementById(`marker-item-${index}`);
            if (markerItem) {
                // Thay vì xóa hẳn khỏi DOM, chúng ta ẩn nó đi và thay đổi giá trị hidden field 'action'
                markerItem.style.display = 'none';
                const actionInput = markerItem.querySelector('input[name="markers[' + index + '][action]"]');
                if (actionInput) {
                    actionInput.value = 'delete';
                }
                // Kiểm tra lại xem còn marker nào không để hiển thị thông báo
                checkIfMarkersExist();
            }
        }
    }

     function checkIfMarkersExist() {
         const visibleExistingMarkers = document.querySelectorAll('#marker-list .marker-item[style*="display: none;"]');
         const existingMarkers = document.querySelectorAll('#marker-list .marker-item');
         const newMarkers = document.querySelectorAll('#new-marker-container .marker-item');
         const noMarkersMsg = document.getElementById('no-markers-message');

         if (noMarkersMsg) {
             if (existingMarkers.length === visibleExistingMarkers.length && newMarkers.length === 0) {
                 noMarkersMsg.style.display = 'block';
             } else {
                  noMarkersMsg.style.display = 'none';
             }
         }
     }

    // Gọi lần đầu khi tải trang để ẩn/hiện đúng phần
    document.addEventListener('DOMContentLoaded', function() {
        toggleMarkersSection();
        checkIfMarkersExist(); // Kiểm tra khi tải trang
    });
</script>

</body>
</html>