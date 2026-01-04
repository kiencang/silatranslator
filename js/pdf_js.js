document.addEventListener('DOMContentLoaded', function () {
    const pdfForm = document.getElementById('pdf-form');
    const pdfFileInput = document.getElementById('pdfFile');
    const fileNameDisplay = document.getElementById('file-name-display');
    const submitButton = document.getElementById('submit-button');
    const buttonText = submitButton.querySelector('.button-text');
    const buttonLoader = submitButton.querySelector('.button-loader');

    if (pdfFileInput && fileNameDisplay) {
        // Hiển thị tên file khi người dùng chọn file
        pdfFileInput.addEventListener('change', function (e) {
            if (pdfFileInput.files.length > 0) {
                fileNameDisplay.textContent = pdfFileInput.files[0].name;
            } else {
                fileNameDisplay.textContent = 'Chưa chọn file nào';
            }
        });

        // Kích hoạt input file khi click vào vùng bao quanh (wrapper)
        const fileUploadWrapper = document.querySelector('.file-upload-wrapper');
        if (fileUploadWrapper) {
            fileUploadWrapper.addEventListener('click', function () {
                pdfFileInput.click();
            });
            // Ngăn sự kiện click lan vào input ẩn khi click label
            const fileLabel = fileUploadWrapper.querySelector('label');
            if (fileLabel) {
                fileLabel.addEventListener('click', function (e) {
                    e.stopPropagation(); // Ngăn không kích hoạt click của wrapper lần nữa
                });
            }
        }
    }

    if (pdfForm && submitButton && buttonText && buttonLoader) {
        // Hiển thị trạng thái loading khi submit form
        pdfForm.addEventListener('submit', function (e) {
            // Kiểm tra xem file đã được chọn chưa (dù đã có required HTML5)
            if (!pdfFileInput || pdfFileInput.files.length === 0) {
                alert('Vui lòng chọn một file PDF.');
                e.preventDefault(); // Ngăn form submit
                return;
            }

            // Hiển thị loader và ẩn text, vô hiệu hóa nút
            buttonText.style.display = 'none';
            buttonLoader.style.display = 'inline-block';
            submitButton.disabled = true;

            // Form sẽ tiếp tục submit theo cách thông thường (không dùng AJAX)
            // Trang sẽ tải lại và hiển thị kết quả từ process_pdf.php
        });
    }
});