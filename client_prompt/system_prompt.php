<?php

require dirname(__DIR__) . DIRECTORY_SEPARATOR. 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

// ---- Cấu hình Đường dẫn ----
define('BASE_PATH', dirname(__DIR__)); // Do vị trí của file này
define('PANDOC_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pandoc.exe');
define('DOCX_DIR', BASE_PATH . DIRECTORY_SEPARATOR . 'docx_temp');
define('MARKDOWN_DIR', BASE_PATH . DIRECTORY_SEPARATOR . 'markdown_client_prompt');
// --------------------------

$errorMessage = '';
$successMessage = '';
$markdownLink = '';
$finalMarkdownFileName = ''; // Biến để lưu tên file cuối cùng (prompt.md hoặc system_instructions.md)

function ensureDirectoryExists(string $dir): bool {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            return false;
        }
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Thêm bước kiểm tra Mục đích ---
    if (!isset($_POST['purpose']) || ($_POST['purpose'] !== 'prompt' && $_POST['purpose'] !== 'system_instructions')) {
        $errorMessage = "Lỗi: Vui lòng chọn mục đích hợp lệ (Prompt hoặc systemInstructions).";
    } else {
        $purpose = $_POST['purpose']; // Lấy mục đích người dùng chọn

        // Xác định tên file Markdown cố định dựa trên mục đích
        if ($purpose === 'prompt') {
            $finalMarkdownFileName = 'prompt.md';
        } else {
            $finalMarkdownFileName = 'system_instructions.md';
        }
        // -----------------------------------

        if (isset($_FILES['docx_file']) && $_FILES['docx_file']['error'] === UPLOAD_ERR_OK) {
            $fileInfo = pathinfo($_FILES['docx_file']['name']);
            $fileExtension = strtolower($fileInfo['extension'] ?? '');

            if ($fileExtension === 'docx') {
                if (!ensureDirectoryExists(DOCX_DIR)) {
                    $errorMessage = "Lỗi nghiêm trọng: Không thể tạo hoặc truy cập thư mục 'docx'.";
                } elseif (!ensureDirectoryExists(MARKDOWN_DIR)) {
                    $errorMessage = "Lỗi nghiêm trọng: Không thể tạo hoặc truy cập thư mục 'markdown'.";
                } else {
                    // Vẫn tạo tên file DOCX tạm thời duy nhất để tránh xung đột khi upload
                    $originalFileName = $fileInfo['filename']; // Lấy tên file gốc
                    $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFileName); // Lấy tên an toàn dựa trên tên file gốc
                    
                    // Đặt tên file docx tạm thời, không cần quá phức tạp vì sẽ bị xóa hoặc không quan trọng
                    $tempDocxFileName = 'temp_upload_' . $purpose . '_' . time() . '.docx';
                    $uploadedDocxPath = DOCX_DIR . DIRECTORY_SEPARATOR . $tempDocxFileName;

                    // Đường dẫn file Markdown output sử dụng tên CỐ ĐỊNH
                    $outputMarkdownPath = MARKDOWN_DIR . DIRECTORY_SEPARATOR . $finalMarkdownFileName;

                    if (move_uploaded_file($_FILES['docx_file']['tmp_name'], $uploadedDocxPath)) {
                        if (!file_exists(PANDOC_PATH)) {
                            $errorMessage = "Lỗi: Không tìm thấy Pandoc tại '" . htmlspecialchars(PANDOC_PATH) . "'.";
                            unlink($uploadedDocxPath); // Xóa file docx tạm
                        } else {
                            $process = new Process([
                                PANDOC_PATH,
                                $uploadedDocxPath,
                                '-f', 'docx',
                                '-t', 'commonmark', // hoặc gfm hoặc markdown, nhưng nên để commonmark để nó bắt chặt hơn nhằm tạo file chuẩn
                                '--wrap=none', // ngăn việc ngắt dòng
                                '-o', $outputMarkdownPath // Ghi đè lên file đích cố định
                            ]);

                            try {
                                $process->mustRun();
                                
                                // ---- BẮT ĐẦU POST-PROCESSING ----
                                $markdownContent = file_get_contents($outputMarkdownPath);
                                if ($markdownContent !== false) {
                                    // Loại bỏ dấu \ đứng trước các ký tự Markdown phổ biến
                                    // Lưu ý: Dùng \\ để khớp với một dấu \ trong chuỗi PHP
                                    // Sửa việc thêm thừa dấu \ đằng trước các ký tự markdown
                                    $correctedMarkdown = str_replace(
                                        ['\\*', '\\`', '\\#', '\\_', "\\'"], // Tìm: \*, \`, \#, \_, \'
                                        ['*', '`', '#', '_', "'"],        // Thay bằng: *, `, #, _, '
                                        $markdownContent
                                    );

                                    // Có thể thêm các ký tự khác nếu cần, ví dụ: '\\`' => '`'

                                    // Ghi lại nội dung đã sửa vào file
                                    file_put_contents($outputMarkdownPath, $correctedMarkdown);

                                } else {
                                    // Có thể ghi log lỗi nếu không đọc được file
                                    throw new \RuntimeException("Không thể đọc file Markdown sau khi tạo: " . $outputMarkdownPath);
                                }
                                // ---- KẾT THÚC POST-PROCESSING ----                                

                                // Thông báo thành công rõ ràng hơn
                                $successMessage = "Đã cập nhật thành công file '" . htmlspecialchars($finalMarkdownFileName) . "' từ file '" . htmlspecialchars($_FILES['docx_file']['name']) . "'.";
                                // Tạo link đến file view.php với tên file cố định
                                $markdownLink = 'view.php?file=' . urlencode($finalMarkdownFileName);

                                // Xóa file docx tạm thời sau khi chuyển đổi thành công
                                if (file_exists($uploadedDocxPath)) {
                                    unlink($uploadedDocxPath);
                                }

                            } catch (ProcessFailedException $exception) {
                                $errorMessage = "Lỗi trong quá trình chuyển đổi Pandoc: <br><pre>" . htmlspecialchars($exception->getMessage()) . "</pre>";
                                // Xóa file docx tạm nếu lỗi
                                if (file_exists($uploadedDocxPath)) unlink($uploadedDocxPath);
                                // Không nên xóa file markdown đích nếu lỗi, vì có thể nó là phiên bản cũ hoạt động tốt
                            }
                        }
                    } else {
                        $errorMessage = "Lỗi: Không thể di chuyển file đã tải lên.";
                    }
                }
            } else {
                $errorMessage = "Lỗi: Chỉ chấp nhận file có định dạng .docx.";
            }
        } elseif (isset($_FILES['docx_file']['error']) && $_FILES['docx_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Chỉ báo lỗi nếu có lỗi thực sự, không phải là chưa chọn file
             switch ($_FILES['docx_file']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE: $errorMessage = "Lỗi: Kích thước file quá lớn."; break;
                case UPLOAD_ERR_PARTIAL: $errorMessage = "Lỗi: File chỉ được tải lên một phần."; break;
                default: $errorMessage = "Lỗi không xác định khi tải file lên."; break;
            }
        } elseif (!isset($_FILES['docx_file']) || $_FILES['docx_file']['error'] === UPLOAD_ERR_NO_FILE) {
             // Nếu đã chọn mục đích mà chưa chọn file
             if ($purpose) { // $purpose đã được set ở đầu khối POST
                $errorMessage = "Lỗi: Vui lòng chọn một file DOCX để tải lên.";
             }
             // Nếu không có lỗi khác và không có file được chọn, không hiển thị lỗi gì cả (trạng thái ban đầu)
        }
    } // Kết thúc kiểm tra mục đích
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
    <title>Tùy chỉnh prompt & systemInstructions | silaTranslator</title>
    <style>
        /* Giữ nguyên CSS từ trước */
        body { font-family: "Be Vietnam Pro", 'Roboto', sans-serif; font-size:1.1em; line-height: 1.6; padding: 20px; max-width: 700px; margin: auto; }
        h1 { font-size:2em; }
        .form-container { border: 1px solid #ccc; padding: 20px; border-radius: 5px; background-color: #f9f9f9; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="file"] { font-family: "Be Vietnam Pro", 'Roboto', sans-serif; border: 1px solid #ccc; padding: 5px; width: calc(100% - 12px); } /* Chiều rộng */
        input[type="radio"] { margin-right: 5px; }
        .radio-group label { display: inline-block; margin-right: 15px; font-weight: normal;} /* Radio label inline */
        button {font-family: "Be Vietnam Pro", 'Roboto', sans-serif; padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 1em; }
        button:hover { background-color: #0056b3; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        pre { background-color: #eee; padding: 10px; border: 1px solid #ccc; white-space: pre-wrap; word-wrap: break-word; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <h1>Tùy chỉnh prompt & systemInstructions</h1>
    <p style="text-align: center;"><span style="font-size: 2em;">⚠️</span> Cẩn thận khi sử dụng khu vực này</p>
    <p><strong>LƯU Ý</strong>: prompt & systemInstructions mặc định của hệ thống đã được thiết lập kỹ lưỡng và đủ tốt. Bạn không phải làm gì vẫn dùng chương trình bình thường. Mục này chỉ dành cho người dùng nâng cao có khả năng tạo prompt & systemInstructions chất lượng cao theo ý muốn để ghi đè lên mặc định cũ.</p>
    <p>Prompt & systemInstructions tùy chỉnh của bạn chỉ áp dụng với chế độ dịch *<strong>Chỉ dịch văn bản</strong>*. Điều này là có chủ đích vì nó sẽ viết dễ hơn đáng kể.</p>
    <h2>Hướng dẫn:</h2>
    <p>Tải lên file .docx để tạo hoặc ghi đè lên file <code>prompt</code> hoặc <code>systemInstructions</code> cũ.</p>
    <p>Khi up lên <strong>nhớ chọn mục tương ứng</strong>, cho prompt (lời nhắc) hay cho systemInstructions (hướng dẫn hệ thống). Xin chú ý là tên file của bạn không quan trọng lắm nhưng phần chọn mục tương ứng rất quan trọng để chương trình biết phải ghi đè vào đâu.</p>
    <p>Bạn vào một trình soạn thảo Docx bất kỳ (ví dụ Google Docs) và viết prompt và systemInstructions, sau đó lưu lại từng file (với định dạng docx) rồi up lên đây, và dĩ nhiên phải up cả 2!</p>
    <p>Ví dụ 2 file docx mẫu: <a href="../sys_prompt_docx/prompt.docx">prompt (docx)</a> & <a href="../sys_prompt_docx/system_instructions.docx">system_instructions (docx)</a>. Đây chính là các prompt và systemInstructions mặc định của chương trình.</p>
    <p>Sau khi up lên thành công (bạn thấy đủ tên 2 file ở dưới cùng) & muốn áp dụng chúng thì bạn cần phải vào <a href="../myself/small_settings.php" target="_blank" class="utility-button">Cài đặt Nhỏ</a> và bật 'Sử dụng prompt & systemInstructions của tôi...'. Nếu không bật tùy chọn đó thì chương trình sẽ vẫn dùng prompt & systemInstructions của hệ thống kể cả bạn đã up 2 file ở khu vực này.</p>
    <p><strong>Đừng quá lo</strong>: Ngay cả khi prompt & systemInstructions của bạn không tạo ra kết quả ưng ý, bạn vẫn luôn có tùy chọn tắt nó để quay về sử dụng mặc định của hệ thống (tắt trong phần <a href="../myself/small_settings.php" target="_blank" class="utility-button">Cài đặt Nhỏ</a> / Kéo xuống cuối để tắt hoặc bật).</p>
    
    <div class="form-container">
        <form action="system_prompt.php" method="post" enctype="multipart/form-data">
            <!-- Thêm lựa chọn mục đích -->
            <div class="form-group radio-group">
                <label><strong>File up lên là dùng cho?</strong></label>
                <label for="purpose_prompt">
                    <input type="radio" id="purpose_prompt" name="purpose" value="prompt" required checked> prompt
                </label>
                <label for="purpose_instructions">
                    <input type="radio" id="purpose_instructions" name="purpose" value="system_instructions" required> systemInstructions
                </label>
            </div>

            <div class="form-group">
                <label for="docx_file">Chọn file DOCX:</label>
                <input type="file" id="docx_file" name="docx_file" accept=".docx" required>
            </div>
            <button type="submit">Tải lên</button>
        </form>
    </div>

    <?php if ($errorMessage): ?>
        <div class="message error">
            <strong>Lỗi:</strong><br> <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="message success">
            <?php echo $successMessage; ?>
            <br>
            <?php if ($markdownLink): ?>
                Nhấp vào đây để xem nội dung file
                <strong><?php echo htmlspecialchars($finalMarkdownFileName); ?></strong>:
                <a href="<?php echo htmlspecialchars($markdownLink); ?>" target="_blank">
                    Xem nội dung
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Hiển thị link đến các file hiện có (nếu có) -->
    <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee;">
        <h2>Các file prompt / systemInstructions hiện có:</h2>
        <?php
            $promptFilePath = MARKDOWN_DIR . DIRECTORY_SEPARATOR . 'prompt.md';
            $instructionsFilePath = MARKDOWN_DIR . DIRECTORY_SEPARATOR . 'system_instructions.md';
            $foundFiles = false;

            if (file_exists($promptFilePath)) {
                echo '<p>📄 <a href="view.php?file=prompt.md" target="_blank">Xem prompt</a></p>';
                $foundFiles = true;
            }
             if (file_exists($instructionsFilePath)) {
                echo '<p>📄 <a href="view.php?file=system_instructions.md" target="_blank">Xem systemInstructions</a></p>';
                $foundFiles = true;
            }
            if (!$foundFiles) {
                echo "<p><em>Chưa có file nào được tạo.</em></p>";
            }
        ?>
    </div>

</body>
</html>