<?php

function home_page_base_sql_core(): string
{
    return "
        SELECT
            h.KU_MAPGD AS MAPOS,
            p.PO_TEN AS TENPOS,
            SUBSTR(h.KU_MADP, 1, 6) AS MAXA,
            x.TEN AS TENXA,
            h.KU_MAKH,
            kh.KH_TENKH,
            h.KU_SOKU,
            h.KU_NGAYVAY,
            h.KU_NGAYDHAN_3 AS NGAYDENHAN,
            s.SC_TEN AS TENCTVAY,
            CASE WHEN h.KU_TTMONVAY <> 'CLOSE' THEN NVL(h.KU_DNOTHAN, 0) ELSE 0 END AS DNTH,
            CASE WHEN h.KU_TTMONVAY <> 'CLOSE' THEN NVL(h.KU_DNOQHAN, 0) ELSE 0 END AS DNQH,
            CASE WHEN h.KU_TTMONVAY <> 'CLOSE' THEN NVL(h.KU_DNOKHOANH, 0) ELSE 0 END AS DNKH,
            CASE WHEN h.KU_TTMONVAY <> 'CLOSE' THEN (h.KU_DNOTHAN + h.KU_DNOQHAN + h.KU_DNOKHOANH) ELSE 0 END AS DUNO,
            NVL(h.KU_M_GNGAN, 0) AS CHOVAY,
            h.KU_A_GNGAN AS DSCHOVAY,
            NVL(h.KU_M_TNTHAN, 0) + NVL(h.KU_M_TNQHAN, 0) + NVL(h.KU_M_TNKHOANH, 0) AS THUNO,
            NVL(h.KU_A_TNTHAN, 0) + NVL(h.KU_A_TNQHAN, 0) + NVL(h.KU_A_TNKHOANH, 0) AS DSTHUNO,
            NVL(tu.TO_MATO, h.KU_MATO) AS MATO,
            tu.TO_TENTT AS TENTT,
            CASE WHEN h.KU_NGUONVON = '1' THEN 'TW' ELSE 'DP' END AS NGUON_VON
        FROM HSCV_DAILY h
        LEFT JOIN DMPOS p ON p.PO_MA = h.KU_MAPGD
        LEFT JOIN DMXA x ON x.MA = SUBSTR(h.KU_MADP, 1, 6) AND x.GDXFLG = 'Y'
        LEFT JOIN DM_SCHEME s ON s.SC_MA = h.KU_SCHEM_CD
        LEFT JOIN HSTO tu ON tu.TO_MATO = h.KU_MATO AND tu.TO_MAPGD = h.KU_MAPGD
        LEFT JOIN HSKH kh ON kh.KH_MAKH = h.KU_MAKH AND kh.KH_MAPGD = h.KU_MAPGD AND kh.KH_TTRANG = 'A'
        WHERE h.KU_NGAYBC = TO_DATE(:P_NGAYBC, 'DD/MM/YYYY')
          AND DECODE(SUBSTR(:P_MAPOS, 3, 2), '00', 1, h.KU_MAPGD) = DECODE(SUBSTR(:P_MAPOS, 3, 2), '00', 1, '00' || :P_MAPOS)
        ORDER BY 1
        
    ";
}

function home_page_base_sql_grouped_core(): string
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
            SUM(DNTH) AS DNTH,
            SUM(DNKH) AS DNKH,
            SUM(CHOVAY) AS CHOVAY,
            SUM(THUNO) AS THUNO
        FROM (" . home_page_base_sql_core() . ")
        GROUP BY
            MAPOS,
            TENPOS,
            MAXA,
            TENXA,
            TENCTVAY
    ";
}

function home_page_base_sql(): string
{
    return "
        SELECT MAPOS, MAXA, TENXA, DUNO, CHOVAY, THUNO
        FROM (" . home_page_base_sql_grouped_core() . ")
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
            s.TOTAL_KH,
            s.DUNO_TW,
            s.DUNO_DP
        FROM (
            SELECT
                SUM(DUNO) AS DUNO,
                SUM(DNQH) AS DNQH,
                SUM(DNTH) AS DNTH,
                SUM(DNKH) AS DNKH,
                SUM(CHOVAY) AS CHOVAY,
                SUM(THUNO) AS THUNO,
                COUNT(*) AS TOTAL_ROWS
            FROM (" . home_page_base_sql_grouped_core() . ")
        ) g
        CROSS JOIN (
            SELECT
                COUNT(DISTINCT KU_MAKH) AS TOTAL_KH,
                SUM(CASE WHEN NGUON_VON = 'TW' THEN DUNO ELSE 0 END) AS DUNO_TW,
                SUM(CASE WHEN NGUON_VON = 'DP' THEN DUNO ELSE 0 END) AS DUNO_DP
            FROM (" . home_page_base_sql_core() . ")
        ) s
    ";
}


function home_page_top_pos_sql(): string
{
    return "
        SELECT
            h.KU_MAPGD AS MAPOS,
            p.PO_TEN AS TENPOS,
            SUM(h.KU_DNOTHAN + h.KU_DNOQHAN + h.KU_DNOKHOANH) AS DUNO
        FROM HSCV_DAILY h
        LEFT JOIN DMPOS p ON p.PO_MA = h.KU_MAPGD
        WHERE trunc(h.KU_NGAYBC) = TO_DATE(:P_NGAYBC, 'DD/MM/YYYY')
          AND DECODE(SUBSTR(:P_MAPOS, 3, 2), '00', 1, h.KU_MAPGD) = DECODE(SUBSTR(:P_MAPOS, 3, 2), '00', 1, '00' || :P_MAPOS)
          AND h.KU_TTMONVAY <> 'CLOSE'
        GROUP BY h.KU_MAPGD, p.PO_TEN
        ORDER BY DUNO DESC
    ";
}


function home_page_top_xa_sql(): string
{
    return "
        SELECT
            SUBSTR(h.KU_MADP, 1, 6) AS MAXA,
            x.TEN AS TENXA,
            SUM(h.KU_DNOTHAN + h.KU_DNOQHAN + h.KU_DNOKHOANH) AS DUNO
        FROM HSCV_DAILY h
        LEFT JOIN DMXA x ON x.MA = SUBSTR(h.KU_MADP, 1, 6) AND x.GDXFLG = 'Y'
        WHERE trunc(h.KU_NGAYBC) = TO_DATE(:P_NGAYBC, 'DD/MM/YYYY')
          AND DECODE(SUBSTR(:P_MAPOS, 3, 2), '00', 1, h.KU_MAPGD) = DECODE(SUBSTR(:P_MAPOS, 3, 2), '00', 1, '00' || :P_MAPOS)
          AND h.KU_TTMONVAY <> 'CLOSE'
        GROUP BY SUBSTR(h.KU_MADP, 1, 6), x.TEN
        ORDER BY DUNO DESC
    ";
}

function home_page_top_scheme_sql(): string
{
    return "
        SELECT
            s.SC_TEN AS TENCTVAY,
            SUM(h.KU_DNOTHAN + h.KU_DNOQHAN + h.KU_DNOKHOANH) AS DUNO
        FROM HSCV_DAILY h
        LEFT JOIN DM_SCHEME s ON s.SC_MA = h.KU_SCHEM_CD
        WHERE TRUC(h.KU_NGAYBC) = TO_DATE(:P_NGAYBC, 'DD/MM/YYYY')
          AND DECODE(SUBSTR(:P_MAPOS, 3, 2), '00', 1, h.KU_MAPGD) = DECODE(SUBSTR(:P_MAPOS, 3, 2), '00', 1, '00' || :P_MAPOS)
          AND h.KU_TTMONVAY <> 'CLOSE'
        GROUP BY s.SC_TEN
        ORDER BY DUNO DESC
    ";
}

function dm_pos_sql(): string
{
    return "
        SELECT PO_MA, PO_TEN
        FROM DMPOS
        WHERE PO_MA = '00' || :P_MAPOS
        ORDER BY PO_MA
        union all
        SELECT '3400', 'TOÀN CHI NHÁNH' FROM DUAL
    ";
}
