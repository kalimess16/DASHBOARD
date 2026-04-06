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
- File liên quan:
- Mục tiêu:
- Yêu cầu chi tiết:
- Không được đụng vào:
- Kết quả mong muốn:
- Cách kiểm tra:
- Ghi chú thêm:

## CODE-ORCALE_SQL

### Task

- Trạng thái: 
- Ưu tiên:  
- File liên quan: CODE.SQL
- Mục tiêu: Tìm kiếm nguyên nhân và phân tích TẠI SAO LẠI THIẾU SỐ LIỆU
- Yêu cầu chi tiết: KHI TÔI XUẤT THÌ SỐ LIỆU LẠI THIẾU 1 ÍT SAO VỚI DỮ LIỆU TỔNG TÔI CÓ. vỀ PHẦN TỔNG DƯ NỢ
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

- Trạng thái: 
- Ưu tiên: 
- File liên quan: DGX.PHP, dgx_report_layout,dgx_sql.php, view/..
- Mục tiêu: xuất báo cáo không thể hiện tên GDV
- Yêu cầu chi tiết: 
    + đọc code gửi kèm
    + xử lý lỗi không thể hiện tên GDV
    + dựa vào code mới có tối ưu dc các code còn lại không? nếu có thì cập nhật là update giúp, không ảnh hưởng tới các chức năng khác
- Không được đụng vào: các thư mục khác
- Kết quả mong muốn: xử lý các vấn đề nêu trên
- Cách kiểm tra: vào DGX xem nội dung bên trong, nhấnn "Báo cáo theo yêu cầu" - nhập, báo cáo sẽ ra kết quả
- Ghi chú thêm: 
    + Kiểm tra xem nếu cần thì tạo, update các file md trong thư mục DGX
    + cần thì có thể xử lý css.
    + Nếu thư mục đã có thì cập nhật thêm vào file md có trong thư mục trước khi sửa tiếp chức năng này.

## HOME_PAGE

### Task

- Trạng thái: 
- Ưu tiên: 
- File liên quan: HOME_PAGE.PHP, HOME_PAGE_SQL.PHP, DB/CONNECT_DB.PHP, view/..
- Mục tiêu: bổ sung dữ liệu/ thay đổi dữu liệu/ css
- Yêu cầu chi tiết: 
    + số liệu thì round(x/ 1000000, 2) lại cho gọn số
- Không được đụng vào: các chức năng không liện quan
- Kết quả mong muốn: khắc phục toàn bộ các yêu cầu trên
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
- Ưu tiên:
- File liên quan:
- Mục tiêu:
- Yêu cầu chi tiết:
- Không được đụng vào:
- Kết quả mong muốn:
- Cách kiểm tra:
- Ghi chú thêm:

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
- Trạng thái: TODO
- Ưu tiên: CAO
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
- Trạng thái: TODO
- Ưu tiên: CAO
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

## Ví dụ nhanh

```md
## DGX

### Task
- Trạng thái: 
- Ưu tiên: Cao
- File liên quan: DGX/dgx.php, DGX/dgx_sql.php
- Mục tiêu: Thêm bộ lọc theo ngày cho màn hình DGX
- Yêu cầu chi tiết: Thêm 2 ô từ ngày/đến ngày, lọc dữ liệu khi bấm tìm kiếm, giữ lại giá trị sau khi submit
- Không được đụng vào: Xuất Excel hiện tại
- Kết quả mong muốn: Người dùng lọc được dữ liệu theo khoảng ngày ngay trên màn hình DGX
- Cách kiểm tra: Mở DGX, nhập khoảng ngày, bấm tìm kiếm, kiểm tra dữ liệu thay đổi đúng
- Ghi chú thêm: Nếu cần sửa CSS thì cập nhật ở view/Style_dgx.php
```
