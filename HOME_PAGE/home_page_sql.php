<?php

function home_page_source_case_sql(): string
{
    return "CASE WHEN h.KU_NGUONVON = '1' THEN 'TW' ELSE 'DP' END";
}

function home_page_base_sql_core(bool $applySourceFilter = true): string
{
    $sourceCase = home_page_source_case_sql();
    $sourceFilterSql = $applySourceFilter
        ? "
          AND (
              :P_NGUONVON IS NULL
              OR {$sourceCase} = :P_NGUONVON
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
            {$sourceCase} AS NGUON_VON
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
          {$sourceFilterSql}
    ";
}

function home_page_detail_grouped_sql_core(): string
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
        FROM (" . home_page_base_sql_core() . ")
        GROUP BY
            MAPOS,
            TENPOS,
            MAXA,
            TENXA
    ";
}

function home_page_detail_sql(): string
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
        FROM (" . home_page_detail_grouped_sql_core() . ")
        ORDER BY MAPOS, MAXA, TENXA
    ";
}

function home_page_scheme_breakdown_grouped_core(): string
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
            SUM(DNKH) AS DNKH
        FROM (" . home_page_base_sql_core() . ")
        GROUP BY
            MAPOS,
            TENPOS,
            MAXA,
            TENXA,
            TENCTVAY
    ";
}

function home_page_scheme_breakdown_sql(): string
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
            DNKH
        FROM (" . home_page_scheme_breakdown_grouped_core() . ")
        ORDER BY MAPOS, MAXA, TENCTVAY
    ";
}

function home_page_totals_sql(): string
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
            FROM (" . home_page_detail_grouped_sql_core() . ")
        ) g
        CROSS JOIN (
            SELECT COUNT(DISTINCT KU_MAKH) AS TOTAL_KH
            FROM (" . home_page_base_sql_core() . ")
        ) s
    ";
}

function home_page_source_totals_sql(): string
{
    return "
        SELECT
            NVL(SUM(CASE WHEN NGUON_VON = 'TW' THEN DUNO ELSE 0 END), 0) AS DUNO_TW,
            NVL(SUM(CASE WHEN NGUON_VON = 'DP' THEN DUNO ELSE 0 END), 0) AS DUNO_DP
        FROM (" . home_page_base_sql_core(false) . ")
    ";
}

function home_page_top_scheme_sql(): string
{
    return "
        SELECT
            TENCTVAY,
            SUM(DUNO) AS DUNO
        FROM (" . home_page_scheme_breakdown_grouped_core() . ")
        GROUP BY TENCTVAY
        ORDER BY DUNO DESC
    ";
}

function dm_pos_sql(): string
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