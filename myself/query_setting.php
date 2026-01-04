<?php
// --- KHAI BÁO BIẾN VÀ ĐƯỜNG DẪN ---
define('CONFIG_FILE', __DIR__ . '/config.php'); // File chứa các cấu hình quan trọng của ứng dụng
define('MODEL_ID_FILE', __DIR__ . '/model_id.json'); // File chứa các model của ứng dụng

$models = []; // Nơi lưu danh sách các model
$current_model = ''; // Biến lưu model AI đang dùng
$message = ''; // Biến lưu các cảnh báo
$error = ''; // Biến thông báo lỗi

// --- ĐỌC DANH SÁCH MODEL TỪ JSON ---
if (file_exists(MODEL_ID_FILE)) { // Kiểm tra sự tồn tại của file trong ứng dụng
    $json_content = file_get_contents(MODEL_ID_FILE); // Lấy nội dung file
    $models = json_decode($json_content, true); // Chuyển đổi thông tin đưa vào mảng các model
    if ($models === null) { // Khi model rỗng
        $error = "Lỗi: Không thể giải mã file model_id.json. Vui lòng kiểm tra định dạng JSON.";
        $models = []; // Đảm bảo là mảng rỗng nếu lỗi
    }
} else { // Thông báo lỗi
    $error = "Lỗi: File model_id.json không tồn tại.";
}

// --- ĐỌC CÀI ĐẶT HIỆN TẠI TỪ config.php ---
if (empty($error) && file_exists(CONFIG_FILE)) {
    $config_content = file_get_contents(CONFIG_FILE);

    // Trích xuất Model hiện tại từ URL API bằng regex chính xác hơn
    $regex_model = "/define\(\s*'IS_USING_MODEL_ID_SEARCH'\s*,\s*'(.*?)'\s*\);/i";
    if (preg_match($regex_model, $config_content, $matches_model)) {
        $current_model = $matches_model[1]; // Group 1 chứa tên model
    } else {
         // Cảnh báo nếu không tìm thấy định nghĩa URL hoặc không đúng định dạng mong đợi
         $message .= "Cảnh báo: Không tìm thấy hoặc không thể phân tích IS_USING_MODEL_ID_SEARCH trong config.php";
         // Gợi ý: Kiểm tra lại cấu trúc dòng define GEMINI_API_URL trong config.php
    }


    // Kiểm tra xem model đọc được có trong danh sách không
    if ($current_model && !empty($models) && !in_array($current_model, $models)) {
        $message .= "Cảnh báo: Model hiện tại ('" . htmlspecialchars($current_model) . "') nằm trong cấu hình config.php không có trong danh sách model_id.json.<br>";
        // Có thể chọn model đầu tiên làm mặc định nếu model hiện tại không hợp lệ
        // $current_model = !empty($models) ? $models[0] : '';
    } elseif (!$current_model && strpos($message, 'Không tìm thấy hoặc không thể phân tích IS_USING_MODEL_ID_SEARCH') === false) {
        // Trường hợp regex không match nhưng không có cảnh báo trước đó (ít xảy ra)
        $message .= "Cảnh báo: Không thể trích xuất tên model từ IS_USING_MODEL_ID_SEARCH trong config.php.<br>";
    }


} elseif (empty($error)) {
    $error = "Lỗi: File config.php không tồn tại.";
}

$change = 0; // Cờ dùng để kiểm tra xem có sự thay đổi nào không

// --- XỬ LÝ FORM KHI SUBMIT (METHOD POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {// Khi người dùng bấm nút
    // Lấy dữ liệu từ form, làm sạch cơ bản
    $selected_model = trim($_POST['model'] ?? ''); // Lấy thông tin model từ select

    // --- VALIDATION DỮ LIỆU ---
    if(!in_array($selected_model, $models)) { // Khi nó không có trong mảng
        $error = "Lỗi: Model đã chọn không hợp lệ.";
    } elseif (!file_exists(CONFIG_FILE)) {
        $error = "Lỗi: File config.php không tồn tại để cập nhật.";
    } elseif (!is_writable(CONFIG_FILE)) {
        $error = "Lỗi: File config.php không có quyền ghi. Vui lòng kiểm tra quyền truy cập file trên server.";
    } else {
        // --- CẬP NHẬT FILE config.php ---
        $config_content_to_update = file_get_contents(CONFIG_FILE); // Đọc lại nội dung mới nhất
        
        // Nếu model được chọn khác với model hiện tại thì tiến hành cập nhật
        if ($current_model != $selected_model) {
            $config_content_to_update = preg_replace(
                "/define\(\s*'IS_USING_MODEL_ID_SEARCH'\s*,\s*'.*?'\s*\);/i", // Regex chung hơn
                "define('IS_USING_MODEL_ID_SEARCH', '" . $selected_model . "');",
                $config_content_to_update,
                1,
                $count_model
            );        

            if ($count_model === 0) {
                $error = "Lỗi: Không thể tìm thấy hoặc cập nhật dòng định nghĩa IS_USING_MODEL_ID_SEARCH trong config.php. Vui lòng kiểm tra lại file.";
            } else {
                $message .= "Đã cập nhật MODEL cho mục đích dịch truy vấn. ";
                $change = 1;
            }
        }


        // Chỉ ghi file nếu không có lỗi nghiêm trọng trong quá trình thay thế URL
        if (empty($error)) {
            // Ghi nội dung đã cập nhật vào file config.php
            if (file_put_contents(CONFIG_FILE, $config_content_to_update) !== false) {
                if ($change == 1) {
                    $message .= 'Lưu thành công! Truy cập <a href="../search.php">Tìm kiếm nội dung tiếng Anh bằng tiếng Việt.</a>';
                } else {
                    $message .= 'Không có thay đổi nào được phát hiện. Dữ liệu vẫn giữ nguyên.';
                }
                $current_model = $selected_model;
            } else {
                $error = "Lỗi: Không thể ghi vào file config.php.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt Gemini API | silaTranslator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro&display=swap" rel="stylesheet">  
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../images/apple-touch-icon.png">
    <link rel="stylesheet" href="../css/shared.css?v=2">
    <style>
        /* Giữ nguyên CSS như phiên bản trước */
        .container {
            padding: 10px 30px 25px 30px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Important to include padding in width */
            font-family: "Be Vietnam Pro", 'Roboto', sans-serif;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            text-align: center;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
        .message.warning {
             background-color: #fff3cd;
             color: #856404;
             border: 1px solid #ffeeba;
             text-align: center;
         } 
         
        /* CSS mới cho input API Key và icon */
        .api-key-wrapper {
            position: relative; /* Chứa icon */
            margin-bottom: 15px; /* Giữ khoảng cách dưới như cũ */
        }

        .api-key-wrapper input[type="password"],
        .api-key-wrapper input[type="text"] { /* Áp dụng cho cả hai trạng thái */
            padding-right: 40px; /* Tạo khoảng trống bên phải cho icon */
            width: 100%; /* Đảm bảo input vẫn chiếm đủ chiều rộng */
            /* Kế thừa các style khác từ input[type="text"] */
            padding: 10px; 
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; 
            font-family: "Be Vietnam Pro", 'Roboto', sans-serif;
            /* Xóa margin-bottom riêng của input vì wrapper đã có */
            margin-bottom: 0; 
        }
        
        /* CSS riêng cho con mắt  */
        .api-key-wrapper #toggleApiKey {
            position: absolute;
            right: 10px;        /* Cách lề phải */
            top: 50px;           /* Căn giữa theo chiều dọc */
            transform: translateY(-50%); /* Tinh chỉnh căn giữa */
            cursor: pointer;    /* Con trỏ tay */
            color: #555;        /* Màu cho icon */
            user-select: none;  /* Ngăn chọn văn bản icon */
        }         
    </style>
</head>
<body>
    <div class="container">
        <h1>Model cho dịch truy vấn tìm kiếm</h1>
        <p style="margin-top: -15px;">
            Khu vực này dùng để chọn model cho nhiệm vụ dịch <a href="../search.php">truy vấn tìm kiếm</a> & <a href="../search.php?scholar=true">truy vấn tìm tài liệu PDF</a>. Nó có thể giống hoặc khác <a href="setting.php" target="_blank">model dùng để dịch bài viết</a>. 
            Vì truy vấn tìm kiếm thường ngắn (tức là không quá khó để dịch), và chúng ta thường cần tốc độ cao cho nhiệm vụ này, do vậy bạn nên lựa giải pháp cân bằng là chọn model có khả năng xử lý nhanh dù có thể không phải mạnh nhất.  
            Có lẽ là bạn nên nhắm đến các model có chữ <code>flash</code> trong tên. Tất nhiên đây chỉ là gợi ý, nếu bạn quan tâm đến chất lượng, hãy chọn model mạnh nhất để dịch truy vấn.
        </p>
        <?php if ($error): ?>
            <div class="message error"><?php echo nl2br(htmlspecialchars($error)); // Hiển thị lỗi nếu có ?></div>
        <?php endif; ?>

        <?php if ($message && !$error): // Chỉ hiện message thành công/cảnh báo nếu không có lỗi ?>
             <?php
                 $message_class = 'warning'; // Mặc định là cảnh báo
                 if (strpos($message, 'thành công') !== false) {
                     $message_class = 'success';
                 } elseif (strpos($message, 'Lưu ý') !== false) {
                     $message_class = 'warning';
                 }
             ?>
            <div class="message <?php echo $message_class; ?>"><?php echo $message; // Hiển thị thông báo ?></div>
        <?php endif; ?>

        <?php if (!empty($models)): // Chỉ hiển thị form nếu model tải thành công ?>
        <form action="query_setting.php" method="post">
            <div>
                <label for="model">Chọn Model:</label>
                <select name="model" id="model" required>
                    <?php foreach ($models as $model_id): ?>
                        <option value="<?php echo htmlspecialchars($model_id); ?>"
                            <?php if ($model_id === $current_model) echo ' selected'; ?>>
                            <?php echo htmlspecialchars($model_id); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php // Thêm tùy chọn cho model hiện tại nếu nó không có trong list (hiếm gặp)
                        if ($current_model && !in_array($current_model, $models)): ?>
                        <option value="<?php echo htmlspecialchars($current_model); ?>" selected disabled>
                            <?php echo htmlspecialchars($current_model); ?> (Hiện tại - Không có trong list)
                        </option>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <button type="submit" name="save_settings">Lưu Cài Đặt</button>
            </div>
        </form>
        <?php elseif(empty($error)): // Nếu không có lỗi nhưng models rỗng ?>
            <p style="text-align: center; color: #555;">Không có model nào được tìm thấy trong file <code>model_id.json</code>.</p>
        <?php endif; ?>
        
            <!-- ===================== Nút Tiện Ích Phụ ===================== -->
            <div class="utility-buttons">
                <a href="small_settings.php" class="utility-button">Cài đặt Nhỏ</a>
                <a href="runAI_settings.php" class="utility-button">Chỉnh tham số API</a>
                <a href="../index.php" class="utility-button">Dịch trang web</a>
                <a href="../translation_PDF_HTML.php" class="utility-button">Dịch PDF</a>
                <a href="model_config.php" class="utility-button">Thêm bớt Model</a>
            </div>
            <!-- ===================== KẾT THÚC Nút Tiện Ích Phụ ===================== -->
        
    </div>   
</body>
</html>