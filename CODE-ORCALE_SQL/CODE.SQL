CREATE OR REPLACE PROCEDURE INTELLECT.PROC_DN_KT740_TH
(
    P_MAPOS         VARCHAR2,
    P_NGAYBC        VARCHAR2,
    P_THONGBAO      OUT VARCHAR2,
    P_TRAVE         OUT SYS_REFCURSOR
)
AS
    V_NGAYBC                VARCHAR2(20);
    V_QUERY                 CLOB;
    V_CODE                  VARCHAR2(6);
    V_PERMIT                VARCHAR2(1);
    V_POS                   VARCHAR2(6);
BEGIN
    -- LUU Y CHI THUC HIEN BAO CAO THEO DINH KY TUAN
    IF NOT FUNC_LAY_NGAY_HSTDCT_GN(P_NGAYBC, P_THONGBAO) THEN
        RETURN ;
    END IF;
    IF FUNC_KT_DB_RECIEVE(P_NGAYBC, P_THONGBAO) IS NOT NULL THEN
        P_THONGBAO:='Chưa có dữ liệu HSTDCT ngày ' || P_NGAYBC || '.Không thể tiếp tục !!' ;
        COMMIT ;
    END IF;
    
    SELECT TO_CHAR(TO_DATE(P_NGAYBC, 'DD/MM/YYYY'), 'DD-MON-YYYY') INTO V_NGAYBC FROM DUAL;
    DELETE DN_DATA_TEMP WHERE C1 = 'DN_KT740_TH';
    --DELETE HSTEST8 WHERE C1 = 'DN_KT740_TH';
    FOR POS IN (
        SELECT PO_MA FROM DMPOS 
        WHERE DECODE(SUBSTR(P_MAPOS, 3, 2), '00', 1, PO_MA) = DECODE(SUBSTR(P_MAPOS, 3, 2), '00' , 1, '00' || P_MAPOS)
             AND PO_MA NOT IN ('003410','003337')
        ORDER BY PO_MA
        ) 
        LOOP
     
        FOR CT IN (
            SELECT A.TH_CODE, A.TH_NAME, A.TH_QUERY, A.TH_CONNECT_KEY, 
                SRQ_QUERY, A.TH_WHERE, A.TH_QUERY_DETAIL, A.TH_PERIOD
            FROM DN_KT740_TH A 
            LEFT JOIN SAVE_RPT_QUERY 
            ON TH_ID_QUERY_TW = SRQ_SAVE_ID 
            WHERE TH_QUERY IS NOT NULL AND A.TH_CODE NOT IN ('M101', 'M11') -- M11: HIỆN VBSP_KTNB_LSUAT CHƯA ĐỒNG BỘ LS MỚI
            ORDER BY TH_ID_QUERY_TW
            )
        LOOP
            
            V_CODE := CT.TH_CODE;
            V_QUERY := CT.SRQ_QUERY;
            V_POS := POS.PO_MA;
            IF CT.TH_PERIOD IS NOT NULL THEN
                EXECUTE IMMEDIATE 
                    REPLACE(REPLACE(CT.TH_PERIOD, '<P_NGAYBC>', V_NGAYBC), '<P_MAPOS>', POS.PO_MA)
                INTO V_PERMIT;
            ELSE
                V_PERMIT := NULL;
            END IF;
            
            IF V_PERMIT IS NULL THEN
                            
                V_QUERY := REPLACE(V_QUERY, 'PV_POS_CD', POS.PO_MA);
                V_QUERY := REPLACE(V_QUERY, 'PD_REPORT_DATE', V_NGAYBC);
                V_QUERY := REPLACE(REPLACE(V_QUERY, '''PS_DUOITV''', 5), '''PS_TRENTV''', 60);
                V_QUERY := REPLACE(V_QUERY, '''PS_SOTHANG''', 3); 
                V_QUERY := REPLACE(V_QUERY, '''PN_SONGAY''', 1);
                V_QUERY := REPLACE(V_QUERY, '''PD_TU_NGAY''', '01' || SUBSTR(V_NGAYBC, 3));
                V_QUERY := REPLACE(V_QUERY, '''PD_DEN_NGAY''', V_NGAYBC);   
                
                IF V_QUERY IS NOT NULL THEN
                    V_QUERY := 'SELECT ''DN_KT740_TH'', ''' || V_CODE || ''', DMXA.MA, COUNT(NVL(' || CT.TH_CONNECT_KEY || ', ''X'')) KQ 
                                FROM DMXA INNER JOIN (' || V_QUERY || ') B ON ' || CT.TH_QUERY || ' = B.' || CT.TH_CONNECT_KEY || 
                                ' WHERE PGD_QL = '|| POS.PO_MA ||' AND DMXA.GDXFLG = ''Y'' ' ||
                                CASE WHEN CT.TH_WHERE IS NOT NULL THEN ' AND (' || CT.TH_WHERE || ') ' ELSE ' ' END ||
                                ' GROUP BY DMXA.MA';
                ELSE
                    V_QUERY := REPLACE(CT.TH_QUERY, '<P_TH_QUERY_DETAIL>', CT.TH_QUERY_DETAIL);
                    V_QUERY := REPLACE(V_QUERY, '<P_NGAYBC>', V_NGAYBC);
                    V_QUERY := REPLACE(V_QUERY, '<P_MAPOS>', POS.PO_MA);
                    V_QUERY := REPLACE(REPLACE(V_QUERY, '<KEY>', 'DN_KT740_TH'), '<CODE>', V_CODE);
                    
                END IF;                                
                V_QUERY := 'INSERT INTO DN_DATA_TEMP (C1, C2, C3, N1) ' || V_QUERY;    
                -- V_QUERY := 'INSERT INTO HSTEST8 (C1, C2, C3, N1) ' || V_QUERY;      
                EXECUTE IMMEDIATE V_QUERY;
            END IF;
        END LOOP;
    END LOOP;        
    
    UPDATE ( SELECT H.C4, X.PGD_QL FROM DN_DATA_TEMP H JOIN DMXA X ON X.MA = H.C3 WHERE H.C1 = 'DN_KT740_TH') SET C4 = PGD_QL;
    --UPDATE ( SELECT H.C4, X.PGD_QL FROM HSTEST8 H JOIN DMXA X ON X.MA = H.C3 WHERE H.C1 = 'DN_KT740_TH') SET C4 = PGD_QL;
   
    OPEN P_TRAVE FOR 
    SELECT C2 AS CODE, C3 AS MAXA, C4 AS MAPOS,
        TH_BAOCAO || TH_NAME AS CHITIEU, NVL(TH_DEP, 'ALL') AS DEP, N1 AS SOLIEU
    FROM DN_DATA_TEMP 
    INNER JOIN DN_KT740_TH ON C2 = TH_CODE
    LEFT JOIN DMXA ON C3 = DMXA.MA AND DMXA.TRANGTHAI = 'A' and DMXA.GDXFLG='Y'
    WHERE C1 = 'DN_KT740_TH'
    ORDER BY MAPOS, MAXA, CODE;
    
    
    EXCEPTION WHEN OTHERS THEN
        P_THONGBAO := SQLERRM() || ' CODE ' || V_CODE || '-POS ' || V_POS;
        DBMS_OUTPUT.PUT_LINE(
            'LOI | CODE=' || V_CODE || ' | POS=' || V_POS || ' | ' || SQLERRM
        );
                --P_THONGBAO := SQLERRM || ' | ' || DBMS_UTILITY.FORMAT_ERROR_BACKTRACE;
                --COMMIT ;
        COMMIT;
END ;
/

