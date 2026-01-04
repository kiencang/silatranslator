document.addEventListener('DOMContentLoaded', function () {

    // --- Helper Functions ---
    function getCssVariableValue(variableName) {
        const value = getComputedStyle(document.documentElement).getPropertyValue(variableName).trim();
        const numericValue = parseFloat(value);
        return numericValue == value ? numericValue : value;
    }

    function setCssVariable(variableName, value) {
        document.documentElement.style.setProperty(variableName, value);
    }

    function throttle(func, limit) {
        let inThrottle;
        return function () {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    }

    // --- Constants and Element References ---
    const root = document.documentElement;
    const articleElement = document.querySelector('article');
    const articleBody = document.querySelector('article[itemprop="articleBody"]');

    // Controls
    const decreaseFontBtn = document.getElementById('decrease-font');
    const increaseFontBtn = document.getElementById('increase-font');
    const decreaseWidthBtn = document.getElementById('decrease-width');
    const increaseWidthBtn = document.getElementById('increase-width');
    const decreaseLineHeightBtn = document.getElementById('decrease-line-height');
    const increaseLineHeightBtn = document.getElementById('increase-line-height');
    const fontSelect = document.getElementById('font-select');
    const resetBtn = document.getElementById('reset-settings');
    const metadataSection = document.getElementById('metadata-section-sila-trans');
    const toggleMetadataBtn = document.getElementById('toggle-metadata');

    // Theme Buttons (CẬP NHẬT)
    const toggleLightModeBtn = document.getElementById('toggle-light-mode');
    const toggleDarkModeBtn = document.getElementById('toggle-dark-mode');
    const toggleSepiaModeBtn = document.getElementById('toggle-sepia-mode');
    const toggleHighContrastModeBtn = document.getElementById('toggle-high-contrast-mode'); // MỚI

    // Progress Bar
    const progressBar = document.getElementById('progress-bar');

    // Reading Time (Nếu có)
    const readingTimeValueSpan = document.getElementById('reading-time-value');
    const WORDS_PER_MINUTE = 220;

    // --- Local Storage Keys ---
    const fontSizeKey = 'userFontSize';
    const contentWidthKey = 'userContentWidth';
    const fontFamilyKey = 'userFontFamily';
    const lineHeightKey = 'userLineHeight';
    const themeKey = 'userReadingTheme'; // Key này giờ quản lý 4 trạng thái
    const metadataHiddenKey = 'userMetadataHidden';

    // --- Default Values & Limits ---
    const defaultSettings = {
        fontSize: getCssVariableValue('--base-font-size'),
        maxWidth: getCssVariableValue('--content-max-width'),
        fontFamily: getCssVariableValue('--base-font-family').replace(/['"]+/g, ''),
        lineHeight: getCssVariableValue('--base-line-height').toString(),
        theme: 'light', // Mặc định là light
        metadataHidden: metadataSection?.classList.contains('hidden') || false
    };

    const limits = {
        fontStep: 1, // px
        widthStep: 40, // px
        lineHeightStep: 0.1,
        minFontSize: getCssVariableValue('--min-font-size'), // px
        maxFontSize: getCssVariableValue('--max-font-size'), // px
        minWidth: getCssVariableValue('--min-width'), // px
        maxWidth: getCssVariableValue('--max-width'), // px
        minLineHeight: getCssVariableValue('--min-line-height'),
        maxLineHeight: getCssVariableValue('--max-line-height')
    };

    // --- Theme Management Functions (CẬP NHẬT) ---

    /**
     * Cập nhật trạng thái trực quan của các nút theme
     * @param {string} activeTheme - Tên theme đang active ('light', 'dark', 'sepia', 'high-contrast')
     */
    function updateThemeButtonStates(activeTheme) {
        const buttons = [
            {btn: toggleLightModeBtn, theme: 'light'},
            {btn: toggleDarkModeBtn, theme: 'dark'},
            {btn: toggleSepiaModeBtn, theme: 'sepia'},
            {btn: toggleHighContrastModeBtn, theme: 'high-contrast'} // Thêm HCM
        ];
        buttons.forEach(item => {
            if (item.btn) {
                const isPressed = item.theme === activeTheme;
                item.btn.setAttribute('aria-pressed', isPressed.toString());
            }
        });
    }

    /**
     * Áp dụng theme được chọn vào trang
     * @param {string} theme - Tên theme ('light', 'dark', 'sepia', 'high-contrast')
     */
    function applyTheme(theme) {
        // Đảm bảo theme hợp lệ
        const validThemes = ['light', 'dark', 'sepia', 'high-contrast']; // Thêm HCM
        if (!validThemes.includes(theme)) {
            console.warn(`Invalid theme "${theme}" requested. Defaulting to light.`);
            theme = 'light';
        }

        // Xóa hết các class theme cũ
        root.classList.remove('dark-mode', 'sepia-mode', 'high-contrast-mode');

        // Thêm class theme đúng
        if (theme === 'dark') {
            root.classList.add('dark-mode');
        } else if (theme === 'sepia') {
            root.classList.add('sepia-mode');
        } else if (theme === 'high-contrast') { // Thêm điều kiện HCM
            root.classList.add('high-contrast-mode');
        }
        // Không cần thêm class cho 'light'

        updateThemeButtonStates(theme);
        localStorage.setItem(themeKey, theme);
    }

    // --- Load Saved Preferences ---
    function loadPreferences() {
        // Load các setting khác
        setCssVariable('--base-font-size', localStorage.getItem(fontSizeKey) || defaultSettings.fontSize);
        setCssVariable('--content-max-width', localStorage.getItem(contentWidthKey) || defaultSettings.maxWidth);
        const savedFont = localStorage.getItem(fontFamilyKey) || defaultSettings.fontFamily;
        setCssVariable('--base-font-family', savedFont);
        if (fontSelect)
            fontSelect.value = savedFont;
        setCssVariable('--base-line-height', localStorage.getItem(lineHeightKey) || defaultSettings.lineHeight);

        // Load Theme
        const savedTheme = localStorage.getItem(themeKey) || defaultSettings.theme;
        applyTheme(savedTheme); // Áp dụng theme (bao gồm cả HCM nếu đã lưu)

        // Load Metadata Visibility
        const savedMetadataHidden = localStorage.getItem(metadataHiddenKey);
        if (metadataSection && toggleMetadataBtn) {
            if (savedMetadataHidden === 'true') {
                metadataSection.classList.add('hidden');
                toggleMetadataBtn.textContent = 'Hiện thông tin';
            } else {
                metadataSection.classList.remove('hidden');
                toggleMetadataBtn.textContent = 'Ẩn thông tin';
            }
        }
    }

    // --- Event Listeners for Controls ---

    // Font Size, Width, Line Height, Font Family (Giữ nguyên)
    if (increaseFontBtn)
        increaseFontBtn.addEventListener('click', () => {
            let currentSize = parseFloat(getCssVariableValue('--base-font-size'));
            let newSize = Math.min(currentSize + limits.fontStep, parseFloat(limits.maxFontSize));
            const newSizeValue = newSize + 'px';
            setCssVariable('--base-font-size', newSizeValue);
            localStorage.setItem(fontSizeKey, newSizeValue);
        });
    if (decreaseFontBtn)
        decreaseFontBtn.addEventListener('click', () => {
            let currentSize = parseFloat(getCssVariableValue('--base-font-size'));
            let newSize = Math.max(currentSize - limits.fontStep, parseFloat(limits.minFontSize));
            const newSizeValue = newSize + 'px';
            setCssVariable('--base-font-size', newSizeValue);
            localStorage.setItem(fontSizeKey, newSizeValue);
        });
    if (increaseWidthBtn)
        increaseWidthBtn.addEventListener('click', () => {
            let currentWidth = parseFloat(getCssVariableValue('--content-max-width'));
            let newWidth = Math.min(currentWidth + limits.widthStep, parseFloat(limits.maxWidth));
            const newWidthValue = newWidth + 'px';
            setCssVariable('--content-max-width', newWidthValue);
            localStorage.setItem(contentWidthKey, newWidthValue);
        });
    if (decreaseWidthBtn)
        decreaseWidthBtn.addEventListener('click', () => {
            let currentWidth = parseFloat(getCssVariableValue('--content-max-width'));
            let newWidth = Math.max(currentWidth - limits.widthStep, parseFloat(limits.minWidth));
            const newWidthValue = newWidth + 'px';
            setCssVariable('--content-max-width', newWidthValue);
            localStorage.setItem(contentWidthKey, newWidthValue);
        });
    if (increaseLineHeightBtn)
        increaseLineHeightBtn.addEventListener('click', () => {
            let currentHeight = parseFloat(getCssVariableValue('--base-line-height'));
            let newHeight = Math.min(currentHeight + limits.lineHeightStep, limits.maxLineHeight);
            const newHeightValue = newHeight.toFixed(2);
            setCssVariable('--base-line-height', newHeightValue);
            localStorage.setItem(lineHeightKey, newHeightValue);
        });
    if (decreaseLineHeightBtn)
        decreaseLineHeightBtn.addEventListener('click', () => {
            let currentHeight = parseFloat(getCssVariableValue('--base-line-height'));
            let newHeight = Math.max(currentHeight - limits.lineHeightStep, limits.minLineHeight);
            const newHeightValue = newHeight.toFixed(2);
            setCssVariable('--base-line-height', newHeightValue);
            localStorage.setItem(lineHeightKey, newHeightValue);
        });
    if (fontSelect) {
        fontSelect.addEventListener('change', (event) => {
            const selectedFont = event.target.value;
            setCssVariable('--base-font-family', selectedFont);
            localStorage.setItem(fontFamilyKey, selectedFont);
        });
    }

    // --- Theme Button Event Listeners (CẬP NHẬT) ---
    if (toggleLightModeBtn) {
        toggleLightModeBtn.addEventListener('click', () => applyTheme('light'));
    }
    if (toggleDarkModeBtn) {
        toggleDarkModeBtn.addEventListener('click', () => applyTheme('dark'));
    }
    if (toggleSepiaModeBtn) {
        toggleSepiaModeBtn.addEventListener('click', () => applyTheme('sepia'));
    }
    if (toggleHighContrastModeBtn) { // Thêm listener cho HCM
        toggleHighContrastModeBtn.addEventListener('click', () => applyTheme('high-contrast'));
    }

    // Metadata Toggle (Giữ nguyên)
    if (toggleMetadataBtn && metadataSection) {
        toggleMetadataBtn.addEventListener('click', () => {
            const isHidden = metadataSection.classList.toggle('hidden');
            localStorage.setItem(metadataHiddenKey, isHidden);
            toggleMetadataBtn.textContent = isHidden ? 'Hiện thông tin' : 'Ẩn thông tin';
        });
    }

    // Reset Settings (CẬP NHẬT)
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            // Reset các setting khác
            setCssVariable('--base-font-size', defaultSettings.fontSize);
            setCssVariable('--content-max-width', defaultSettings.maxWidth);
            setCssVariable('--base-font-family', defaultSettings.fontFamily);
            setCssVariable('--base-line-height', defaultSettings.lineHeight);
            if (fontSelect)
                fontSelect.value = defaultSettings.fontFamily;

            // Reset Theme về mặc định (light)
            applyTheme(defaultSettings.theme);

            // Reset Metadata
            if (metadataSection)
                metadataSection.classList.remove('hidden');
            if (toggleMetadataBtn)
                toggleMetadataBtn.textContent = 'Ẩn thông tin';

            // Remove all saved settings
            localStorage.removeItem(fontSizeKey);
            localStorage.removeItem(contentWidthKey);
            localStorage.removeItem(fontFamilyKey);
            localStorage.removeItem(lineHeightKey);
            localStorage.removeItem(themeKey); // Key này giờ quản lý cả 4 theme
            localStorage.removeItem(metadataHiddenKey);
        });
    }

    // --- Reading Progress Bar (Giữ nguyên) ---
    function updateProgressBar() {
        if (!progressBar)
            return;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const docHeight = document.documentElement.scrollHeight;
        const clientHeight = document.documentElement.clientHeight;
        const scrollableHeight = docHeight - clientHeight;
        if (scrollableHeight <= 0) {
            progressBar.style.width = '100%';
            return;
        }
        const scrollPercent = (scrollTop / scrollableHeight) * 100;
        progressBar.style.width = Math.min(scrollPercent, 100) + '%';
    }
    window.addEventListener('scroll', throttle(updateProgressBar, 100));

    // --- Estimated Reading Time (Nếu có - Giữ nguyên) ---
    function calculateReadingTime() {
        if (!articleBody || !readingTimeValueSpan)
            return;
        const text = articleBody.innerText || articleBody.textContent || "";
        const wordMatch = text.match(/\b\w+\b/g);
        const wordCount = wordMatch ? wordMatch.length : 0;
        if (wordCount === 0) {
            readingTimeValueSpan.textContent = "0 phút";
            return;
        }
        const minutes = Math.ceil(wordCount / WORDS_PER_MINUTE);
        readingTimeValueSpan.textContent = `${minutes} phút`;
    }

    // --- Table of Contents (Giữ nguyên) ---
    function initializeTableOfContents() {
        if (!articleElement)
            return;
        const headings = articleElement.querySelectorAll('h2, h3');
        const tocThreshold = 3;
        if (headings.length < tocThreshold) {
            console.log("Số lượng headings không đủ để tạo TOC.");
            return;
        }

        const tocContainer = document.createElement('div');
        tocContainer.id = 'toc-container';
        tocContainer.setAttribute('aria-label', 'Mục lục bài viết');

        const tocToggleButton = document.createElement('button');
        tocToggleButton.id = 'toc-toggle-button';
        tocToggleButton.textContent = '+';
        tocToggleButton.title = 'Mở/Đóng Mục lục';
        tocToggleButton.setAttribute('aria-expanded', 'false');
        tocToggleButton.setAttribute('aria-controls', 'toc-list');

        const tocTitle = document.createElement('div');
        tocTitle.id = 'toc-title';
        tocTitle.textContent = 'Mục lục';
        tocTitle.setAttribute('aria-hidden', 'true');

        const tocList = document.createElement('ul');
        tocList.id = 'toc-list';
        tocList.setAttribute('role', 'navigation');

        let headingCounter = 0;
        headings.forEach(heading => {
            headingCounter++;
            const level = heading.tagName.toLowerCase();
            const text = heading.textContent.trim();
            if (!text)
                return;
            let id = heading.id;
            if (!id) {
                id = `toc-heading-${headingCounter}-${text.toLowerCase().replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-')}`;
                id = id.substring(0, 50);
                heading.id = id;
            }

            const listItem = document.createElement('li');
            listItem.classList.add(`toc-level-${level.charAt(1)}`);

            const link = document.createElement('a');
            link.href = `#${id}`;
            link.textContent = text;
            link.title = `Đi đến: ${text}`;

            link.addEventListener('click', function (event) {
                event.preventDefault();
                const targetElement = document.getElementById(id);
                if (targetElement) {
                    targetElement.scrollIntoView({behavior: 'smooth', block: 'start'});
                }
            });

            listItem.appendChild(link);
            tocList.appendChild(listItem);
        });

        if (tocList.children.length > 0) {
            tocContainer.appendChild(tocToggleButton);
            tocContainer.appendChild(tocTitle);
            tocContainer.appendChild(tocList);
            document.body.appendChild(tocContainer);

            tocToggleButton.addEventListener('click', () => {
                const isExpanded = tocContainer.classList.toggle('toc-expanded');
                tocToggleButton.setAttribute('aria-expanded', isExpanded);
                tocToggleButton.textContent = isExpanded ? '−' : '+';
                tocTitle.setAttribute('aria-hidden', !isExpanded);
            });
        } else {
            console.log("Không có heading hợp lệ nào được tìm thấy để tạo TOC.");
        }
    }

    // --- Initialization ---
    loadPreferences(); // Load settings (bao gồm cả theme)
    if (readingTimeValueSpan) {
        calculateReadingTime();
    }
    initializeTableOfContents();
    updateProgressBar(); // Initial progress bar state

});

/**
 * Hàm thiết lập chức năng bật/tắt hiển thị cho một cặp nút và nội dung.
 * @param {string} buttonId - ID của phần tử nút nhấn.
 * @param {string} contentId - ID của phần tử div chứa nội dung.
 * @param {string} textWhenHidden - Văn bản hiển thị trên nút khi nội dung đang ẩn.
 * @param {string} textWhenVisible - Văn bản hiển thị trên nút khi nội dung đang hiển thị.
 */
function setupToggleVisibilityPromptSystem(buttonId, contentId, textWhenHidden, textWhenVisible) {
    // 1. Lấy tham chiếu đến nút nhấn và div nội dung dựa trên ID được truyền vào
    const toggleBtn = document.getElementById(buttonId);
    const contentDiv = document.getElementById(contentId);

    // 2. Kiểm tra xem các phần tử có thực sự tồn tại không trước khi thêm listener
    if (!toggleBtn || !contentDiv) {
        console.error(`Không tìm thấy phần tử với ID: "${buttonId}" hoặc "${contentId}". Vui lòng kiểm tra lại HTML.`);
        return; // Dừng thực thi hàm nếu không tìm thấy phần tử
    }

    // 3. Thêm bộ lắng nghe sự kiện 'click' cho nút
    toggleBtn.addEventListener('click', function () {
        // 4. Kiểm tra xem div hiện tại có lớp 'visible' không
        const isVisible = contentDiv.classList.contains('visible');

        // 5. Thực hiện hành động ngược lại
        if (isVisible) {
            // Nếu đang hiển thị -> ẩn đi
            contentDiv.classList.remove('visible'); // Xóa lớp visible
            toggleBtn.textContent = textWhenHidden;  // Sử dụng văn bản được truyền vào
        } else {
            // Nếu đang ẩn -> hiển thị
            contentDiv.classList.add('visible');    // Thêm lớp visible
            toggleBtn.textContent = textWhenVisible; // Sử dụng văn bản được truyền vào
        }
    });
}

// --- Đảm bảo mã chỉ chạy sau khi DOM đã tải hoàn toàn ---
document.addEventListener('DOMContentLoaded', function () {
    // Gọi hàm setupToggleVisibility với các ID và văn bản cụ thể của bạn
    setupToggleVisibilityPromptSystem(
            'toggleButtonPromptSystem', // ID nút
            'prompt_system_sila_trans', // ID nội dung
            'Xem System Instructions / Prompt', // Text khi ẩn
            'Ẩn System Instructions / Prompt'   // Text khi hiện
            );

    // Bạn có thể gọi lại hàm này cho các cặp nút/nội dung khác nếu cần
    // setupToggleVisibility('anotherButtonId', 'anotherContentId', 'Show More', 'Hide Less');
});