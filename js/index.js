
// Khi người dùng click vào input thì xóa input cũ đi. Đảm bảo DOM đã tải xong trước khi chạy mã
document.addEventListener('DOMContentLoaded', function () {
    // Lấy phần tử input bằng ID của nó
    const urlInput = document.getElementById('url-input');
    const statusArea = document.getElementById('status-area'); // Lấy thêm statusArea

    // Kiểm tra xem phần tử có tồn tại không (đề phòng lỗi)
    if (urlInput) {
        // Thêm một trình lắng nghe sự kiện 'focus'
        urlInput.addEventListener('focus', function () {
            // Khi input nhận focus, đặt giá trị của nó thành rỗng
            this.value = ''; // hoặc urlInput.value = '';
            // 2. **Ẩn status area (Logic mới cần thêm)**
            if (statusArea) { // Luôn kiểm tra statusArea tồn tại trước khi thao tác
                if (statusArea.style.display !== 'none' && statusArea.querySelector('.error')) {
                    statusArea.style.display = 'none';
                }
            }
        });
    }

    const toggleButton = document.getElementById('toggle-news-button');
    const newsContainer = document.getElementById('google-news-container');

    // Kiểm tra xem nút và container có tồn tại không
    if (toggleButton && newsContainer) {
        toggleButton.addEventListener('click', function () {
            // Kiểm tra trạng thái hiện tại của container
            if (newsContainer.style.display === 'none' || newsContainer.style.display === '') {
                // Nếu đang ẩn, thì hiện ra
                newsContainer.style.display = 'block';
                // Đổi nội dung nút/link thành "Ẩn tin tức" và mũi tên lên
                toggleButton.innerHTML = 'Ẩn tin từ RSS ▲';
            } else {
                // Nếu đang hiện, thì ẩn đi
                newsContainer.style.display = 'none';
                // Đổi nội dung nút/link trở lại ban đầu
                toggleButton.innerHTML = 'Xem 10 tin mới nhất từ RSS ▼';
            }
        });
    }

    // --- BẮT ĐẦU CODE MỚI CHO TOGGLE RECENT TRANSLATIONS ---
    const toggleBtn = document.getElementById('toggle-recent-btn');
    const recentListDiv = document.getElementById('recent-translations-list');

    // Chỉ thêm listener nếu cả nút và div đều tồn tại
    if (toggleBtn && recentListDiv) {
        toggleBtn.addEventListener('click', function () {
            // Kiểm tra trạng thái hiển thị hiện tại của div
            const isHidden = recentListDiv.style.display === 'none' || recentListDiv.offsetParent === null; // Kiểm tra cả display:none và trường hợp ẩn do CSS khác

            if (isHidden) {
                // Nếu đang ẩn -> Hiện ra
                recentListDiv.style.display = 'block'; // Hoặc 'flex', 'grid' tùy vào cách bạn muốn hiển thị
                toggleBtn.textContent = 'Ẩn các bài đã dịch';
            } else {
                // Nếu đang hiện -> Ẩn đi
                recentListDiv.style.display = 'none';
                toggleBtn.textContent = 'Hiện các bài đã dịch';
            }
        });
    }

    const statsButton = document.getElementById('stats-button-translations');
    const statsResultDiv = document.getElementById('stats-result-translations');

    if (statsButton && statsResultDiv) {
        statsButton.addEventListener('click', function () {
            statsResultDiv.innerHTML = 'Đang thống kê...'; // Thông báo đang xử lý

            // Gọi file PHP xử lý AJAX
            fetch('functions/get_stats_translation_folder.php') // Đảm bảo đường dẫn này đúng
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json(); // Chuyển đổi response thành JSON
                    })
                    .then(data => {
                        // Kiểm tra dữ liệu trả về hợp lệ
                        if (data && typeof data.count !== 'undefined' && typeof data.size_mb !== 'undefined') {
                            const count = data.count;
                            // Định dạng số MB thành 2 chữ số thập phân
                            const sizeMb = parseFloat(data.size_mb).toFixed(2);
                            // Hiển thị kết quả
                            statsResultDiv.innerHTML = `Số lượng bài đã dịch: <strong>${count}</strong> | Tổng dung lượng: <strong>${sizeMb}</strong> MB`;
                        } else {
                            // Xử lý trường hợp dữ liệu không mong đợi
                            statsResultDiv.innerHTML = 'Lỗi: Dữ liệu trả về không hợp lệ.';
                            console.error('Invalid data received:', data);
                        }
                    })
                    .catch(error => {
                        // Xử lý lỗi nếu fetch thất bại
                        console.error('Lỗi khi lấy thống kê:', error);
                        statsResultDiv.innerHTML = 'Đã xảy ra lỗi khi thống kê. Vui lòng thử lại.';
                    });
        });
    } else {
        console.error('Không tìm thấy nút #stats-button-translations hoặc div #stats-result-translations');
    }
    // --- KẾT THÚC CODE MỚI ---        
});