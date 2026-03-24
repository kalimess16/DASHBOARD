<?php

function user_system_list_sql(): string
{
    return "
        SELECT
            u.macanbo,
            u.hoten,
            CV.MOTA AS mota,
            p.tenphong,
            u.email,
            p.MAINTL AS mapos
        FROM canbo u
        LEFT JOIN phongban p ON p.MAPHONG = u.MAPHONG
        LEFT JOIN CHUCVU CV ON CV.MACV = u.MACHUCVU
        ORDER BY p.MAINTL
    ";
}

function user_system_count_sql(): string
{
    return "
        SELECT COUNT(*) AS total
        FROM canbo u
    ";
}

function user_system_detail_sql(): string
{
    return "
        SELECT
            u.macanbo,
            u.hoten,
            CV.MOTA AS mota,
            u.hashps AS password_hash,
            p.tenphong,
            u.email,
            u.MAPHONG AS maphong,
            u.MACHUCVU AS machucvu
        FROM canbo u
        INNER JOIN phongban p ON u.MAPHONG = p.MAPHONG
        LEFT JOIN CHUCVU CV ON CV.MACV = u.MACHUCVU
        WHERE u.macanbo = ?
        ORDER BY u.macanbo DESC
        LIMIT 1
    ";
}

function user_system_insert_sql(): string
{
    return "
        INSERT INTO canbo (
            macanbo,
            hoten,
            hashps,
            MAPHONG,
            MACHUCVU,
            email
        ) VALUES (?, ?, PASSWORD(?), ?, ?, ?)
    ";
}

function user_system_update_sql(bool $include_password = false): string
{
    if ($include_password) {
        return "
            UPDATE canbo
            SET
                hoten = ?,
                hashps = PASSWORD(?),
                MAPHONG = ?,
                MACHUCVU = ?,
                email = ?
            WHERE macanbo = ?
        ";
    }

    return "
        UPDATE canbo
        SET
            hoten = ?,
            MAPHONG = ?,
            MACHUCVU = ?,
            email = ?
        WHERE macanbo = ?
    ";
}

function user_system_role_sql(): string
{
    return "
        SELECT
            cv.MACV AS role_code,
            cv.MOTA AS role_name
        FROM CHUCVU cv
        ORDER BY cv.MOTA ASC
    ";
}

function user_system_department_sql(): string
{
    return "
        SELECT
            p.MAPHONG AS department_code,
            p.tenphong AS department_name
        FROM phongban p
        ORDER BY p.tenphong ASC
    ";
}

function user_system_posotion_sql(): string
{
    return "
        SELECT
            cv.MACV AS position_code,
            cv.MOTA AS position_name
        FROM CHUCVU cv
        ORDER BY cv.MOTA ASC
    ";
}

