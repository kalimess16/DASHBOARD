# Quy ước Dashboard

## Cấu Trúc Tính Năng

Khi tạo một tính năng mới, tách file theo đúng trách nhiệm:

1. `code`
   - File điều khiển/trang chính, ví dụ: `FEATURE/feature.php`
2. `sql`
   - Chỉ chứa các hàm SQL, ví dụ: `FEATURE/feature_sql.php`
3. `style`
   - File hoặc endpoint CSS, ví dụ: `view/Style_feature.php`

## Quy Ước Mã Hóa Text

- Tất cả file `.php`, `.md`, `.sql`, `.js`, `.css` phải lưu bằng `UTF-8` không BOM.
- Nội dung tiếng Việt phải viết trực tiếp bằng Unicode chuẩn, không lưu theo ANSI/Windows-1252 và không mã hóa chồng lần nữa.
- Với PHP/HTML, luôn giữ `header('Content-Type: text/html; charset=UTF-8');` và `<meta charset="UTF-8">` khi có giao diện.
- Chỉ dùng HTML entity khi thật sự cần trong thuộc tính hoặc ngữ cảnh đặc biệt; ưu tiên text tiếng Việt trực tiếp.
- Khi chỉnh sửa file cũ có tiếng Việt, editor phải dùng `Save with Encoding -> UTF-8`.
- Không copy nội dung qua công cụ làm mất dấu hoặc biến text thành chuỗi lỗi mã hóa (mojibake).

## Ví Dụ Đang Áp Dụng

- `DGX`
  - `DGX/dgx.php`
  - `DGX/dgx_sql.php`
  - `view/Style_dgx.php`
- `VB_IOT`
  - `VB_IOT/vb_iot.php`
  - `VB_IOT/vb_iot_sql.php`
  - `view/Styple_vb_iot.php`
- `USER-SYSTEM`
  - `USER-SYSTEM/user_system.php`
  - `USER-SYSTEM/user_system_sql.php`
  - `view/Style_user_system.php`

## Quy Ước Thông Báo USER-SYSTEM

- Với `USER-SYSTEM`, cả thêm mới ở `USER-SYSTEM/layout_insert.php` và cập nhật ở `USER-SYSTEM/user_system.php` đều phải có thông báo sau khi lưu.
- Luồng chuẩn là: xác nhận trên giao diện trước khi ghi, sau đó redirect về đúng màn hình đang thao tác và hiện flash thành công hoặc thất bại.
- Form POST cần gửi `return_url` để quay lại đúng màn hình và `return_keyword` để giữ bộ lọc khi cập nhật từ trang danh sách.

## Quy Ước Xuất Excel DGX

- `DGX/dgx.php`: phần `api=report_excel` phải có tiêu đề báo cáo ở đầu sheet.
- File xuất cần dùng font mặc định `Times New Roman`.
- Nếu có dòng thông tin phụ, chỉ hiển thị khi thực sự cần để tránh chừa khoảng trắng dư phía trên bảng dữ liệu.