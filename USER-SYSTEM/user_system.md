# USER-SYSTEM Notes

## Ma hoa bat buoc

- Tat ca file trong thu muc `USER-SYSTEM` phai luu bang `UTF-8` khong BOM.
- Khong chuyen file qua ANSI, Windows-1252, hoac cong cu lam hong text tieng Viet.
- Khi sua text tieng Viet trong PHP/HTML/JS, luon kiem tra lai hien thi truc tiep tren web sau khi luu.

## File chinh

- `USER-SYSTEM/user_system.php`
  - Trang chinh, xu ly list, detail, save, thong bao.
- `USER-SYSTEM/user_system_sql.php`
  - Chi chua ham SQL.
- `USER-SYSTEM/layout_insert.php`
  - Man hinh them moi tach rieng.
- `view/Style_user_system.php`
  - CSS cho giao dien `USER-SYSTEM`.

## Quy uoc hien tai

- Da bo hoan toan truong `giaoviec` khoi giao dien them moi va cap nhat.
- Khi insert hoac update, he thong khong nhan gia tri `giaoviec` tu form nua.
- SQL dang chu dong luu `giaoviec = NULL` de khop voi yeu cau hien tai.
- Man danh sach co them combobox loc theo phong ban, dung gia tri `department_code` tu bang `phongban`.
- Khi dang loc theo phong ban, phan trang, mo chi tiet va luu ban ghi phai giu nguyen bo loc vua chon.

## Cach kiem tra nhanh

1. Mo `USER-SYSTEM/user_system.php`.
2. Chon mot phong ban trong combobox va bam `Loc danh sach`.
3. Xac nhan danh sach chi con can bo thuoc phong ban da chon.
4. Thu ket hop loc theo phong ban voi tu khoa tim kiem va phan trang.
5. Mo sua mot can bo trong danh sach dang loc, luu lai, xac nhan man hinh quay ve dung bo loc cu.
6. Mo `USER-SYSTEM/layout_insert.php`, xac nhan form them moi van hoat dong binh thuong.