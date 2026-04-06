<?php

function sql_phan_loai_nguon_von_home_page(): string
{
    return "CASE WHEN h.KU_NGUONVON = '1' THEN 'TW' ELSE 'DP' END";
}

function sql_nen_home_page(bool $apDungLocNguonVon = true): string
{
    $phanLoaiNguonVon = sql_phan_loai_nguon_von_home_page();
    $dieuKienLocNguonVon = $apDungLocNguonVon
        ? "
          AND (
              :P_NGUONVON IS NULL
              OR {$phanLoaiNguonVon} = :P_NGUONVON
          )
        "
        : '';

    return "
        SELECT
            h.KU_MAPGD AS MAPOS,
            p.PO_TEN AS TENPOS,
            SUBSTR(h.KU_MADP, 1, 6) AS MAXA,
            x.TEN AS TENXA,
            NVL(s.SC_TEN, 'Không rõ CT vay') AS TENCTVAY,
            CASE WHEN h.KU_TTMONVAY <> 'CLOSE' THEN NVL(h.KU_DNOTHAN, 0) ELSE 0 END AS DNTH,
            CASE WHEN h.KU_TTMONVAY <> 'CLOSE' THEN NVL(h.KU_DNOQHAN, 0) ELSE 0 END AS DNQH,
            CASE WHEN h.KU_TTMONVAY <> 'CLOSE' THEN NVL(h.KU_DNOKHOANH, 0) ELSE 0 END AS DNKH,
            CASE WHEN h.KU_TTMONVAY <> 'CLOSE'
                THEN NVL(h.KU_DNOTHAN, 0) + NVL(h.KU_DNOQHAN, 0) + NVL(h.KU_DNOKHOANH, 0)
                ELSE 0
            END AS DUNO,
            NVL(h.KU_M_GNGAN, 0) AS CHOVAY,
            NVL(h.KU_M_TNTHAN, 0) + NVL(h.KU_M_TNQHAN, 0) + NVL(h.KU_M_TNKHOANH, 0) AS THUNO,
            h.KU_MAKH,
            {$phanLoaiNguonVon} AS NGUON_VON
        FROM HSCV_DAILY h
        LEFT JOIN DMPOS p ON p.PO_MA = h.KU_MAPGD
        LEFT JOIN DMXA x ON x.MA = SUBSTR(h.KU_MADP, 1, 6) AND x.GDXFLG = 'Y'
        LEFT JOIN DM_SCHEME s ON s.SC_MA = h.KU_SCHEM_CD
        WHERE h.KU_NGAYBC = TO_DATE(:P_NGAYBC, 'DD/MM/YYYY')
          AND (
              :P_MAPOS IS NULL
              OR SUBSTR(:P_MAPOS, 3, 2) = '00'
              OR h.KU_MAPGD = '00' || :P_MAPOS
          )
          {$dieuKienLocNguonVon}
    ";
}

function sql_nhom_chi_tiet_home_page(): string
{
    return "
        SELECT
            MAPOS,
            TENPOS,
            MAXA,
            TENXA,
            SUM(DUNO) AS DUNO,
            SUM(DNQH) AS DNQH,
            SUM(DNTH) AS DNTH,
            SUM(DNKH) AS DNKH,
            SUM(CHOVAY) AS CHOVAY,
            SUM(THUNO) AS THUNO
        FROM (" . sql_nen_home_page() . ")
        GROUP BY
            MAPOS,
            TENPOS,
            MAXA,
            TENXA
    ";
}

function sql_chi_tiet_home_page(): string
{
    return "
        SELECT
            MAPOS,
            TENPOS,
            MAXA,
            TENXA,
            DUNO,
            DNQH,
            DNTH,
            DNKH,
            CHOVAY,
            THUNO
        FROM (" . sql_nhom_chi_tiet_home_page() . ")
        ORDER BY MAPOS, MAXA, TENXA
    ";
}

function sql_nhom_chuong_trinh_vay_home_page(): string
{
    return "
        SELECT
            MAPOS,
            TENPOS,
            MAXA,
            TENXA,
            TENCTVAY,
            SUM(DUNO) AS DUNO,
            SUM(DNQH) AS DNQH,
            SUM(DNKH) AS DNKH,
            SUM(CHOVAY) AS CHOVAY,
            SUM(THUNO) AS THUNO
        FROM (" . sql_nen_home_page() . ")
        GROUP BY
            MAPOS,
            TENPOS,
            MAXA,
            TENXA,
            TENCTVAY
    ";
}

function sql_chuong_trinh_vay_home_page(): string
{
    return "
        SELECT
            MAPOS,
            TENPOS,
            MAXA,
            TENXA,
            TENCTVAY,
            DUNO,
            DNQH,
            DNKH,
            CHOVAY,
            THUNO
        FROM (" . sql_nhom_chuong_trinh_vay_home_page() . ")
        ORDER BY MAPOS, MAXA, TENCTVAY
    ";
}

function sql_tong_the_home_page(): string
{
    return "
        SELECT
            g.DUNO,
            g.DNQH,
            g.DNTH,
            g.DNKH,
            g.CHOVAY,
            g.THUNO,
            g.TOTAL_ROWS,
            s.TOTAL_KH
        FROM (
            SELECT
                NVL(SUM(DUNO), 0) AS DUNO,
                NVL(SUM(DNQH), 0) AS DNQH,
                NVL(SUM(DNTH), 0) AS DNTH,
                NVL(SUM(DNKH), 0) AS DNKH,
                NVL(SUM(CHOVAY), 0) AS CHOVAY,
                NVL(SUM(THUNO), 0) AS THUNO,
                COUNT(*) AS TOTAL_ROWS
            FROM (" . sql_nhom_chi_tiet_home_page() . ")
        ) g
        CROSS JOIN (
            SELECT COUNT(DISTINCT KU_MAKH) AS TOTAL_KH
            FROM (" . sql_nen_home_page() . ")
        ) s
    ";
}

function sql_tong_theo_nguon_von_home_page(): string
{
    return "
        SELECT
            NVL(SUM(CASE WHEN NGUON_VON = 'TW' THEN DUNO ELSE 0 END), 0) AS DUNO_TW,
            NVL(SUM(CASE WHEN NGUON_VON = 'DP' THEN DUNO ELSE 0 END), 0) AS DUNO_DP
        FROM (" . sql_nen_home_page(false) . ")
    ";
}

function sql_top_chuong_trinh_vay_home_page(): string
{
    return "
        SELECT
            TENCTVAY,
            SUM(DUNO) AS DUNO
        FROM (" . sql_nhom_chuong_trinh_vay_home_page() . ")
        GROUP BY TENCTVAY
        ORDER BY DUNO DESC
    ";
}

function sql_danh_muc_pos_home_page(): string
{
    return "
        SELECT PO_MA, PO_TEN
        FROM DMPOS
        WHERE PO_MA = '00' || :P_MAPOS
        UNION ALL
        SELECT '3400', 'TOAN CHI NHANH' FROM DUAL
        ORDER BY PO_MA
    ";
}