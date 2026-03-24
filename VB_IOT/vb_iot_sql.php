<?php

function vb_iot_year_options_sql(): string
{
    return "
        SELECT DISTINCT YEAR(ngaytao) AS y
        FROM eoffice_approval
        WHERE ngaytao IS NOT NULL
        ORDER BY y DESC
    ";
}

function vb_iot_mark_page_sql(string $whereSql): string
{
    return "
        SELECT d.maso
        FROM eoffice_approval d
        {$whereSql}
        ORDER BY DATE(d.ngaytao) DESC, d.maso DESC
        LIMIT ? OFFSET ?
    ";
}

function vb_iot_mark_all_pages_sql(string $whereSql): string
{
    return "
        SELECT d.maso
        FROM eoffice_approval d
        {$whereSql}
        ORDER BY DATE(d.ngaytao) DESC, d.maso DESC
    ";
}

function vb_iot_count_sql(string $whereSql): string
{
    return "
        SELECT COUNT(*) AS total
        FROM eoffice_approval d
        {$whereSql}
    ";
}

function vb_iot_list_sql(string $whereSql): string
{
    return "
        SELECT
            d.maso,
            d.sokyhieu,
            d.tieude,
            d.ngaytao
        FROM eoffice_approval d
        {$whereSql}
        ORDER BY DATE(d.ngaytao) DESC, d.maso DESC
        LIMIT ? OFFSET ?
    ";
}

function vb_iot_receiver_sql(string $placeholders): string
{
    return "
        SELECT DISTINCT
            a.MaSo AS maso,
            c.poscd,
            c.fullname AS ten
        FROM eoffice_approval a
        INNER JOIN eoffice_approval_receiver b ON a.MaSo = b.MaSo
        INNER JOIN `user` c ON c.username = b.NguoiNhan
        WHERE a.MaSo IN ({$placeholders})
        ORDER BY c.poscd, a.MaSo DESC, c.fullname ASC
    ";
}

function vb_iot_read_filter_sql(string $whereSql, string $readPlaceholders): string
{
    return "
        SELECT COUNT(*) AS total
        FROM eoffice_approval d
        " . ($whereSql !== '' ? $whereSql . ' AND ' : ' WHERE ') . "d.maso IN ({$readPlaceholders})
    ";
}

function vb_iot_source_file_sql(): string
{
    return "
        SELECT
            d.maso,
            d.tieude,
            d.ngaytao,
            f.TenFile AS tenFile,
            f.KieuFile AS kieuFIile,
            f.DuLieu AS duLieu
        FROM eoffice_approval d
        INNER JOIN eoffice_approval_file f ON f.MaSo = d.maso
        WHERE d.maso = ?
        ORDER BY f.TenFile ASC
        LIMIT 1
    ";
}

function vb_iot_archive_exists_sql(): string
{
    return "
        SELECT COUNT(*) AS total
        FROM save_eoffice
        WHERE maSo = ? AND maPl = ?
    ";
}

function vb_iot_archive_delete_by_key_sql(): string
{
    return "
        DELETE FROM save_eoffice
        WHERE maSo = ? AND maPl = ?
    ";
}

function vb_iot_archive_update_sql(): string
{
    return "
        UPDATE save_eoffice
        SET tenFile = ?, kieuFIile = ?, tieuDe = ?, ngayGui = ?, duLieu = ?
        WHERE maSo = ? AND maPl = ?
    ";
}

function vb_iot_archive_insert_sql(): string
{
    return "
        INSERT INTO save_eoffice (maPl, maSo, tenFile, kieuFIile, tieuDe, ngayGui, duLieu)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";
}

function vb_iot_archive_list_sql(): string
{
    return "
        SELECT
            x.maSo,
            x.maPl,
            MAX(x.tenFile) AS tenFile,
            MAX(x.kieuFIile) AS kieuFIile,
            MAX(x.tieuDe) AS tieuDe,
            MAX(x.ngayGui) AS ngayGui
        FROM save_eoffice x
        WHERE maPl = ?
        GROUP BY x.maSo, x.maPl
        ORDER BY MAX(x.ngayGui) DESC, x.maSo DESC
    ";
}

function vb_iot_archive_raw_sql(): string
{
    return "
        SELECT tenFile, kieuFIile, duLieu
        FROM save_eoffice
        WHERE maSo = ? AND maPl = ?
        ORDER BY ngayGui DESC
        LIMIT 1
    ";
}
