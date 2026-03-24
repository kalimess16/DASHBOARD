<?php

function dgx_fixed_points_sql(): string
{
    return "
        SELECT DISTINCT
            m.ma_pgd,
            (SELECT p.po_ten FROM dmpos p WHERE p.po_ma = m.ma_pgd) AS tenpos,
            m.ma_diem_gdx,
            m.ten_diem_gdx,
            TO_CHAR(m.ngay_gdx) AS ngay_gdx
        FROM TRANSPOINT_SUBCOMMUNE_MAPPING m
        WHERE m.ngay_capnhat = (SELECT MAX(x.ngay_capnhat) FROM TRANSPOINT_SUBCOMMUNE_MAPPING x)
          AND (
                :ngay_gdx = 'All'
                OR REGEXP_REPLACE(TRIM(TO_CHAR(m.ngay_gdx)), '[^0-9]', '') = :ngay_gdx_digits
                OR REGEXP_REPLACE(TRIM(TO_CHAR(m.ngay_gdx)), '[^0-9]', '') = :ngay_gdx_digits_alt
                OR (
                    :ngay_gdx_day_only <> ''
                    AND (
                        SUBSTR(REGEXP_REPLACE(TRIM(TO_CHAR(m.ngay_gdx)), '[^0-9]', ''), 1, 2) = :ngay_gdx_day_only
                        OR SUBSTR(REGEXP_REPLACE(TRIM(TO_CHAR(m.ngay_gdx)), '[^0-9]', ''), 7, 2) = :ngay_gdx_day_only
                    )
                )
          )
          AND (
                :fixed_keyword = ''
                OR UPPER(TRIM(TO_CHAR(m.ma_diem_gdx))) LIKE :fixed_keyword_like
                OR UPPER(TRIM(TO_CHAR(m.ten_diem_gdx))) LIKE :fixed_keyword_like
          )
        ORDER BY 1
    ";
}

function dgx_fixed_dates_sql(): string
{
    return "
        SELECT DISTINCT TO_CHAR(ngay_gdx) AS ngay_gdx
        FROM TRANSPOINT_SUBCOMMUNE_MAPPING
        WHERE ngay_capnhat = (SELECT MAX(ngay_capnhat) FROM TRANSPOINT_SUBCOMMUNE_MAPPING)
        ORDER BY 1 DESC
    ";
}

function dgx_base_sql(string $dgx_search = ''): string
{
    return "
        SELECT DISTINCT
            m.ma_pgd,
            (SELECT p.po_ten FROM dmpos p WHERE p.po_ma = m.ma_pgd) AS tenpos,
            s.dgx,
            m.ten_diem_gdx,
            m.ngay_gdx,
            s.ngay_gd
        FROM (
            SELECT DISTINCT
                SUBSTR(item_name, 1, 10) AS dgx,
                TRUNC(from_date_value) AS ngay_gd
            FROM SYNC_CONTROL
            WHERE TRUNC(from_date_value) = TO_DATE(:from_date_value, 'DD/MM/YYYY')
              AND SUBSTR(item_name, 1, 4) = 'TXN0'
              AND (ITEM_NAME LIKE '%.Offline' or ITEM_NAME LIKE '%.signature.gz')
              AND (
                    :dgx_search IS NULL
                    OR UPPER(SUBSTR(item_name, 1, 10)) = :dgx_search
                    OR UPPER(SUBSTR(item_name, 1, 10)) LIKE :dgx_search_prefix
              )
        ) s
        INNER JOIN (
            SELECT
                ma_pgd,
                ma_diem_gdx,
                ten_diem_gdx,
                MAX(ngay_gdx) AS ngay_gdx
            FROM TRANSPOINT_SUBCOMMUNE_MAPPING
            WHERE ngay_capnhat = (SELECT MAX(x.ngay_capnhat) FROM TRANSPOINT_SUBCOMMUNE_MAPPING x)
            GROUP BY ma_pgd, ma_diem_gdx, ten_diem_gdx
        ) m ON m.ma_diem_gdx = s.dgx
    ";
}

function dgx_report_sql(): string
{
    return "
        WITH
            PARAMS AS (
                SELECT TO_DATE(:P_NGAYBC, 'DD/MM/YYYY') AS NGAYBC, :P_MADGD AS MADGD
                FROM DUAL
            ),
            FILTER_CODES AS (
                SELECT DISTINCT
                    UPPER(TRIM(REGEXP_SUBSTR((SELECT MADGD FROM PARAMS), '[^;]+', 1, LEVEL))) AS MA_DIEM_GDX
                FROM DUAL
                CONNECT BY REGEXP_SUBSTR((SELECT MADGD FROM PARAMS), '[^;]+', 1, LEVEL) IS NOT NULL
            ),
            GDTN_DAY AS (
                SELECT /*+ MATERIALIZE */
                    GDV, MA_PGD, MA_DIEM_GDX, LOAI_GIAO_DICH, MA_TO, MA_KH, SO_KU, SO_TIEN_LAI, PT_TRA_NO, SO_TIEN_GOC, NGAY_GD
                FROM OFL_GDTN g
                WHERE TRUNC(g.NGAY_GD) = (SELECT NGAYBC FROM PARAMS)
                  AND (
                        (SELECT MADGD FROM PARAMS) IS NULL
                        OR EXISTS (
                            SELECT 1
                            FROM FILTER_CODES f
                            WHERE f.MA_DIEM_GDX = UPPER(TRIM(g.MA_DIEM_GDX))
                        )
                  )
            ),
            TKTO_DAY AS (
                SELECT /*+ MATERIALIZE */
                    GDV, MA_PGD, MA_DIEM_GDX, MA_KH, SO_TIEN_GUI, LAI_TRA, GOC_TRA
                FROM OFL_TKTO
                WHERE TRUNC(NGAY_GD) = (SELECT NGAYBC FROM PARAMS)
            ),
            GDTG_DAY AS (
                SELECT /*+ MATERIALIZE */
                    GDV, MA_PGD, MA_DIEM_GDX, MA_KH, SO_TIEN_GUI, SO_TIEN_RUT
                FROM OFL_GDTG
                WHERE MA_SAN_PHAM = '105'
                  AND TRUNC(NGAY_GD) = (SELECT NGAYBC FROM PARAMS)
            ),
            TIDE_BOOKING_DAY AS (
                SELECT /*+ MATERIALIZE */
                    GDV, MA_PGD, MA_DIEM_GDX, SO_SO, DU_GOC
                FROM OFL_TIDE_BOOKING
                WHERE TRUNC(NGAY_GD) = (SELECT NGAYBC FROM PARAMS)
            ),
            TIDE_WITHDRAW_DAY AS (
                SELECT /*+ MATERIALIZE */
                    GDV, MA_PGD, MA_DIEM_GDX, SO_SO, SO_TIEN_THUC_NHAN
                FROM OFL_TIDE_WITHDRAWAL
                WHERE TRUNC(NGAY_GD) = (SELECT NGAYBC FROM PARAMS)
            ),
            GDGN_DAY AS (
                SELECT /*+ MATERIALIZE */
                    GDV, MA_PGD, MA_DIEM_GDX, SO_KU, SO_TIEN_GN
                FROM OFL_GDGN
                WHERE TRUNC(NGAY_GD) = (SELECT NGAYBC FROM PARAMS)
            ),
            AA AS (
                SELECT DISTINCT GDV, MA_PGD, MA_DIEM_GDX, NGAY_GD
                FROM GDTN_DAY
            ),
            TEN_DIEM_GDX AS (
                SELECT DISTINCT ma_dgd, ten_dgd
                FROM dm_dia_phuong_dgd
            ),
            TK_G AS (
                SELECT B.ma_pgd, B.ma_diem_gdx, B.GDV,
                    SOTO, SOKU,
                    TONGGOC - (GOCTM_TO + GOCTM_CN) AS THUGOC_CK,
                    GOCTM_TO + GOCTM_CN AS THUGOC_TM,
                    TONGLAI - (LAITM_TO + LAITM_CN) AS THULAI_CK,
                    LAITM_TO + LAITM_CN AS THULAI_TM
                FROM (
                    SELECT GDV, MA_PGD, MA_DIEM_GDX,
                        SUM(CASE WHEN LAI_TRA > SO_TIEN_GUI THEN SO_TIEN_GUI ELSE LAI_TRA END) AS LAITM_TO,
                        SUM(CASE WHEN GOC_TRA = 0 THEN 0
                                ELSE SO_TIEN_GUI - CASE WHEN LAI_TRA > SO_TIEN_GUI THEN SO_TIEN_GUI ELSE LAI_TRA END
                            END) AS GOCTM_TO
                    FROM TKTO_DAY
                    GROUP BY MA_PGD, MA_DIEM_GDX, GDV
                ) A
                INNER JOIN (
                    SELECT GDV, MA_PGD, MA_DIEM_GDX,
                        COUNT(DISTINCT MA_TO) AS SOTO,
                        COUNT(DISTINCT SO_KU) AS SOKU,
                        SUM(SO_TIEN_LAI) AS TONGLAI,
                        SUM(CASE WHEN PT_TRA_NO = 'D2GL' THEN SO_TIEN_LAI ELSE 0 END) AS LAITM_CN,
                        SUM(CASE WHEN PT_TRA_NO = 'D2GL' THEN SO_TIEN_GOC ELSE 0 END) AS GOCTM_CN,
                        SUM(SO_TIEN_GOC) AS TONGGOC
                    FROM GDTN_DAY
                    WHERE LOAI_GIAO_DICH = 'G'
                    GROUP BY MA_PGD, MA_DIEM_GDX, GDV
                ) B ON A.GDV = B.GDV AND A.ma_pgd = B.ma_pgd AND A.ma_diem_gdx = B.ma_diem_gdx
            ),
            GN AS (
                SELECT GDV, MA_PGD, MA_DIEM_GDX,
                    COUNT(DISTINCT SO_KU) AS SOKU,
                    SUM(SO_TIEN_GN) AS GIAINGAN
                FROM GDGN_DAY
                GROUP BY MA_PGD, MA_DIEM_GDX, GDV
            ),
            TK_TM AS (
                SELECT GDV, MA_PGD, MA_DIEM_GDX,
                    SUM(sokh) AS sokh_gdtk,
                    SUM(guitk_tm) AS guitk_tm,
                    SUM(ruttk_tm) AS ruttk_tm
                FROM (
                    SELECT GDV, MA_PGD, MA_DIEM_GDX,
                        COUNT(DISTINCT MA_KH) AS sokh,
                        SUM(SO_TIEN_GUI - LAI_TRA) AS guitk_tm,
                        0 AS ruttk_tm
                    FROM TKTO_DAY
                    GROUP BY MA_PGD, MA_DIEM_GDX, GDV
                    UNION ALL
                    SELECT GDV, MA_PGD, MA_DIEM_GDX,
                        COUNT(DISTINCT MA_KH) AS sokh,
                        SUM(SO_TIEN_GUI) AS guitk_tm,
                        SUM(SO_TIEN_RUT) AS ruttk_tm
                    FROM GDTG_DAY
                    GROUP BY MA_PGD, MA_DIEM_GDX, GDV
                )
                GROUP BY MA_PGD, GDV, MA_DIEM_GDX
            ),
            TNCN AS (
                SELECT GDV, MA_PGD, MA_DIEM_GDX,
                    COUNT(DISTINCT MA_KH) AS THUNO_CANHAN
                FROM GDTN_DAY
                WHERE LOAI_GIAO_DICH = 'P'
                GROUP BY MA_PGD, MA_DIEM_GDX, GDV
            ),
            TKCKH AS (
                SELECT GDV, MA_PGD, MA_DIEM_GDX,
                    SUM(sokh_tkckh) AS sokh_tkckh,
                    SUM(guitk) AS guitk,
                    SUM(ruttk) AS ruttk
                FROM (
                    SELECT GDV, MA_PGD, MA_DIEM_GDX,
                        COUNT(SO_SO) AS sokh_tkckh,
                        SUM(DU_GOC) AS guitk,
                        SUM(0) AS ruttk
                    FROM TIDE_BOOKING_DAY
                    GROUP BY MA_PGD, MA_DIEM_GDX, GDV
                    UNION ALL
                    SELECT GDV, MA_PGD, MA_DIEM_GDX,
                        COUNT(SO_SO) AS sokh_tkckh,
                        SUM(0) AS guitk,
                        SUM(SO_TIEN_THUC_NHAN) AS ruttk
                    FROM TIDE_WITHDRAW_DAY
                    GROUP BY MA_PGD, MA_DIEM_GDX, GDV
                )
                GROUP BY MA_PGD, MA_DIEM_GDX, GDV
            )
        SELECT DISTINCT
            AA.ma_pgd AS MAPOS,
            CB.HOTEN AS TEN_GDV,
            gg.ten_dgd AS DIEM_GDX,
            cc.soto AS TO_TN,
            cc.soku AS SO_KU,
            bb.soku AS KH_GN,
            bb.giaingan AS SOTIEN_GN,
            ee.THUNO_CANHAN AS KH_TNCN,
            ff.sokh_tkckh AS KH_TKCKH,
            ff.guitk AS GUITK,
            ff.ruttk AS RUTTK
        FROM AA
        LEFT JOIN HS_CANBO_TTBC cb ON CB.MACB = AA.GDV
        LEFT JOIN TK_G cc ON aa.gdv = cc.gdv AND aa.ma_pgd = cc.ma_pgd AND aa.ma_diem_gdx = cc.ma_diem_gdx
        LEFT JOIN GN bb ON aa.gdv = bb.gdv AND aa.ma_pgd = bb.ma_pgd AND aa.ma_diem_gdx = bb.ma_diem_gdx
        LEFT JOIN TK_TM dd ON aa.gdv = dd.gdv AND aa.ma_pgd = dd.ma_pgd AND aa.ma_diem_gdx = dd.ma_diem_gdx
        LEFT JOIN TNCN ee ON aa.gdv = ee.gdv AND aa.ma_pgd = ee.ma_pgd AND aa.ma_diem_gdx = ee.ma_diem_gdx
        LEFT JOIN TKCKH ff ON aa.gdv = ff.gdv AND aa.ma_pgd = ff.ma_pgd AND aa.ma_diem_gdx = ff.ma_diem_gdx
        INNER JOIN TEN_DIEM_GDX gg ON aa.ma_diem_gdx = gg.ma_dgd
        ORDER BY 1, DIEM_GDX
    ";
}

function dgx_missing_codes_sql(): string
{
    return "
        WITH CODE_LIST AS (
            SELECT REGEXP_SUBSTR(:P_CODES, '[^;]+', 1, LEVEL) AS MA_DIEM_GDX
            FROM DUAL
            CONNECT BY REGEXP_SUBSTR(:P_CODES, '[^;]+', 1, LEVEL) IS NOT NULL
        )
        SELECT DISTINCT UPPER(TRIM(c.MA_DIEM_GDX)) AS MA_DIEM_GDX
        FROM CODE_LIST c
        WHERE c.MA_DIEM_GDX IS NOT NULL
          AND NOT EXISTS (
              SELECT 1
              FROM OFL_GDTN g
              WHERE TRUNC(g.NGAY_GD) = TO_DATE(:P_NGAYBC, 'DD/MM/YYYY')
                AND UPPER(TRIM(g.MA_DIEM_GDX)) = UPPER(TRIM(c.MA_DIEM_GDX))
          )
        ORDER BY 1
    ";
}

