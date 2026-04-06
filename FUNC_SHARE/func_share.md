# FUNC_SHARE Notes

## Cap nhat 2026-04-06

- Da tao `FUNC_SHARE/ham_dung_chung.php` de gom cac helper dung chung cho dashboard.
- Cac nhom helper hien co:
  - Khoi tao session thong nhat cho cac module.
  - Chan truy cap theo IP noi bo, mac dinh chi cho phep `10.64.0.108`.
  - Chuan hoa ngay, text, HTML, JSON response.
  - Helper query MySQL / Oracle dung lai cho nhieu man hinh.
  - Cau hinh menu ngang va thong tin footer cho trang chu `index.php`.

## Bien moi truong co the dung

- `DASHBOARD_ALLOWED_IPS`: ghi de danh sach IP duoc phep, cach nhau boi dau phay.
- `DASHBOARD_CREATOR_NAME`: ten hien thi o chan trang.
- `DASHBOARD_CREATOR_UNIT`: don vi / nhom phat trien hien thi o chan trang.
- `DASHBOARD_FOOTER_NOTE`: ghi chu them o chan trang.

## Luu y

- File nay uu tien gom helper an toan, it anh huong nghiep vu.
- Cac ham nghiep vu rieng cua tung module van giu o file module de tranh refactor qua rong.
- Neu can mo rong tiep, uu tien dua cac helper lap lai vao day truoc khi sua code o tung module.
## Cap nhat giao dien 2026-04-06

- `index.php` da bo khoi header phu trong than trang de layout gon hon.
- `iframe` noi dung duoc dong bo chieu cao theo noi dung thuc te cua module, giam khoang trang thua khi module ngan.
- Footer dashboard doi sang dang full-width, khong con bi co lai thanh mot o nho le trai.