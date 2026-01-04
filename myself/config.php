<?php
// File: myself/config.php
// --- !!! TUYỆT ĐỐI KHÔNG ĐƯA FILE NÀY VÀO VERSION CONTROL CÔNG KHAI !!! ---
// Cách tốt nhất là dùng biến môi trường (environment variables) trên server.
define('GEMINI_API_KEY', 'Xóa dòng này và nhập API Key của bạn vào đây'); // Thay bằng API Key thực của bạn

// Cấu hình tăng tối đa chất lượng dịch web
define('MAXIMIZE_TRANSLATION_QUALITY', false);

// Cấu hình tăng tối đa chất lượng dịch PDF
define('MAXIMIZE_TRANSLATION_QUALITY_PDF', false);

// Có đưa vào ví dụ để AI học mẫu không?
define('EXAMPLE_FOR_TRANSLATION', false);

// Hỗ trợ thêm từ công cụ tìm kiếm, ví dụ Google Search?
define('SEARCH_ENGINE_GEMINI', false);

// Xem có sử dụng thông tin prompt & systemInstruction mà người dùng up lên không
define('USE_USER_PROMPT_SYSTEM', false); // Mặc định để false, tức là dùng prompt & system mặc định của ứng dụng

// Có giữ lại toàn bộ thẻ HTML hay không
define('ALL_HTML_TAGS', false); // hiếm khi dùng, vì kiểu dịch này khó có chất lượng cao bằng markdown, ngoài ra tương đối tốn token

// Cấu hình cURL 
define('CURL_TIMEOUT', 90); // Timeout chung (giây)

// Cấu hình cURL Fetch HTML
define('CURL_TIMEOUT_FETCH', 120); // Timeout cho việc tải HTML (giây)

// Cấu hình cURL API
define('CURL_TIMEOUT_API', 1000); // Timeout cho việc gọi API cho việc dịch bài (giây)

// Cấu hình cho API chuyển đổi query
define('CURL_TIMEOUT_API_SEARCH', 60); // Timeout cho việc gọi API cho việc search (giây)

// Giới hạn số lượng token đầu vào
define('MAX_INPUT_TOKENS_ALLOWED', 64000); // Đặt giới hạn token đầu vào

// Hiển thị số lượng các bài dịch gần đây
define('RECENT_TRANSLATIONS_NUMBER', 10); // Tránh quá nhiều để tránh quá dài

// Xem có dùng fetchHtmlWithPanther để lấy nội dung hay không, mặc định tắt
define('FETCH_HTML_WITH_PANTHER', true); // Cách này mạnh hơn để lấy nội dung, nhưng yêu cầu người dùng cần phải cài sẵn trình duyệt Firefox hoặc Chrome

// Nếu muốn ưu tiên Panther là trình lấy nội dung html
define('ONLY_FETCH_HTML_WITH_PANTHER', false);

// Trình duyệt mà người dùng muốn dùng cùng với Panther
define('USER_PREFERRED_BROWSER_PANTHER', 'chrome'); // Các lựa chọn khả dụng là Firefox và Chrome

// Xác định xem dùng bộ lọc nội dung nào, mặc định dùng PHP để tăng tính tương thích với nhiều hệ điều hành và đơn giản hơn
// Khi để true, nó sẽ sử dụng bộ lọc gốc của Mozilla làm phương pháp dự phòng
define('USE_ADVANCED_READABILITY', false); // Có chất lượng cao, nhưng phải thực hiện qua file exe và có thể không tương thích với một số hệ điều hành

// Khi để true, nó sẽ sử dụng bộ lọc gốc của Mozilla làm phương pháp chính
define('ONLY_ADVANCED_READABILITY', true); // Có chất lượng cao

// Xem có sử dụng HTML 2 Markdown của pandoc.exe không
define('USE_HTML_TO_MARKDOWN_PANDOC', false); // Mặc định để false, sẽ sử dụng thư viện PHP để chuyển HTML sang Markdown, cũng rất tốt

// Xác định xem có dùng thêm bộ lọc HTMLPurifier hay không?
define('USE_HTML_PURIFIER', true); // Mặc định bật để phòng xa

// Xem có nên loại bỏ các tag inline không?
define('REMOVE_INLINE_HTML_TAGS', false); // true thì có loại bỏ, mặc định tắt

// Đường dẫn LƯU file dịch. Đảm bảo thư mục này TỒN TẠI và web server (vd: Apache, Nginx) CÓ QUYỀN GHI vào đó.
// Sử dụng đường dẫn tương đối từ gốc của script PHP là an toàn hơn.
define('TRANSLATION_SAVE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'translations'); // Lưu vào thư mục 'translations' ở thư mục cha của config.php 

// URL tương đối để truy cập thư mục lưu file từ trình duyệt (nếu thư mục này nằm trong web root)
define('TRANSLATION_PUBLIC_URL', 'translations'); // Ví dụ: http://yourdomain.com/translations/file.html

// Đường dẫn của mô hình, hiếm khi thay đổi
define('GENERATIVE_LANGUAGE', 'https://generativelanguage.googleapis.com/v1beta/models/');

// Tên model của AI, thường xuyên thay đổi theo lựa chọng của người dùng
define('IS_USING_MODEL_ID', 'gemini-2.5-flash');

// Một phần đường dẫn của mô hình, hiếm khi thay đổi
define('GENERATE_CONTENT_API', 'generateContent'); // có một loại khác là streamGenerateContent

// URL API Gemini
// Ví dụ https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro-exp-03-25:generateContent?key=HjIkQ26KKfhsogjhsdof4785fsgfs5
define('GEMINI_API_URL', GENERATIVE_LANGUAGE . IS_USING_MODEL_ID  . ':' . GENERATE_CONTENT_API . '?key=' . GEMINI_API_KEY);
// Lưu ý: Kiểm tra lại model name phù hợp nếu 'gemini-1.5-pro-latest' hoặc 'gemini-2.5-pro-exp-03-25' không hoạt động/khả dụng

// Tên model của AI dùng cho SEARCH query, nên chọn cái có tốc độ cao, không cần mô hình quá mạnh
define('IS_USING_MODEL_ID_SEARCH', 'gemini-2.5-flash-lite');

// Với GEMINI_API_URL_SEARCH, nên sử dụng mô hình đơn giản hơn để nhanh hơn
define('GEMINI_API_URL_SEARCH', GENERATIVE_LANGUAGE . IS_USING_MODEL_ID_SEARCH  . ':' . GENERATE_CONTENT_API . '?key=' . GEMINI_API_KEY);

// Sử dụng để dịch RSS, chung cấu hình với Search
define('RSS_GEMINI_MODEL_ENDPOINT', GENERATIVE_LANGUAGE . IS_USING_MODEL_ID_SEARCH);

// Có hiển thị sidebar bên trái hay không
define('LEFT_SIDEBAR', false);

// Có dịch đa ngữ hay không
define('MULTI_LINGUAL', false);

// Xem có cần xóa thẻ audio và video không?
// Nên để FALSE, vì prompt đã xử lý tốt vụ nghe audio và xem video ngoài ý định
// define('REMOVE_AUVI_HTML_TAGS', false);