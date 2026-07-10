# AI Request Board

File này là nơi nhập yêu cầu để Codex xử lý.

## Cách dùng

Khi muốn mình làm việc theo file này, bạn chỉ cần nhắn một câu như:

`Hãy làm theo AI_REQUESTS.md`

## Quy tắc Codex phải làm trước

1. Đọc các file `.md` ở thư mục gốc trước.
2. Nếu thư mục đang được yêu cầu có file `.md` riêng, đọc tiếp các file đó trước khi sửa code.
3. Bỏ qua `vendor/` nếu yêu cầu không liên quan đến thư viện.
4. Chỉ xử lý các mục có `Trạng thái: TODO`.
5. Ưu tiên mục có `Ưu tiên: Cao`.
6. Sau khi làm xong, cập nhật lại trạng thái trong phần trả lời cho người dùng.

## Cách bạn nhập yêu cầu

- Mỗi yêu cầu đặt vào đúng thư mục liên quan.
- Nếu có nhiều việc, bạn tạo nhiều khối trong cùng một thư mục.
- Nếu có thư mục mới, bạn tự thêm một mục mới theo đúng mẫu bên dưới.
- Nếu việc liên quan file gốc ở ngoài các thư mục con, dùng mục `ROOT`.

## Mẫu chung

```md
### Task
- Trạng thái: TODO
- Ưu tiên: Cao / Trung bình / Thấp
- File liên quan:
- Mục tiêu:
- Yêu cầu chi tiết:
- Không được đụng vào:
- Kết quả mong muốn:
- Cách kiểm tra:
- Ghi chú thêm:
```

---

## ROOT

### Task

- Trạng thái: 
- Ưu tiên: 
- File liên quan: index.php 
- Mục tiêu: khắc phục lỗi/ edit trang chính
- Yêu cầu chi tiết:
    + tôi muốn phần helder nhỏ lại
    + cở chữ cũng nhỏ lại đi 30%
    + vần body thì khi load chức năng thì không nên kéo quá dài. auto to khung màng hình
    + heder vs body tôi không muốn có khoảng trắng
- Không được đụng vào: c
- Kết quả mong muốn: xử lý các lỗi trên
- Cách kiểm tra: vào web là thấy
- Ghi chú thêm: + đọc qua các .md 
    + nếu cần thì bổ sung vào md
    + có thể chỉnh sửa lại sytle

## CODE-ORCALE_SQL

### Task

- Trạng thái: 
- Ưu tiên:  
- File liên quan: CODE.SQL
- Mục tiêu: Tìm kiếm nguyên nhân và phân tích TẠI SAO LẠI THIẾU SỐ LIỆU
- Yêu cầu chi tiết: KHI TÔI XUẤT THÌ SỐ LIỆU DƯ NỢ LẠI THIẾU 1 ÍT SAO VỚI DỮ LIỆU TỔNG TÔI CÓ. vỀ PHẦN TỔNG DƯ NỢ
- Không được đụng vào: TOÀN BỘ BỘ CÁC FILE KHÁC
- Kết quả mong muốn: có thể chạy dc - DONE và không ERROR
- Cách kiểm tra: KHÔNG
- Ghi chú thêm: CHỈ TẬP CHUNG VÀO FILE CODE.SQL

## DB

### Task

- Trạng thái:
- Ưu tiên:
- File liên quan: CONNECT_DB.PHP
- Mục tiêu: xử lý lỗi: Chua ket noi duoc Oracle. Kiem tra OCI8 va thong tin ket noi trong DB/connect_DB.php.
- Yêu cầu chi tiết: khắc phục lỗi Chua ket noi duoc Oracle. Kiem tra OCI8 va thong tin ket noi trong DB/connect_DB.php.
- Không được đụng vào: các file không liên quan
- Kết quả mong muốn: có thể kết nối được
- Cách kiểm tra:
- Ghi chú thêm:

## DGX

### Task

- Trạng thái: TODO
- Ưu tiên: CAO
- File liên quan: `DGX.PHP`, `dgx_report_layout.php`,`dgx_sql.php`, `view/..`, `DGX.MD`
- Mục tiêu: bổ sung dữ liệu trên cột bảng
- Yêu cầu chi tiết: 
    - `BÁO CÁO THEO YÊU CẦU ` => TÔI MUỐN BỔ SUNG THÊM CỘT `LÃNH ĐẠO THAM GIA GDX`
    - TRONG SQL THÌ ĐỐI VỚI BẢNG I_USER NẾU iu_nv IN ('POGD','POPGD') THÌ HIỂN THỊ TÊN LÃNH ĐẠO (IU_TEN) TRONG `LÃNH ĐẠO THAM GIA GDX`, NGƯỢC LẠI NẾU KHÔNG CÓ THÌ ĐỂ TRỐNG
- Không được đụng vào: các thư mục khác
- Kết quả mong muốn: xử lý các vấn đề nêu trên và áp dụng có các báo có liên quan.
- Cách kiểm tra: vào DGX xem nội dung bên trong, nhấn `báo cáo THEO YÊU CẦU` = > xem kết quả.
- Ghi chú thêm: 
    + Kiểm tra xem nếu cần thì tạo, update các file md trong thư mục DGX
    + cần thì có thể xử lý css.
    + Nếu thư mục đã có thì cập nhật thêm vào file md có trong thư mục trước khi sửa tiếp chức năng này.

## HOME_PAGE

### Task

- Trạng thái: 
- Ưu tiên: 
- File liên quan: `HOME_PAGE.PHP`, `HOME_PAGE_SQL.PHP`, `DB/CONNECT_DB.PHP, view/..`
- Mục tiêu: bổ sung dữ liệu/ thay đổi dữu liệu/ css
- Yêu cầu chi tiết: 
    - Điều kiện lọc `Ngày báo cáo` tôi muốn thay đổi nếu `Ngày báo cáo` là ngày cuối tháng thì câu lệnh sql trong `HOME_PAGE_SQL.PHP` có table là `hscv_daily` sẽ đổi thành `hsku`
    - nếu `Ngày báo cáo` lớn hơn hoặc bằng ngày hiện tại thì báo chưa có số liệu.
- Không được đụng vào: các chức năng không liện quan
- Kết quả mong muốn: chọn `Ngày báo cáo` => `Tải báo cáo` => số liệu cuối thàng
- Cách kiểm tra: vào chức năng home page sẽ thấy được đúng số liệu
- Ghi chú thêm: 
    + Kiểm tra xem nếu cần thì tạo, update các file md liên quan. 
    + cần thì có thể xử lý css.
    + đọc thêm tài liệu riêng tại `HOME_PAGE/home_page.md` trước khi sửa tiếp chức năng này.

## USER-SYSTEM

### Task

- Trạng thái:
- Ưu tiên:  
- File liên quan: user_system_sql, user_system.php, layout_insert.php, user_system.md
- Mục tiêu: bổ sung tính năng lọc theo phòng ban
- Yêu cầu chi tiết: lựa chọn phòng ban từ hệ thống để lọc danh sách, phòng ban dưới dạng combox
- Không được đụng vào: các thư mục khác
- Kết quả mong muốn: click phòng bàn, nhắn lọc danh sách
- Cách kiểm tra: click phòng bàn, nhắn lọc danh sách
- Ghi chú thêm: cập nhật vào md của thư mục

## VB_IOT

### Task

- Trạng thái: 
- Ưu tiên: CAO
- File liên quan: `vb_iot_sql.php`, `vb_iot_tmp_backup.php`, `vb_iot.php`, `view_file.php`, `view/style_vb_iot.php`
- Mục tiêu: bổ sung tính năng tìm kiếm
- Yêu cầu chi tiết:
    - kiếm tra lại tính năng `tìm kiếm theo mã số văn bản hoặc người nhận` hiện tính năng này tôi muốn chỉ lọc theo người nhận thôi, không lấy theo mã số văn bản nữa
- Không được đụng vào: các file không liên quan
- Kết quả mong muốn: tìm kiếm được kết quả mong muốn
- Cách kiểm tra: vào nhập người nhận => search => ra kết quả cần
- Ghi chú thêm:
    + Kiểm tra xem nếu cần thì tạo, update các file md liên quan. 
    + cần thì có thể xử lý css, có thể sửa css cho đẹp hơn.

## view

### Task

- Trạng thái:
- Ưu tiên:
- File liên quan:
- Mục tiêu:
- Yêu cầu chi tiết:
- Không được đụng vào:
- Kết quả mong muốn:
- Cách kiểm tra:
- Ghi chú thêm:

## FUNC_SHARE

### Task
- Trạng thái: 
- Ưu tiên: 
- File liên quan: Toàn bộ dự án có trong project
- Mục tiêu: bổ sung tính năng/ gom dữ liệu / thiết kế lại menu
- Yêu cầu chi tiết:
    + Đọc toàn bộ code 
    + tạo cho tôi 1 file php. mang tên ham_dung_chung.php Hàm này thì gom hết các chức năng có thể dùng dung cho toàn dự án này
    + Thiết kế lại menu theo dạng Ngang và mở các menu con. 
        - "Báo cáo" -> "Điểm GDX"
        - "Tín dụng" -> "Tổng hợp dư nợ", ...
        - "Văn bản" -> "Văn Bản"
        - "Hệ Thống" -> "Quản lý User"
    + tại trang chủ tôi muốn chia thành 3 phần: phần đầu trang (thể hiện menu), thân trang(chứa dữ liệu các chức năng), cuối trang(thông tin người tạo trang web)
    + bảo mật cho trang web này chỉ mình ip: 10.64.0.108 connect, còn lại chặn hết
    + đưa hết function thành tiếng Việt Hóa hết để dễ quản lý
- Không được đụng vào: không có 
- Kết quả mong muốn: Hoàn thành các yêu cầu trên
- Cách kiểm tra: vào web là thấy
- Ghi chú thêm: + cần sửa thì edit thêm CSS
                + bổ sung thêm .md để sau này dễ quản ký

## Mẫu thêm thư mục mới

Sao chép khối này khi bạn tạo thư mục mới:

```md
## FUNC_SHARE

### Task
- Trạng thái: 
- Ưu tiên: 
- File liên quan: Toàn bộ dự án có trong project
- Mục tiêu: bổ sung tính năng/ gom dữ liệu / thiết kế lại menu
- Yêu cầu chi tiết:
    + Đọc toàn bộ code 
    + tạo cho tôi 1 file php. mang tên ham_dung_chung.php Hàm này thì gom hết các chức năng có thể dùng dung cho toàn dự án này
    + Thiết kế lại menu theo dạng Ngang và mở các menu con. 
        - "Báo cáo" -> "Điểm GDX"
        - "Tín dụng" -> "Tổng hợp dư nợ", ...
        - "Văn bản" -> "Văn Bản"
        - "Hệ Thống" -> "Quản lý User"
    + tại trang chủ tôi muốn chia thành 3 phần: phần đầu trang (thể hiện menu), thân trang(chứa dữ liệu các chức năng), cuối trang(thông tin người tạo trang web)
    + bảo mật cho trang web này chỉ mình ip: 10.64.0.108 connect, còn lại chặn hết
    + đưa hết function thành tiếng Việt Hóa hết để dễ quản lý
- Không được đụng vào: không có 
- Kết quả mong muốn: Hoàn thành các yêu cầu trên
- Cách kiểm tra: vào web là thấy
- Ghi chú thêm: + cần sửa thì edit thêm CSS
                + bổ sung thêm .md để sau này dễ quản ký
            
```

