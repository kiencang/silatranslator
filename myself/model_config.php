<?php
// File: myself/model_config.php
// --- Cấu hình ---
$jsonFilePath = __DIR__ . '/model_id.json'; // Đường dẫn tới file JSON
$numModels = 5; // Số lượng model hiển thị
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------


// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Các biến ---
$models = array_fill(0, $numModels, ''); // Khởi tạo mảng hiển thị với 5 chuỗi rỗng
$message = ''; // Thông báo lỗi hoặc thành công
$message_type = ''; // 'success' or 'error' // Cờ thành công hoặc thất bại
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Hảm tải và hiển thị các model ---
/*
 * @param $filePath là đường dẫn đến file JSON
 * @param $numDisplay là số hiển thị
 * @return array trả về mảng kết quả
 */
function load_models($filePath, $numDisplay): array {
    $loaded = []; // Tạo mảng
    if (file_exists($filePath)) { // Kiểm tra sự tồn tại của đường dẫn
        $jsonContent = file_get_contents($filePath); // Lấy nội dung
        $decoded = json_decode($jsonContent, true); // decode file JSON
        if (is_array($decoded)) { // Kiểm tra xem đúng phải mảng không
            // Chỉ lấy các chuỗi model hợp lệ
            foreach($decoded as $model) {
                if(is_string($model) && trim($model) !== '') { // Xác nhận là chuỗi và có nội dung, không rỗng
                    $loaded[] = $model; // Đưa vào mảng
                }
            }
        } else {

            // Xử lý khi phát hiện JSON không hợp lệ // Trả về mảng rỗng gồm $numDisplay phần tử kèm báo lỗi ở error
            return ['models' => array_fill(0, $numDisplay, ''), 'error' => "Lỗi: File model_id.json không chứa dữ liệu JSON hợp lệ."];
        }
    }

    // Thêm mảng đã tải bằng các chuỗi rỗng cho mục đích hiển thị
    $displayModels = $loaded; // Start with valid loaded models
    while (count($displayModels) < $numDisplay) { // Vì yêu cầu hiển thị đủ nên cần phải bổ sung phần tử vào mảng nếu thiếu
        $displayModels[] = ''; // Nếu số lượng nhỏ hơn yêu cầu (5) thì bổ sung chuỗi rỗng vào mảng
    }
    
    // Cắt bớt nếu tệp JSON bằng cách nào đó có nhiều mô hình hợp lệ hơn $numDisplay (không có khả năng với logic lưu)
    if (count($displayModels) > $numDisplay) {
        // Chỉ lấy đúng số lượng cần hiển thị
        $displayModels = array_slice($displayModels, 0, $numDisplay);
    }
    
    // Trả về mảng models kèm báo lỗi là null / không lỗi
    return ['models' => $displayModels, 'error' => null];
}
//E  -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Lúc trang tải lần đầu ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $loadResult = load_models($jsonFilePath, $numModels);
    $models = $loadResult['models']; // Danh sách các model
    
    if ($loadResult['error']) { // Nếu có lỗi
        $message = $loadResult['error'];
        $message_type = 'error';
    } elseif (!file_exists($jsonFilePath)) {
        $message = "Thông báo: File model_id.json không tồn tại. Lưu sẽ tạo file mới.";
        $message_type = 'info';
    }
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Xử lý khi người dùng thao tác với Form ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Bấm nút
        $loadResultX = load_models($jsonFilePath, $numModels);
        $models_in_file = $loadResultX['models']; // Dùng để so sánh xem có cần lưu không    
    
        $submittedModelsRaw = isset($_POST['models']) && is_array($_POST['models']) ? $_POST['models'] : []; // Một kiểu rút gọn của hàm if else
        $modelsToSave = []; // Mảng để chứa các model không rỗng để lưu
        $modelsForDisplayPost = array_fill(0, $numModels, ''); // Mảng để điền lại vào form

        // Xử lý dữ liệu đã gửi: Điền $modelsToSave (đã lọc) và $modelsForDisplayPost (thô, để điền lại biểu mẫu)
        for ($i = 0; $i < $numModels; $i++) { // Lấy từng dữ liệu input để đưa vào mảng
            $value = isset($submittedModelsRaw[$i]) ? trim($submittedModelsRaw[$i]) : '';
            // Lưu trữ giá trị có khả năng trống để hiển thị nếu xác thực/lưu không thành công
            $modelsForDisplayPost[$i] = $value; // Lưu trữ giá trị có khả năng trống để hiển thị nếu xác thực/lưu không thành công 

            if (!empty($value)) {
                $modelsToSave[] = $value; // Chỉ thêm các model không rỗng vào danh sách lưu
            }
        }

        // --- Kiểm tra trùng lặp model ---
        $modelCounts = array_count_values($modelsToSave);
        $duplicateModels = [];

        foreach ($modelCounts as $modelName => $count) { // Kiểm tra chống trùng lặp là quan trọng để người dùng không nhập thông tin thừa không cần thiết
            if ($count > 1) {
                $duplicateModels[] = $modelName;
            }
        }

        if (!empty($duplicateModels)) {
            $message = "Lỗi: Các model sau bị trùng lặp: " . htmlspecialchars(implode(", ", $duplicateModels)) . ". Vui lòng sửa lại.";
            $message_type = 'error';
            $models = $modelsForDisplayPost; // Hiển thị lại dữ liệu đã nhập để người dùng sửa
            // Không tiếp tục lưu, dừng lại ở đây
        } else if (empty($modelsToSave)) { // Không được để trống không nhập model
            $message = "Lỗi: Phải có ít nhất một model được nhập.";
            $message_type = 'error';
            $models = $modelsForDisplayPost; // Hiển thị các trường trống mà người dùng đã gửi
        } else {
            // --- Tiến hành lưu file JSON ---
            // Mã hóa mảng FILTERED ($modelsToSave) thành JSON
            $jsonOutput = json_encode($modelsToSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            // Kiểm tra xem có sự thay đổi thì mới cần lưu
            if ($modelsToSave !== $models_in_file) {
                // Ghi file
                if (file_put_contents($jsonFilePath, $jsonOutput) !== false) {
                    $message = "Đã lưu danh sách model thành công!";
                    $message_type = 'success';

                    // Tải lại các mô hình từ tệp để hiển thị chính xác những gì đã được lưu
                    $loadResult = load_models($jsonFilePath, $numModels);
                    $models = $loadResult['models'];
                    // Không cần phải kiểm tra $loadResult['error'] ở đây, vì chúng ta vừa ghi thành công
                } else {
                    $message = "Lỗi: Không thể ghi vào file model_id.json. Vui lòng kiểm tra quyền ghi (permissions) của thư mục.";
                    $message_type = 'error';

                    // Hiển thị dữ liệu mà người dùng ĐÃ THỬ lưu
                    $models = $modelsForDisplayPost; // Hiển thị những gì người dùng đã nhập ban đầu nhưng không lưu được
                }
            } else {
                    $message = "Thông báo: Bạn không thực hiện bất cứ thay đổi nào.";
                    $message_type = 'info';
                    $models = $modelsForDisplayPost; // Hiển thị lại dữ liệu đã nhập để người dùng sửa nếu cần
            }
        }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm bớt các Model Gemini hiện Có | silaTranslator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro&display=swap" rel="stylesheet"> 
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../images/apple-touch-icon.png"> 
    <link rel="stylesheet" href="../css/shared.css?v=4">
    <style>
        body {
            padding-top: 30px;
            padding-bottom: 30px;
        }
        .container {
            margin: auto; 
            padding: 25px; 
        }
        
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
        }
        
        input[type="text"] {
            width: calc(100% - 22px); /* Account for padding and border */
            padding: 10px;
            font-family: "Be Vietnam Pro", 'Roboto', sans-serif; 
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        
        img {
            width: 100%;
            margin-bottom: 10px;
        }
        
        /* Ẩn hiện thông tin phụ */
        
        #hide-show-container {
            display: none; /* Ẩn box tin tức mặc định */
        }
        
        /* Định dạng cho nút/link kích hoạt */
        .toggle-info-button {
            display: block; /* Chiếm cả dòng để dễ căn giữa */
            width: fit-content; /* Độ rộng vừa với nội dung */
            margin: -10px auto 10px auto; /* Căn giữa và tạo khoảng cách */
            padding: 8px 15px;
            font-size: 0.9em;
            font-weight: bold;
            color: #0066cc;
            background-color: transparent; /* Nền trong suốt */
            border: none; /* Bỏ viền */
            /* border-bottom: 1px dashed #0066cc;  Có thể thêm gạch chân nếu muốn */
            cursor: pointer;
            text-align: center;
            transition: color 0.2s ease;
        }

        .toggle-info-button:hover {
            color: #004c99;
            /* border-bottom-style: solid; */
        }         
    </style>
</head>
<body>
    <div class="container">
        <h1>Thêm bớt các Model Gemini</h1>
        
        <button type="button" id="toggle-info-button" class="toggle-info-button">
            Xem ý nghĩa của phần này ▼ 
        </button> 
        
        <!-- ===================== Thông tin để người dùng biết ý nghĩa của form ===================== -->
        <p style="margin-top: 5px;" id="hide-show-container">
            Trang này được dùng để thêm bớt các model vào danh sách. Danh sách model này sẽ xuất hiện trong trang <a href="setting.php">Lựa chọn model</a>. 
            Các model sẽ xuất hiện theo đúng thứ tự như danh sách này. Bạn có thể sửa, xóa các model, sắp xếp lại thứ tự. 
            Danh sách chỉ có tối đa 5 model. Nên để model thường dùng ở vị trí cao trong danh sách (không liên quan gì đến tính năng, nhưng giúp bạn chọn nhanh hơn). 
            Bạn cần nhập chính xác tên model, tốt nhất là copy - paste nó từ nguồn uy tín, ví dụ <a href="https://aistudio.google.com/" target="_blank">Google AI Studio</a> (một trong các trang chính thức của Gemini).
        </p>    
        
        <!-- <img src="../images/model_id.png"> -->
        
        <?php if (!empty($message)): // Thông báo lỗi và thành công hiển thị ở khu vực này, class theo $message_type để tiện CSS ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="model_config.php" method="post">
            <?php for ($i = 0; $i < $numModels; $i++): ?>
                <div>
                    <?php 
                    // Label logic could be improved if you want dynamic labels like "Model 1", "Model 2" even after deletion 
                    // name="models[]" là cú pháp chuẩn để báo cho PHP gom nhóm các giá trị từ nhiều input cùng tên thành một mảng, rất hữu ích khi làm việc với danh sách dữ liệu trong form.
                    ?>
                    <label for="model_<?php echo $i; ?>">Model Input <?php echo $i + 1; ?>:</label>
                    <input
                        type="text"
                        id="model_<?php echo $i; ?>"
                        name="models[]"
                        value="<?php echo isset($models[$i]) ? htmlspecialchars($models[$i]) : ''; // Đảm bảo chỉ mục tồn tại trước khi truy cập ?>"
                        placeholder="Nhập ID của model hoặc để trống để xóa"
                    >
                </div>
            <?php endfor; ?>

            <button type="submit" name="save_settings">Lưu Model</button>
        </form>

        <!-- ===================== Nút Tiện Ích Phụ ===================== -->
        <div class="utility-buttons">
            <a href="setting.php" class="utility-button">Chọn Model</a>
            <a href="runAI_settings.php" class="utility-button">Chỉnh tham số API</a>
            <a href="../index.php" class="utility-button">Dịch trang web</a>
            <a href="../translation_PDF_HTML.php" class="utility-button">Dịch PDF</a>
            <a href="small_settings.php" class="utility-button">Cài đặt Nhỏ</a>
        </div>
        <!-- ===================== KẾT THÚC Nút Tiện Ích Phụ ===================== -->
    </div>
    
    <script>
        const toggleButton = document.getElementById('toggle-info-button');
        const newsContainer = document.getElementById('hide-show-container');

        // Kiểm tra xem nút và container có tồn tại không
        if (toggleButton && newsContainer) {
            toggleButton.addEventListener('click', function() {
                // Kiểm tra trạng thái hiện tại của container
                if (newsContainer.style.display === 'none' || newsContainer.style.display === '') {
                    // Nếu đang ẩn, thì hiện ra
                    newsContainer.style.display = 'block';
                    // Đổi nội dung nút/link thành "Ẩn tin tức" và mũi tên lên
                    toggleButton.innerHTML = 'Ẩn giải thích ý nghĩa ▲';
                } else {
                    // Nếu đang hiện, thì ẩn đi
                    newsContainer.style.display = 'none';
                    // Đổi nội dung nút/link trở lại ban đầu
                    toggleButton.innerHTML = 'Xem ý nghĩa của phần này ▼';
                }
            });
        }  
    </script>      
</body>
</html>