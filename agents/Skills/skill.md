# Dashboard Skills

## Mục tiêu
File này là nơi tập trung mô tả tính năng của toàn bộ dashboard. Không tạo thêm `.md` riêng trong từng thư mục chức năng; khi cần cập nhật tài liệu module thì bổ sung vào file này.

## Tổng quan dự án
- Dự án PHP chạy trên XAMPP tại `C:\xampp\htdocs\dashboard`.
- `index.php` là shell chính, mở các module trong `iframe`.
- `HOME_PAGE/home_page.php` là màn hình mặc định, được load ngay khi vào dashboard và không còn nằm trong menu Tín dụng.
- Menu chính hiện dùng dạng ngang, gồm nhóm cha - con: Báo cáo, Văn bản, Hệ thống.
- DB dùng MySQL cho văn bản/hệ thống, MySQL local cho lưu trữ văn bản, Oracle cho báo cáo DGX và dư nợ.
- Thư viện chính: `phpoffice/phpspreadsheet` để xuất Excel.

## Giao diện chính
- File shell: `index.php`.
- CSS shell: `view/style_Das.php`.
- Layout hiện tại: thanh tiêu đề riêng ở trên, menu cha - con dạng dropdown nằm ở hàng dưới; không dùng nút thu gọn.
- Tông màu giao diện chính: palette mệnh Thủy gồm xanh đen `#061a2f`, navy `#0b3f6f`, xanh dương `#0f6ea8`, cyan `#0ea5b7`, xanh băng `#eaf6fb`; dùng chàm tím `#4f46a8` làm điểm nhấn nhẹ cho thần số học số 9.
- Badge văn bản chưa đọc lấy từ API `index.php?api=unread_count`.
- Home mặc định: `HOME_PAGE/home_page.php`; shell không hiển thị thanh tiêu đề phụ. Mỗi chức năng được cache bằng iframe riêng sau lần mở đầu để chuyển lại nhanh hơn và không reload dữ liệu.

## HOME_PAGE - Tổng hợp dư nợ
- File chính: `HOME_PAGE/home_page.php`.
- SQL helper: `HOME_PAGE/home_page_sql.php`.
- CSS: `view/Style_home_page.php`.
- Mục đích: tổng hợp dư nợ, nợ quá hạn, nợ khoanh, cho vay, thu nợ theo ngày báo cáo, POS/Xã và nguồn vốn.
- `MAPOS` mặc định: `3400`.
- Ngày báo cáo mặc định: hôm nay trừ 1 ngày.
- Bộ lọc nguồn vốn dùng tham số `source`: `ALL`, `TW`, `DP`.
- Nếu ngày báo cáo là ngày cuối tháng, SQL chọn bảng `HSKU`; các ngày khác chọn `HSCV_DAILY`.
- Nếu ngày báo cáo lớn hơn hoặc bằng ngày hiện tại thì trả thông báo chưa có số liệu, không chạy Oracle.
- Danh sách tổng hợp tách theo `POS` và `Xã`.
- Click từng dòng để mở modal chi tiết theo chương trình vay.
- Các chỉ tiêu tiền hiển thị theo đơn vị triệu đồng, làm tròn `round(x / 1000000, 2)`.
- Khi sửa module này cần kiểm tra lại thẻ thống kê, danh sách POS/Xã, biểu đồ donut và modal chi tiết.

## DGX - Báo cáo điểm giao dịch xã
- File chính: `DGX/dgx.php`.
- SQL helper: `DGX/dgx_sql.php`.
- Layout báo cáo: `DGX/dgx_report_layout.php`.
- CSS: `view/Style_dgx.php`.
- Mục đích: xem danh sách điểm giao dịch xã, điểm cố định, báo cáo theo ngày, báo cáo theo yêu cầu và xuất Excel.
- Báo cáo theo yêu cầu hỗ trợ khoảng ngày và danh sách POS; nếu bỏ trống POS thì lấy toàn bộ POS.
- Mã POS nhập ngắn hơn 6 ký tự được tự động thêm `0` bên trái, ví dụ `3401` -> `003401`.
- Báo cáo theo yêu cầu đã bổ sung cột `Mã Điểm GDX`, `Ngày giao dịch xã`, `Lãnh đạo tham gia GDX`.
- Cột `Lãnh đạo tham gia GDX` lấy từ `I_USER.IU_TEN` khi `I_USER.IU_NV` thuộc `POGD`, `POPGD`; không có thì để trống.
- Luồng báo cáo tăng timeout OCI riêng lên 120 giây, query theo từng ngày trong khoảng để giảm rủi ro timeout.
- `DGX/dgx.php` nhả khóa session sớm cho request báo cáo/xuất Excel để không làm chậm tab khác.
- Khi sửa DGX cần lint: `DGX/dgx.php`, `DGX/dgx_sql.php`, `DGX/dgx_report_layout.php`, `view/Style_dgx.php`.

## VB_IOT - Văn bản nội bộ
- File chính: `VB_IOT/vb_iot.php`.
- SQL helper: `VB_IOT/vb_iot_sql.php`.
- Xem file: `VB_IOT/view_file.php`.
- CSS: `view/Styple_vb_iot.php`.
- Mục đích: xem văn bản, người nhận, file đính kèm, đánh dấu đã đọc/chưa đọc và lưu trữ local theo phân loại.
- Dữ liệu nguồn: `eoffice_approval`, `eoffice_approval_file`, `eoffice_approval_receiver`, `user`.
- Dữ liệu local: `dashboard_my.classify`, `dashboard_my.save_eoffice`.
- Có API lưu trữ: `archive_types`, `archive_save`, `archive_list`, `archive_raw`.
- Trạng thái đã đọc lưu trong session `read_docs`, dashboard shell đọc để cập nhật badge.
- Module có `postMessage` `vb_iot:refresh_unread` để yêu cầu shell cập nhật số chưa đọc.
- Lưu BLOB theo chunk và dùng `GET_LOCK/RELEASE_LOCK` để tránh lưu trùng cùng lúc.
- Khi sửa tìm kiếm/lọc, kiểm tra lại phân trang, đánh dấu đã đọc toàn trang/toàn bộ và popup lưu trữ.

## USER-SYSTEM - Quản lý User
- File chính: `USER-SYSTEM/user_system.php`.
- SQL helper: `USER-SYSTEM/user_system_sql.php`.
- Layout thêm mới: `USER-SYSTEM/layout_insert.php`.
- CSS: `view/Style_user_system.php`.
- Mục đích: quản lý cán bộ/người dùng, xem danh sách, chi tiết, thêm mới, cập nhật.
- Đã bỏ trường `giaoviec` khỏi giao diện thêm mới và cập nhật; SQL chủ động lưu `giaoviec = NULL`.
- Danh sách có combobox lọc theo phòng ban, dùng `department_code` từ bảng `phongban`.
- Khi lọc theo phòng ban, phân trang, mở chi tiết và lưu bản ghi phải giữ nguyên bộ lọc.
- Form POST cần giữ `return_url` và `return_keyword` để quay lại đúng màn hình/bộ lọc.
- Toàn bộ file trong `USER-SYSTEM` phải lưu `UTF-8` không BOM.

## DB - Kết nối cơ sở dữ liệu
- File chính: `DB/connect_DB.php`.
- Mục đích: khởi tạo MySQL nguồn, MySQL local fallback và Oracle.
- MySQL nguồn/local đọc cấu hình từ biến môi trường, có fallback giá trị mặc định nội bộ.
- Oracle thử nhiều DSN: `SERVICE_NAME`, `SERVER=DEDICATED`, `SID`, `SERVER=SHARED`.
- Nếu gặp lỗi listener kiểu `ORA-12516`, `ORA-12520` thì retry ngắn trước khi bỏ.
- Oracle lỗi không `die`, chỉ log cảnh báo để module không phụ thuộc Oracle vẫn chạy.
- Host bảo vệ: `10.64.0.251`, `10.64.0.56`.
- Wrapper `db_mysqli_prepare`, `db_mysqli_query`, `db_oci_parse` chặn SQL ghi trên host bảo vệ nếu chưa được cho phép.
- Các biến chính sau khi load: `$conn`, `$mysqlActiveHost`, `$oracle_conn`, `$sourceHost`, `$localHost`, `$oracleHost`.

## FUNC_SHARE - Hàm dùng chung
- File chính: `FUNC_SHARE/ham_dung_chung.php`.
- Mục đích: helper session, chặn IP, chuẩn hóa ngày/text, escape HTML, JSON response, query MySQL/Oracle, cấu hình menu, loading overlay.
- Chặn IP bằng `dashboard_chan_ip_khong_hop_le()`, mặc định chỉ cho phép `10.64.0.108`.
- Khởi tạo session bằng `dashboard_khoi_tao_phien()`.
- Cấu hình menu bằng `dashboard_cau_hinh_menu()`; không đưa `HOME_PAGE` vào menu vì đã là màn hình mặc định.
- Biến môi trường hỗ trợ: `DASHBOARD_ALLOWED_IPS`, `DASHBOARD_CREATOR_NAME`.

## CODE-ORCALE_SQL
- File chính: `CODE-ORCALE_SQL/CODE.SQL`.
- File mẫu/phụ: `CODE-ORCALE_SQL/KT740.txt`.
- Mục đích: lưu SQL/PLSQL Oracle phục vụ đối chiếu và phân tích nghiệp vụ.
- Quy định encoding: lưu `CODE.SQL` bằng `UTF-8` không BOM, không copy qua công cụ làm mất dấu hoặc sinh mojibake.
- Khi sửa text tiếng Việt trong SQL, rà lại chuỗi trong dấu nháy đơn, comment và mã mẫu như `HĐ-TD`, `02A/HĐTD`.

## view - CSS module
- `view/style_Das.php`: CSS shell dashboard.
- `view/Styple_vb_iot.php`: CSS module văn bản.
- `view/Style_dgx.php`: CSS module DGX.
- `view/Style_home_page.php`: CSS module Home Page.
- `view/Style_user_system.php`: CSS module User System.
- CSS là endpoint PHP, luôn giữ `header('Content-Type: text/css; charset=UTF-8');` và UTF-8 không BOM.

## Kiểm tra nhanh sau khi sửa
- Shell/menu: `php -l index.php`, `php -l view/style_Das.php`, mở dashboard và kiểm tra Home tự load.
- Helper/menu: `php -l FUNC_SHARE/ham_dung_chung.php`.
- Home Page: lint `HOME_PAGE/home_page.php`, `HOME_PAGE/home_page_sql.php` và kiểm tra lọc ngày/nguồn vốn.
- DGX: lint `DGX/dgx.php`, `DGX/dgx_sql.php`, `DGX/dgx_report_layout.php` và kiểm tra xuất Excel.
- VB_IOT: lint `VB_IOT/vb_iot.php`, `VB_IOT/vb_iot_sql.php`, `VB_IOT/view_file.php` và kiểm tra badge chưa đọc.
- USER-SYSTEM: lint `USER-SYSTEM/user_system.php`, `USER-SYSTEM/user_system_sql.php`, `USER-SYSTEM/layout_insert.php` và kiểm tra lọc phòng ban.