CREATE OR REPLACE PACKAGE BODY INTELLECT.PCK_DN_NOXH
IS

PROCEDURE LAY_THONGTIN
(
    P_CMTVAY              VARCHAR2,
    P_MDVAY                VARCHAR2,
    P_THONGBAO      OUT     VARCHAR2,
    P_TRAVE             OUT     SYS_REFCURSOR
)
AS
    V_CHECK     INT;
 BEGIN
    
    SELECT COUNT(1) INTO V_CHECK
    FROM HSKH, HSCV_DAILY 
    WHERE KH_MAKH = KU_MAKH AND KU_SCHEM_CD = 'S01200' AND TRIM(UPPER(KH_CMT)) = TRIM(UPPER(P_CMTVAY)) AND
                KU_NGAYBC = (SELECT MAX(NGAY) FROM DB_RECEIVE WHERE LOAI = 'HSTDCTNGAY');
    IF V_CHECK > 0 THEN
        P_THONGBAO := 'KHÁCH HÀNG CÓ CMT SAI ' || P_CMTVAY || ' ĐÃ ĐƯỢC ĐĂNG KÝ THÔNG TIN INTELLECT. KHÔNG THỂ TIẾP TỤC !!';
        RETURN ;
    END IF;                
    
    OPEN P_TRAVE FOR
    SELECT A.CHIMUC, A.CHITIEU, A.COCMCHA, A.GHICHU, A.TTNHAP, A.COTEXTAREA, A.TRUONGDL, 
                A.THAYDL, A.MAUHD, NVL(D.SOHD, 0) SOHD, D.KHHD, NVL(D.GIATRI, ' ') GIATRI
    FROM DN_NOXH_DM A 
    LEFT JOIN 
    (
    SELECT B.KHHD, B.SOHD, C.CHIMUC, C.GIATRI
    FROM DN_NOXH B INNER JOIN DN_NOXH_CT C ON B.KHHD = C.KHHD AND B.SOHD = C.SOHD 
    WHERE TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY))
    ) D ON A.CHIMUC = D.CHIMUC            
    WHERE (A.MDVAY IS NULL OR INSTR(A.MDVAY, P_MDVAY) > 0)
    ORDER BY A.CHIMUC;
    
    EXCEPTION WHEN OTHERS THEN
        P_THONGBAO := SQLERRM();
          
END LAY_THONGTIN;

FUNCTION BANGCHU
(
    pSotien            VARCHAR2,
    pSoTphan        VARCHAR2,
    pDonvi             VARCHAR2
) RETURN VARCHAR2
AS
    sBangchu         VARCHAR2(800);
    sBangchuTp         VARCHAR2(800);
    sSotienchu        VARCHAR2(35);
    sSotienTp         VARCHAR2(35);

    FUNCTION DOCSO
    (
        sSotienchu         VARCHAR2
    )
    RETURN VARCHAR2
    AS
        sSotien     VARCHAR2(35);
        ret           VARCHAR2(800);
        sDonvi              VARCHAR2(100);
        sHangdonvi      VARCHAR2(100);
        sHangchuc       VARCHAR2(100);
        sHangtram       VARCHAR2(100);
        sHauto              VARCHAR2(50);
        i INT;
        baso                VARCHAR2(10);
        reSotien            VARCHAR2(35);
        reBaso              VARCHAR2(10);
        
    BEGIN
        sDonvi := '1một,2hai,3ba,4bốn,5năm,6sáu,7bảy,8tám,9chín,';
        sSotien := sSotienchu;
        
        IF LENGTH(sSotien) = 1 THEN
            ret := SUBSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, sSotien)), 2), 1, INSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, sSotien)), 2), ',') - 1);
        ELSIF LENGTH(sSotien) = 2 THEN
            IF sSotien = '10' THEN
                    ret := 'mười';
            ELSIF sSotien = '11' THEN
                ret := 'mười mốt';
            ELSE
                sHangchuc := SUBSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(sSotien, 1, 1))), 2), 1,
                                    INSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(sSotien, 1, 1))), 2), ',') - 1);
                    
                SELECT REVERSE(sSotien) INTO reSotien FROM DUAL;
                IF SUBSTR(reSotien, 1, 1) = '5' THEN
                    sHangdonvi := 'lam';
                ELSIF SUBSTR(reSotien, 1, 1) = '1' THEN
                    sHangdonvi := 'một';
                ELSIF SUBSTR(reSotien, 1, 1) <> '0' THEN
                    sHangdonvi := SUBSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(reSotien, 1, 1))), 2), 1,
                                        INSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(reSotien, 1, 1))), 2), ',') - 1);
                ELSE
                    sHangdonvi := '';
                END IF;
                ret := sHangchuc || ' mươi ' || sHangdonvi;
            END IF;
        ELSE
            i := 1;
            WHILE i <= 4 LOOP
                IF LENGTH(sSotien) > 0 THEN
                    IF i = 1 THEN
                        sHauto := ' trăm ';
                    ELSIF i = 2 THEN
                        sHauto := ' nghìn ';
                    ELSIF i = 3 THEN
                        sHauto := ' triệu ';
                    ELSE
                        sHauto := ' tỷ ';
                    END IF;
                        
                    IF LENGTH(TRIM(sSotien)) > 3 THEN
                        baso := TRIM(SUBSTR(sSotien, LENGTH(TRIM(sSotien)) - 2));
                    ELSE
                        baso := TRIM(sSotien);
                    END IF;                    
                        
                    IF baso <> '000' THEN
                        IF SUBSTR(baso, 2) = '00' THEN
                            sHangtram := SUBSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(baso, 1, 1))), 2), 1,
                                                    INSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(baso, 1, 1))), 2), ',') - 1);
                            IF i > 1 THEN       -- hang don vi                                                
                                ret := sHangtram || ' trăm ' || sHauto || ret ;
                            ELSE
                                ret := sHangtram || sHauto || ret ;
                            END IF;                            
                        ELSE
                            sHangdonvi := '';
                            sHangchuc := '';
                            sHangtram := '';
                            IF SUBSTR(baso, LENGTH(baso), 1) = '5' AND SUBSTR(baso,  LENGTH(baso) - 1, 1) <> '0' THEN
                                sHangdonvi := ' lăm ';
                            ELSIF SUBSTR(baso,  LENGTH(baso), 1) = '1' AND SUBSTR(baso,  LENGTH(baso) - 1, 1) NOT IN ('0', '1') THEN
                                sHangdonvi := ' một ';
                            ELSIF SUBSTR(baso,  LENGTH(baso), 1) <> '0' THEN
                                sHangdonvi := SUBSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(baso,  LENGTH(baso), 1))), 2), 1,
                                                    INSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(baso,  LENGTH(baso), 1))), 2), ',') - 1);
                            END IF;
                            IF LENGTH(baso) = 2 THEN
                                IF SUBSTR(baso, 1) = '0' THEN
                                    sHangchuc := ' lẻ ';
                                ELSIF baso = '10' OR SUBSTR(baso, 1, 1) = 1 THEN
                                    sHangchuc := ' mươi ';
                                ELSE
                                    sHangchuc := SUBSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(baso, 1, 1))), 2), 1,
                                                        INSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(baso, 1, 1))), 2), ',') - 1);
                                    sHangchuc := CONCAT(sHangchuc, ' mươi ');
                                END IF;
                            ELSIF LENGTH(baso) = 3 THEN
                                IF SUBSTR(baso, 2, 1) = '0' THEN
                                    sHangchuc := ' lẻ ';
                                ELSIF SUBSTR(baso, 2, 1) = '1' THEN
                                    sHangchuc := ' mươi ';
                                ELSE
                                  sHangchuc := SUBSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(baso, 2, 1))), 2), 1,
                                                     INSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(baso, 2, 1))), 2), ',') - 1);
                                  sHangchuc := CONCAT(sHangchuc, ' muoi ');
                                END IF;
                            END IF;
                            IF LENGTH(baso) = 3 THEN
                                IF SUBSTR(baso, 1, 1) = '0' THEN
                                    IF i > 1 then
                                        sHangtram := ' không trăm ';
                                    ELSE
                                        sHangtram := ' không ';
                                    END IF;        
                                ELSE
                                    sHangtram := SUBSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(baso, 1, 1))), 2), 1,
                                                 INSTR(SUBSTR(SUBSTR(sDonvi, INSTR(sDonvi, SUBSTR(baso, 1, 1))), 2), ',') - 1);
                                    IF i > 1 THEN                                           
                                        sHangtram := CONCAT(sHangtram, ' trăm ');
                                    END IF;                                    
                                END IF;
                            END IF;
                            IF i = 1 THEN
                                IF LENGTH(sHangtram) > 0 THEN
                                    ret := sHangtram || sHauto || sHangchuc || sHangdonvi ;
                                ELSE
                                    ret := sHangchuc || sHangdonvi ;                                
                                END IF;                                 
                            ELSE
                                IF LENGTH(sHangdonvi) > 0 THEN
                                    ret := sHangtram || sHangchuc || sHangdonvi || sHauto || ret ;
                                ELSIF LENGTH(sHangchuc) > 0 THEN
                                    ret := sHangtram || sHangchuc || sHauto || ret ;
                                ELSE
                                    ret := sHangtram || sHangchuc || sHangdonvi || ret ;
                                END IF;                                
                            END IF;
                        END IF;
                    END IF;
                        
                    sSotien := SUBSTR(sSotien, 1, LENGTH(sSotien) - 3) ;
                                            
                END IF;
                i := i + 1;
            END LOOP;
        END IF;
        RETURN ret;
    END;

   
    BEGIN
        IF pSotien = '0' THEN
            RETURN 'không ' || pDonvi;
        ELSIF pSotien = '5000000' THEN
            RETURN 'năm triệu ' || pDonvi;
        ELSIF pSotien = '5000' THEN
            RETURN 'năm nghìn ' || pDonvi;    
        END IF;
        
        sSotienchu := REPLACE(TRIM(pSotien), ' ', '');
        sSotienTp := NULL;
        
        -- Co so thap phan, phan biet boi dau phay (,)
        IF pSoTphan = 'Y' AND INSTR(sSotienchu, ',') > 0 THEN
            sSotienTp := TRIM(REPLACE(SUBSTR(sSotienchu, INSTR(sSotienchu, ',') + 1), ',', ''));
            sSotienchu := TRIM(SUBSTR(sSotienchu, 1, INSTR(sSotienchu, ',') - 1)) ;
        END IF;
        
        sBangchu := DOCSO(sSotienchu);
        IF sSotienTp IS NOT NULL AND LENGTH(sSotienTp) > 0 THEN
            sBangchuTp := DOCSO(sSotienTp);
            sBangchu := sBangchu || ' phẩy ' || sBangchuTp;
        END IF;
        sBangchu := REPLACE(TRIM(UPPER(SUBSTR(sBangchu, 1, 1)) || SUBSTR(sBangchu, 2)), '  ', ' ') || ' ' || pDonvi;
        RETURN sBangchu;
END BANGCHU;

PROCEDURE IN_MAUBIEU
(
    P_CMTVAY            VARCHAR2,
    P_MDVAY             VARCHAR2,
    P_TTHOPDONG     OUT     VARCHAR2,
    P_THONGBAO      OUT     VARCHAR2
)
AS
    V_KTSL          INT;
    V_DSH           VARCHAR2(2000);
    V_NGAYHD        VARCHAR2(200);
    V_GIATRI          VARCHAR2(2000);
    V_MDVAY         VARCHAR2(10);
BEGIN

    SELECT COUNT(1) INTO V_KTSL FROM DN_NOXH WHERE TRIM(UPPER(CMT)) = TRIM(UPPER(P_CMTVAY));
    IF V_KTSL = 0 THEN
        P_THONGBAO := 'Chưa nhập thông tin vay vốn NOXH của nguời vay có CMT này !!';
        RETURN;
    END IF;
    
    SELECT DISTINCT MDVAY INTO V_MDVAY FROM DN_NOXH WHERE TRIM(UPPER(CMT)) = TRIM(UPPER(P_CMTVAY));
    DELETE DN_NOXH_TEMP;
    
    SELECT C.GIATRI INTO P_TTHOPDONG
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND B.MDVAY = V_MDVAY AND
                TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND A.TRUONGDL = 'FNHD' AND A.MAUHD = '02';
    
    FOR CT IN (
                    SELECT MDVAY, STT, CHITIEU, MAUBIEU, TTHTML, VITRI, CHOPHEPIN
                    FROM DN_NOXH_MAUBIEU 
                    WHERE (MDVAY IS NULL OR INSTR(MDVAY, V_MDVAY) > 0) AND
                                INSTR(CASE WHEN V_MDVAY <> 'c' THEN '02,14,05,12' ELSE '02,05,04' END, MAUBIEU) > 0 
                    ORDER BY MAUBIEU, STT
                    )
    LOOP
        IF INSTR(CT.CHITIEU, '-F') > 0 AND (CT.CHOPHEPIN IS NULL OR CT.CHOPHEPIN <> 'opt') THEN
            FOR DL IN (
                            SELECT A.MAUHD, A.TRUONGDL, C.GIATRI, A.COBGCHU, A.COSOTPHAN, A.THAYDL
                            FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                            WHERE A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                                        TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = V_MDVAY AND 
                                        A.TRUONGDL IS NOT NULL 
                            ORDER BY A.MAUHD, C.CHIMUC
                            )
            LOOP
                
                DL.GIATRI := REPLACE(DL.GIATRI, CHR(10), '<BR>');
                CT.CHITIEU := REPLACE(CT.CHITIEU, '(' || DL.MAUHD || '-' || DL.TRUONGDL || ')', NVL(DL.GIATRI, ' .......... '));
                
                IF CT.CHOPHEPIN IS NOT NULL AND INSTR(CT.CHOPHEPIN, '-F') > 0 AND 
                    CT.CHOPHEPIN = DL.MAUHD || '-' || DL.TRUONGDL AND
                    DL.GIATRI IS NOT NULL AND LENGTH(TRIM(DL.GIATRI)) > 0 THEN
                    CT.CHOPHEPIN := 'Y';
                END IF;
                
                IF DL.COBGCHU IS NOT NULL AND SUBSTR(DL.COBGCHU, 1, 3) = 'FBC' AND 
                    DL.GIATRI IS NOT NULL AND TRIM(LENGTH(DL.GIATRI)) > 0 THEN
                    DL.GIATRI := REPLACE(REPLACE(LOWER(DL.GIATRI), 'đồng', ''), 'm2', '');
                    DL.GIATRI := REPLACE(REPLACE(DL.GIATRI, '.', ''), ' ', '');
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(FBC' || DL.MAUHD || '-' || DL.TRUONGDL || ')', BANGCHU(DL.GIATRI, NVL(DL.COSOTPHAN, 'N'), ''));
                END IF;
                
            END LOOP;
            
            IF INSTR(CT.CHITIEU, '-FNHD-D') > 0 THEN
                    SELECT C.GIATRI INTO V_NGAYHD 
                    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                    WHERE A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                                TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = V_MDVAY AND 
                                A.TRUONGDL = 'FNHD' AND A.MAUHD = CT.MAUBIEU ;
                            
                IF LENGTH(TRIM(V_NGAYHD)) = 0 THEN
                    V_NGAYHD := 'ngày ...... tháng ...... năm ............';
                ELSE
                    V_NGAYHD := 'ngày ' || SUBSTR(V_NGAYHD, 1, 2) || ' tháng ' || SUBSTR(V_NGAYHD, 4, 2) || ' năm ' || SUBSTR(V_NGAYHD, 7);                     
                END IF;   
                CT.CHITIEU := REPLACE(CT.CHITIEU, '(' || CT.MAUBIEU || '-FNHD-D)', V_NGAYHD);                         
            END IF;
            
       ELSIF CT.MAUBIEU IN ('04', '12') AND INSTR(CT.CHITIEU, '(<DONG_SO_HUU>)') > 0 THEN
                V_DSH := NULL;
                FOR DL IN (
                                SELECT A.CHITIEU, C.GIATRI
                                FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                                WHERE A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                                            TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = V_MDVAY AND 
                                            A.TRUONGDL = 'DSH'
                                ORDER BY A.MAUHD, C.CHIMUC
                                )
                LOOP
                    IF DL.GIATRI IS NOT NULL AND LENGTH(TRIM(DL.GIATRI)) > 0 THEN
                        IF V_DSH IS NULL THEN
                            V_DSH := '<b>CÁC ĐỒNG SỞ HỮU :</b><BR>';
                        END IF;                        
                        V_DSH := V_DSH || DL.CHITIEU || ' ' || DL.GIATRI || '<BR>';
                    END IF;                    
                END LOOP;
                CT.CHITIEU := REPLACE(CT.CHITIEU, '(<DONG_SO_HUU>)', V_DSH);       
        END IF;
        
        IF  CT.TTHTML IS NOT NULL AND LENGTH(TRIM(CT.TTHTML)) > 0 THEN
            CT.CHITIEU := CT.TTHTML || CT.CHITIEU || REPLACE(CT.TTHTML, '<', '</');
        END IF;
        
        IF INSTR(CT.CHITIEU, '(1204-FHDTC)') > 0 THEN
            FOR DL IN (
                    SELECT A.MAUHD, A.TRUONGDL, C.GIATRI
                    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                    WHERE A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                                TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = V_MDVAY AND 
                                A.TRUONGDL IN ('FNHD', 'FHD') AND A.MAUHD IN ('04', '12')
                    ORDER BY A.MAUHD, C.CHIMUC
                    )
            LOOP
                IF V_MDVAY = 'c' AND DL.MAUHD = '04' THEN
                    IF DL.TRUONGDL = 'FHD' THEN 
                        CT.CHITIEU := REPLACE(CT.CHITIEU, '(1204-FHDTC)', NVL(DL.GIATRI, ' '));
                    ELSE
                        CT.CHITIEU := REPLACE(CT.CHITIEU, '(1204-FNHDTC)', NVL(DL.GIATRI, ' '));
                    END IF;                        
                ELSIF DL.MAUHD = '12' THEN
                    IF DL.TRUONGDL = 'FHD' THEN 
                        CT.CHITIEU := REPLACE(CT.CHITIEU, '(1204-FHDTC)', NVL(DL.GIATRI, ' '));
                    ELSE
                        CT.CHITIEU := REPLACE(CT.CHITIEU, '(1204-FNHDTC)', NVL(DL.GIATRI, ' '));
                    END IF;        
                END IF;
            END LOOP;
        END IF;
        
        IF (CT.CHOPHEPIN IS NULL OR CT.CHOPHEPIN = 'Y') THEN
            CT.CHITIEU := REPLACE(REPLACE(CT.CHITIEU, 'M2', 'm<sup>2</sup>'), 'm2', 'm<sup>2</sup>');
            
            INSERT INTO DN_NOXH_TEMP (MAUBIEU, STT, CHITIEU, TTHTML, VITRI)
            VALUES (CT.MAUBIEU, CT.STT, CT.CHITIEU,  CT.TTHTML, CT.VITRI);
        END IF;        
    END LOOP;
    
    EXCEPTION WHEN OTHERS THEN
        P_THONGBAO := SQLERRM();
END IN_MAUBIEU;

PROCEDURE LAY_THONGTIN_GQVL
(
    P_CMTVAY              VARCHAR2,
    P_THONGBAO      OUT     VARCHAR2,
    P_TRAVE             OUT     SYS_REFCURSOR
)
AS
    V_CHECK     INT;
 BEGIN
    
    OPEN P_TRAVE FOR
    SELECT A.CHIMUC, A.CHITIEU, A.COCMCHA, A.GHICHU, A.TTNHAP, A.COTEXTAREA, A.TRUONGDL, 
                A.THAYDL, A.MAUHD, NVL(D.SOHD, 0) SOHD, D.KHHD, NVL(D.GIATRI, ' ') GIATRI
    FROM DN_NOXH_DM A 
    LEFT JOIN 
    (
    SELECT DISTINCT B.KHHD, B.SOHD, C.CHIMUC, C.GIATRI
    FROM DN_NOXH B INNER JOIN DN_NOXH_CT C ON B.KHHD = C.KHHD AND B.SOHD = C.SOHD 
    WHERE TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT)
    ) D ON A.CHIMUC = D.CHIMUC            
    WHERE A.MDVAY = '07b/GQVL'
    ORDER BY A.CHIMUC;
    
    EXCEPTION WHEN OTHERS THEN
        P_THONGBAO := SQLERRM();
          
END LAY_THONGTIN_GQVL;

PROCEDURE IN_MAUBIEU_GQVL_CT
(
    P_CMTVAY            VARCHAR2,
    P_MDVAY             VARCHAR2,
    P_NGAYGDX           VARCHAR2,
    P_THONGBAO      OUT     VARCHAR2
)
AS
    V_KTSL              INT;
    V_SOKY              INT;
    V_THOIHAN           INT;
    V_KYHAN             INT;
    V_TIENKY            NUMBER(15, 0);
    V_TONGTIEN      NUMBER(15, 0);
    V_NGAYKY        DATE;
    V_SOTIEN        NUMBER(15, 0);
    V_NGAYGN        DATE;
    V_NGAYDH        DATE;
    V_THONGTINKYHAN         VARCHAR2(3000);
    V_LSUAT         NUMBER(8, 5);
    V_TUOI          INT;
    V_NGAYGDX           DATE;
    V_SOHD          VARCHAR2(100);
    V_SOKY_1              INT;
BEGIN
   
    SELECT DISTINCT EXTRACT(YEAR FROM SYSDATE) - EXTRACT(YEAR FROM TO_DATE(C.GIATRI, 'DD/MM/YYYY')) INTO V_TUOI
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL = 'C02' AND
                B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                
    SELECT DISTINCT TO_NUMBER(REPLACE(REPLACE(NVL(C.GIATRI, '0'), '.', ''), ',', '')) INTO V_SOTIEN
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL = 'D01' AND
                B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                
    SELECT DISTINCT TO_NUMBER(NVL(C.GIATRI, '0')) INTO V_THOIHAN
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL = 'D03' AND
                B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                
    SELECT DISTINCT TO_NUMBER(NVL(C.GIATRI, '0')) INTO V_KYHAN
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL = 'D06' AND
                B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                
    SELECT DISTINCT TO_DATE(C.GIATRI, 'DD/MM/YYYY') INTO V_NGAYGN
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL = 'D04' AND
                B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
    
    IF P_NGAYGDX IS NOT NULL THEN
        V_NGAYGDX := TO_DATE(P_NGAYGDX || TO_CHAR(V_NGAYGN, '/MM/YYYY'), 'DD/MM/YYYY');
    ELSE
        V_NGAYGDX := V_NGAYGN;
    END IF;        
    V_NGAYDH := ADD_MONTHS(V_NGAYGN, V_THOIHAN);
                               
    SELECT DISTINCT TO_NUMBER(C.GIATRI, '99.99999') INTO V_LSUAT
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL = 'D07' AND
                B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);       
                                       
    FOR CT IN (
                    SELECT STT, CHITIEU, MAUBIEU, TTHTML, VITRI, CHOPHEPIN
                    FROM DN_NOXH_MAUBIEU 
                    WHERE MAUBIEU = 'GQVL' AND MDVAY = '07b/GQVL-2'
                    ORDER BY STT
                    )
        -- MDVAY = '07b/GQVL-2' -- LẤY MẪU 2026 THEO VB 11055 == '07b/GQVL': mẫu cũ
    LOOP
        IF INSTR(CT.CHITIEU, '(~') > 0 THEN             
            -- KIEM TRA CO IN THOA THUAN KHAC
            IF INSTR(CT.CHITIEU, '(~E00~)') > 0 THEN
                SELECT COUNT(1) INTO V_KTSL
                FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                            TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND 
                            A.TRUONGDL = 'E00' AND LENGTH(TRIM(C.GIATRI)) > 0 AND
                            B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                IF V_KTSL = 0 THEN
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~E00~)', 'Không có thỏa thuận khác.');
                    --CONTINUE;
                END IF;                            
            END IF; 
         -- KIEM TRA TUOI QUA 60
            IF INSTR (CT.CHITIEU, '(~C061~)') > 0 THEN
                IF V_TUOI > 60 THEN CT.CHITIEU := REPLACE(CT.CHITIEU , '(~C061~)', 'Không thời hạn.');
                END IF;
            END IF;
        ------------- Kiem TRA HINH THUC GIAI NGAN---------------------------
            IF INSTR(CT.CHITIEU, '(~F00~)') > 0 THEN
                SELECT COUNT(1) INTO V_KTSL
                FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                            TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND 
                            A.TRUONGDL IN ('F01', 'F02') AND LENGTH(TRIM(C.GIATRI)) > 0 AND
                            B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                IF V_KTSL = 0 THEN
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~F00~)', 'Tiền mặt. [X] ');
                ELSE  CT.CHITIEU := REPLACE(CT.CHITIEU, '(~F00~)', 'Tiền mặt. []');
                END IF;
            END IF;
            
            IF INSTR(CT.CHITIEU, '(~F001~)') > 0 THEN
                SELECT COUNT(1) INTO V_KTSL
                FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                            TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND 
                            A.TRUONGDL IN ('F01', 'F02') AND LENGTH(TRIM(C.GIATRI)) > 0 AND
                            B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                IF V_KTSL = 0 THEN
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~F001~)',  LPAD(' ', 40, ' ') || 'Chuyển khoản: []' || chr(10));
                ELSE CT.CHITIEU := REPLACE(CT.CHITIEU, '(~F001~)',  LPAD(' ', 40, ' ') || 'Chuyển khoản: [X]' || chr(10));
                END IF;
            END IF;
            
            IF INSTR(CT.CHITIEU, '(~F01~)') > 0 THEN
                SELECT COUNT(1) INTO V_KTSL
                FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                            TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND 
                            A.TRUONGDL IN ('F01') AND LENGTH(TRIM(C.GIATRI)) > 0 AND
                            B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                IF V_KTSL = 0 THEN CT.CHITIEU := REPLACE(CT.CHITIEU, '(~F01~)', '.............................................................');
                END IF;
            END IF;
            iF INSTR(CT.CHITIEU, '(~F02~)') > 0 THEN
                SELECT COUNT(1) INTO V_KTSL
                FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                            TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND 
                            A.TRUONGDL IN ('F02') AND LENGTH(TRIM(C.GIATRI)) > 0 AND
                            B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                IF V_KTSL = 0 THEN CT.CHITIEU := REPLACE(CT.CHITIEU, '(~F02~)', '......................tại..........................................');
                END IF;
            END IF;
        ------------------------------------------       
        -------- LẤY SỐ HỢP ĐỒNG -----------------
            IF INSTR(CT.CHITIEU, '(~Z~)') > 0 THEN
                SELECT COUNT(1), REPLACE(REPLACE(C.GIATRI, '-GQVL', ''), '/HĐ-TD', '') INTO V_KTSL, V_SOHD
                FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                            TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND 
                            A.TRUONGDL IN ('Z') AND LENGTH(TRIM(C.GIATRI)) > 0 AND
                            B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT)
                            GROUP BY C.GIATRI;
                IF V_KTSL = 1 THEN
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~Z~)', V_SOHD || '/HĐ-TD');
                END IF;
            END IF;                
        ------------------------------------------
        --- giay quy quyen ------------------------
            IF INSTR(CT.CHITIEU, '(~B06~)') > 0 THEN
                SELECT COUNT(1) INTO V_KTSL
                FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                            TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND 
                            A.TRUONGDL IN ('B06') AND LENGTH(TRIM(C.GIATRI)) > 0 AND
                            B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                IF V_KTSL = 0 THEN
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~B06~)', '..................');
                END IF;
            END IF;
        ------------------------------------------
            FOR DL IN (
                            SELECT DISTINCT A.MAUHD, C.CHIMUC, A.TRUONGDL, C.GIATRI, A.COBGCHU, A.COSOTPHAN, A.THAYDL
                            FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                            WHERE A.MAUHD = 'GQVL' AND A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                                        TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL IS NOT NULL AND
                                        B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT) 
                            ORDER BY A.MAUHD, C.CHIMUC
                            )
            LOOP
                
                IF DL.TRUONGDL = '000' THEN
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~' ||  DL.TRUONGDL || '~)', 
                        'ngày ' || SUBSTR(DL.GIATRI, 1, INSTR(DL.GIATRI, '/') - 1) ||
                        ' tháng ' || SUBSTR(SUBSTR(DL.GIATRI, INSTR(DL.GIATRI, '/') + 1), 1,  INSTR(SUBSTR(DL.GIATRI, INSTR(DL.GIATRI, '/') + 1), '/') - 1) ||
                        ' năm ' || SUBSTR(SUBSTR(DL.GIATRI, INSTR(DL.GIATRI, '/') + 1), INSTR(SUBSTR(DL.GIATRI, INSTR(DL.GIATRI, '/') + 1), '/') + 1));
                ELSIF DL.TRUONGDL = 'D01' THEN
                    DL.GIATRI := REPLACE(REPLACE(DL.GIATRI, '.', ''), ',', '');
                    DL.GIATRI := TRIM(REPLACE(TO_CHAR(TO_NUMBER(DL.GIATRI), '999,999,999'), ',', '.'));
                ELSIF DL.TRUONGDL = 'D05' THEN
                    DL.GIATRI := TO_CHAR(V_NGAYDH, 'DD/MM/YYYY');     
                ELSIF DL.TRUONGDL = 'C03' THEN
                    DL.GIATRI := V_TUOI;
                 ELSIF DL.TRUONGDL = 'D07' THEN
                    DL.GIATRI := REPLACE(V_LSUAT, '.', ',');
                END IF;
                
                IF INSTR(CT.CHITIEU, '(~BC' || DL.TRUONGDL || '~)') > 0 THEN
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~BC' || DL.TRUONGDL || '~)', BANGCHU(V_SOTIEN, NVL(DL.COSOTPHAN, 'N'), '') || ' đồng') ;
                END IF;
                
                IF INSTR(CT.CHITIEU, '(~LSD07~)') > 0 THEN
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~LSD07~)', REPLACE(TO_CHAR(V_LSUAT * 1.3, '99.99999'), '.', ','));
                END IF;
                
                IF INSTR(CT.CHITIEU, '(~KHD06~)') > 0 AND V_THOIHAN > 0 AND V_KYHAN > 0 AND V_SOTIEN > 0 THEN
                    V_SOKY := CEIL(V_THOIHAN / V_KYHAN);
                    V_TIENKY := FLOOR(V_SOTIEN / V_SOKY / 100000) * 100000;
                    V_TONGTIEN := 0;
                   
                    IF V_NGAYGN <> V_NGAYGDX THEN
                        V_NGAYKY := ADD_MONTHS(V_NGAYGDX, V_KYHAN + CASE WHEN V_NGAYGN > V_NGAYGDX THEN 1 ELSE 0 END);
                       --V_NGAYKY := ADD_MONTHS(V_NGAYGDX, V_KYHAN);
                    ELSE 
                        V_NGAYKY := ADD_MONTHS(V_NGAYGN, V_KYHAN);
                    END IF;
                    V_THONGTINKYHAN :='';
                    FOR KY IN 1 .. V_SOKY LOOP
                        IF (KY = V_SOKY AND V_NGAYKY < V_NGAYDH) OR V_NGAYKY > V_NGAYDH OR
                            ((KY = V_SOKY OR V_NGAYKY = V_NGAYDH) AND V_TONGTIEN < V_SOTIEN) THEN
                            V_NGAYKY := V_NGAYDH;
                            V_TIENKY := V_SOTIEN - V_TONGTIEN;
                            V_TONGTIEN := V_TONGTIEN + V_TIENKY;
                            V_THONGTINKYHAN := '- Ngày ' || TO_CHAR(V_NGAYKY, 'DD/MM/YYYY') || 
                                    ', số tiền: ' || TRIM(REPLACE(TO_CHAR(TO_NUMBER(V_TIENKY), '999,999,999'), ',', '.')) || ' đồng.';
                            INSERT INTO DN_NOXH_TEMP (MAUBIEU, STT, CHITIEU, TTHTML, VITRI, MDVAY, CMT)
                            VALUES (CT.MAUBIEU, CT.STT || TRIM(TO_CHAR(KY, '099')), V_THONGTINKYHAN,  CT.TTHTML, CT.VITRI, P_MDVAY, P_CMTVAY);     
                            EXIT;
                        ELSE
                            
                            V_THONGTINKYHAN := '- Ngày ' || TO_CHAR(V_NGAYKY, 'DD/MM/YYYY') || 
                                    ', số tiền: ' || TRIM(REPLACE(TO_CHAR(TO_NUMBER(V_TIENKY), '999,999,999'), ',', '.')) || ' đồng.';
                            INSERT INTO DN_NOXH_TEMP (MAUBIEU, STT, CHITIEU, TTHTML, VITRI, MDVAY, CMT)
                            VALUES (CT.MAUBIEU, CT.STT || TRIM(TO_CHAR(KY, '099')), V_THONGTINKYHAN,  CT.TTHTML, CT.VITRI, P_MDVAY, P_CMTVAY);
                        END IF;
                        V_NGAYKY := ADD_MONTHS(V_NGAYKY, V_KYHAN);
                        V_TONGTIEN := V_TONGTIEN + V_TIENKY;
                        CT.CHITIEU := REPLACE(CT.CHITIEU, '(~KHD06~)', NULL);
                        
                    END LOOP ;
                    
                    CONTINUE;
                END IF;
                
                DL.GIATRI := REPLACE(DL.GIATRI, CHR(10), '<BR>');
                CT.CHITIEU := REPLACE(CT.CHITIEU, '(~' ||  DL.TRUONGDL || '~)', NVL(DL.GIATRI, '  '));
               
            END LOOP;
        END IF;    
            
        IF  CT.TTHTML IS NOT NULL AND LENGTH(TRIM(CT.TTHTML)) > 0 THEN
            CT.CHITIEU := CT.TTHTML || CT.CHITIEU || REPLACE(CT.TTHTML, '<', '</');
        END IF;

        INSERT INTO DN_NOXH_TEMP (MAUBIEU, STT, CHITIEU, TTHTML, VITRI, MDVAY, CMT)
        VALUES (CT.MAUBIEU, CT.STT, CT.CHITIEU,  CT.TTHTML, CT.VITRI, P_MDVAY, P_CMTVAY);
        
        -- THOIHAN = 96 THÁNG, THÊM 4 DÒNG TRỐNG
        -- THOIHAN = 108 THÁNG, THÊM 1 DÒNG TRỐNG
        -- ĐỂ KHI IN ĐẨY DÒNG CUỐI SANG PHẦN KÝ TÊN
        IF CT.STT = '082' AND CT.CHITIEU LIKE '<b>Điều 10. </b>%' THEN
            IF V_THOIHAN = 96 THEN
               -- FOR DONG IN 1..4 LOOP
               --     INSERT INTO DN_NOXH_TEMP (MAUBIEU, STT, CHITIEU, TTHTML, VITRI, MDVAY, CMT)
               --     VALUES (CT.MAUBIEU, CT.STT, ' ',  CT.TTHTML, CT.VITRI, P_MDVAY, P_CMTVAY);
             --   END LOOP;
                continue;
            ELSIF V_THOIHAN = 108 THEN
                INSERT INTO DN_NOXH_TEMP (MAUBIEU, STT, CHITIEU, TTHTML, VITRI, MDVAY, CMT)
                VALUES (CT.MAUBIEU, CT.STT, ' ',  CT.TTHTML, CT.VITRI, P_MDVAY, P_CMTVAY);
            END IF;
        END IF;
        
    END LOOP;
    
    DELETE DN_NOXH_TEMP WHERE CHITIEU IS NULL ;
    
    EXCEPTION WHEN OTHERS THEN
        P_THONGBAO := SQLERRM();
END IN_MAUBIEU_GQVL_CT;

PROCEDURE IN_MAUBIEU_GQVL
(
    P_CMTVAY            VARCHAR2,
    P_MDVAY             VARCHAR2,
    P_THONGBAO      OUT     VARCHAR2
)
AS
    V_KTSL          INT;
   
BEGIN
   
    SELECT COUNT(1) INTO V_KTSL FROM DN_NOXH WHERE TRIM(UPPER(CMT)) = TRIM(UPPER(P_CMTVAY)) AND MDVAY = P_MDVAY;
    IF V_KTSL = 0 THEN
        P_THONGBAO := 'Chua nhập thông tin vay vốn GQVL của người vay có CMT này !!';
        RETURN;
    END IF;
    
    DELETE DN_NOXH_TEMP;
    IN_MAUBIEU_GQVL_CT(P_CMTVAY, P_MDVAY, NULL, P_THONGBAO);
    
    EXCEPTION WHEN OTHERS THEN
        P_THONGBAO := SQLERRM();
END IN_MAUBIEU_GQVL;


PROCEDURE LAY_THONGTIN_GQVL_TOTK
(
    P_MATOTK              VARCHAR2,
    P_NGAYIN                VARCHAR2,
    P_KIEUGN                VARCHAR2,
    P_THONGBAO      OUT     VARCHAR2,
    P_TRAVE             OUT     SYS_REFCURSOR
)
AS
    V_NGAYBC            DATE;
    V_LSMACDINH     VARCHAR2(10) := '7.9248';
BEGIN
    IF P_MATOTK IS NULL OR LENGTH(TRIM(P_MATOTK)) = 0 THEN
        P_THONGBAO := 'CHƯA NHẬP MÃ TỔ. KHÔNG THỰC HIỆN ĐƯỢC !!';
        RETURN;
    END IF;
    IF P_NGAYIN IS NULL OR LENGTH(TRIM(P_NGAYIN)) <> 10 THEN
        P_THONGBAO := 'NGÀY IN HỢP ĐỒNG KHÔNG HỢP LỆ. KHÔNG THỰC HIỆN ĐƯỢC !!'; 
    END IF;


    SELECT MAX(NGAY) INTO V_NGAYBC FROM DB_RECEIVE WHERE LOAI = 'HSTDCTNGAY';
    DELETE DN_DATA_TEMP WHERE C1 = 'HD-GQVL';
    IF V_NGAYBC IS NULL THEN
    P_THONGBAO := 'KHÔNG LẤY ĐƯỢC NGÀY CẮT DỮ LIỆU TỪ DB_RECEIVE';
    RETURN;
    END IF;
    MERGE INTO DN_DATA_TEMP AA
    --MERGE INTO HSTEST8 AA
    USING
    (
        SELECT distinct INITCAP(KH_TENKH) TENKH, TO_CHAR(KH_NGAYSINH, 'DD/MM/YYYY') NGAYSINH, KH_CMT KHOACMT, KH_NOICAP NOICAP, 
                    TO_CHAR(KH_NGAYCAP, 'DD/MM/YYYY') NGAYCAP, REPLACE(KH_MOBILE, '08-4-', '') SDT, TRIM(TO_CHAR(KU_MUCVAY, '999,999,999')) TIENVAY,
                    INITCAP(LOWER(REGEXP_REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(TRIM(KH_DIACHI),'(^[-[:space:]]+)|([-[:space:]]+$)',''),'[-[:space:]]{2,}','-'),'^[^[:alnum:]]+',''))) HOKHAU,
                    (EXTRACT(MONTH FROM KU_NGAYDHAN_1) + EXTRACT(YEAR FROM KU_NGAYDHAN_1) * 12) - 
                    (EXTRACT(MONTH FROM KU_NGAYGNDT) + EXTRACT(YEAR FROM KU_NGAYGNDT) * 12) THOIHAN,
                    TO_CHAR(KU_NGAYGNDT, 'DD/MM/YYYY') NGAYGN, TO_CHAR(KU_NGAYDHAN_1, 'DD/MM/YYYY') NGAYDH, 
                    TRIM(NVL(TO_CHAR(KU_LSUAT, '99.9999'), V_LSMACDINH)) LSUAT, 
                    (SELECT GIATRI  FROM DMKHAC WHERE KHOA_1 = '25' AND KHOA_2 = KU_MAPNKT51) PLMD,
                    (EXTRACT(MONTH FROM KU_NGAY_TGOC) + EXTRACT(YEAR FROM KU_NGAY_TGOC) * 12) - 
                    (EXTRACT(MONTH FROM KU_NGAYGNDT) + EXTRACT(YEAR FROM KU_NGAYGNDT) * 12) KYHAN,
                    TO_CHAR(KH_NGAYHHCMT, 'DD/MM/YYYY') NGAYHHCMT
        FROM HSKH, HSCV_DAILY
        WHERE KH_MAKH = KU_MAKH 
                AND TRUNC(KU_NGAYBC) = TRUNC(V_NGAYBC)
                AND KU_CHTRINH = '03' AND KH_TTRANG = 'A' AND 
                    KU_GNGAN = 0 AND 
                    KU_TTMONVAY <> 'CLOSE' AND KU_MATO = TRIM(P_MATOTK)                
    ) BB
    ON (AA.C1 = 'HD-GQVL' AND AA.C4 = BB.KHOACMT)
    WHEN MATCHED THEN 
        UPDATE SET AA.C2 = BB.TENKH, AA.C3 = BB.NGAYSINH, AA.C5 = BB.NGAYCAP, AA.C6 = BB.NOICAP, AA.C7 = BB.HOKHAU,
                            AA.C8 = BB.SDT, AA.C13 = BB.TIENVAY, AA.C15 = BB.THOIHAN, AA.C16 = BB.NGAYGN, AA.C17 = BB.NGAYDH, 
                            AA.C18 = BB.KYHAN, AA.C19 = BB.LSUAT, AA.C20 = BB.PLMD, AA.C23 = TRIM(P_MATOTK), AA.C27 = BB.NGAYHHCMT
    WHEN NOT MATCHED THEN
        INSERT (C1, C2, C3, C4, C5, C6, C7, C8, C9, C13, C14, C15, C16, C17, C18, C19, C20, C21, C22, C23, C27)
        VALUES ('HD-GQVL', BB.TENKH, BB.NGAYSINH, BB.KHOACMT,  BB.NGAYCAP, BB.NOICAP, BB.HOKHAU, BB.SDT, NULL,
                     BB.TIENVAY, '1', BB.THOIHAN, BB.NGAYGN, BB.NGAYDH, BB.KYHAN, BB.LSUAT, BB.PLMD, NULL, NULL, TRIM(P_MATOTK), BB.NGAYHHCMT);   

    MERGE INTO DN_DATA_TEMP AA
    USING
    ( 
    SELECT  KHHD, SOHD, KHOACMT,
            TO_CHAR(ROW_NUMBER() OVER (PARTITION BY KHOACMT ORDER BY KHHD, SOHD)) RN_KEY,
            MAX(TENKH) TENKH, MAX(NGAYSINH) NGAYSINH, MAX(CMT) CMT, MAX(NGAYCAP) NGAYCAP, 
            MAX(NOICAP) NOICAP, MAX(HOKHAU) HOKHAU, MAX(SDT) SDT,
            MAX(TIENVAY) TIENVAY, MAX(SOLD) SOLD, MAX(THOIHAN) THOIHAN, MAX(NGAYGN) NGAYGN, 
            MAX(NGAYDH) NGAYDH, MAX(KYHAN) KYHAN, MAX(LSUAT) LSUAT, MAX(PLMD) PLMD, MAX(GHICHU) GHICHU, MAX(TENTH) TENTH, MAX(THTTK) THTTK,
            MAX(NGAYHH) NGAYHH, MAX(NOITH) NOITH
    FROM
    (
        SELECT KHHD, SOHD, KHOACMT,
                    DECODE(A.TRUONGDL, 'C01', D.GIATRI) TENKH,
                    DECODE(A.TRUONGDL, 'C02', D.GIATRI) NGAYSINH,
                    DECODE(A.TRUONGDL, 'C04', D.GIATRI) CMT,
                    DECODE(A.TRUONGDL, 'C05', D.GIATRI) NGAYCAP,
                    DECODE(A.TRUONGDL, 'C06', D.GIATRI) NOICAP,
                    DECODE(A.TRUONGDL, 'C07', D.GIATRI) HOKHAU,
                    DECODE(A.TRUONGDL, 'C08', REPLACE(D.GIATRI, '08-4-', '0')) SDT,
                    DECODE(A.TRUONGDL, 'D01', D.GIATRI) TIENVAY,
                    DECODE(A.TRUONGDL, 'D02', D.GIATRI) SOLD,
                    DECODE(A.TRUONGDL, 'D03', D.GIATRI) THOIHAN,
                    DECODE(A.TRUONGDL, 'D04', D.GIATRI) NGAYGN,
                    DECODE(A.TRUONGDL, 'D05', D.GIATRI) NGAYDH,
                    DECODE(A.TRUONGDL, 'D06', D.GIATRI) KYHAN,
                    DECODE(A.TRUONGDL, 'D07', D.GIATRI) LSUAT,
                    DECODE(A.TRUONGDL, 'D08', D.GIATRI) PLMD,
                    DECODE(A.TRUONGDL, 'E00', D.GIATRI) GHICHU,
                    DECODE(A.TRUONGDL, 'F01', D.GIATRI) TENTH,
                    DECODE(A.TRUONGDL, 'F02', D.GIATRI) THTTK,
                    DECODE(A.TRUONGDL, 'C061', D.GIATRI) NGAYHH,
                    DECODE(A.TRUONGDL, 'D09', D.GIATRI) NOITH
        FROM DN_NOXH_DM A 
        LEFT JOIN 
        (
        SELECT B.KHHD, B.SOHD, C.CHIMUC, C.GIATRI, B.CMT KHOACMT
        FROM DN_NOXH B INNER JOIN DN_NOXH_CT C ON B.KHHD = C.KHHD AND B.SOHD = C.SOHD 
        WHERE TRIM(B.MATO) = TRIM(P_MATOTK) AND B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT)
        ) D ON A.CHIMUC = D.CHIMUC            
        WHERE A.MDVAY = '07b/GQVL'
    )
    WHERE KHHD IS NOT NULL
    GROUP BY  KHHD, SOHD, KHOACMT 
    ) BB
    ON (AA.C1 = 'HD-GQVL' AND AA.C4 = BB.KHOACMT AND NVL(AA.C9, '1') = BB.RN_KEY)
    WHEN MATCHED THEN 
        UPDATE SET AA.C2 = BB.TENKH, AA.C3 = BB.NGAYSINH, AA.C5 = BB.NGAYCAP, AA.C6 = BB.NOICAP, AA.C7 = BB.HOKHAU,
                            AA.C8 = BB.SDT, AA.C13 = BB.TIENVAY, AA.C14 = BB.SOLD, AA.C15 = BB.THOIHAN, 
                            AA.C16 = BB.NGAYGN, AA.C17 = BB.NGAYDH, AA.C18 = BB.KYHAN, AA.C19 = BB.LSUAT, AA.C20 = BB.PLMD, 
                            AA.C21 = BB.GHICHU, AA.C22 = BB.SOHD, AA.C23 = TRIM(P_MATOTK), AA.C24 = BB.KHHD, AA.C25 = BB.TENTH, AA.C26 = BB.THTTK,
                            AA.C27 = BB.NGAYHH, AA.C28 = BB.NOITH
    WHEN NOT MATCHED THEN
        INSERT (C1, C2, C3, C4, C5, C6, C7, C8, C9, C13, C14, C15, C16, C17, C18, C19, C20, C21, C22, C23, C24, C25, C26, C27, C28)
        VALUES ('HD-GQVL', BB.TENKH, BB.NGAYSINH, BB.KHOACMT,  BB.NGAYCAP, BB.NOICAP, BB.HOKHAU, BB.SDT, BB.RN_KEY,
                 BB.TIENVAY, BB.SOLD, BB.THOIHAN, BB.NGAYGN, BB.NGAYDH,  BB.KYHAN, BB.LSUAT, BB.PLMD, BB.GHICHU, BB.SOHD, TRIM(P_MATOTK), BB.KHHD,
                 BB.TENTH, BB.THTTK, BB.NGAYHH, BB.NOITH);

     OPEN P_TRAVE FOR 
    WITH CURRENT_YEAR_M2 AS (
        SELECT DISTINCT C4 AS CMT
        FROM DN_DATA_TEMP
        WHERE C1 = 'HD-GQVL'
          AND C23 = TRIM(P_MATOTK)
          AND C12 IS NOT NULL
          AND SUBSTR(C24, 1, 4) = TO_CHAR(TO_DATE(V_NGAYBC, 'DD/MM/YYYY'), 'YYYY')
    )
    SELECT C2 TENKH, C3 NGAYSINH, C4 CMT, C5 NGAYCAP, C6 NOICAP, C7 HOKHAU,
           C8 SDT, C13 TIENVAY, C14 SOLD, C15 THOIHAN, C16 NGAYGN, C17 NGAYDH, 
           C18 KYHAN, C19 LSUAT, C20 PLMD, C21 GHICHU, C22 SOHD, C24 KHHD, C25 TENTH, C26 THTTK, C27 NGAYHH, C28 NOITH
    FROM (
        SELECT AA.*
        FROM DN_DATA_TEMP AA
        WHERE AA.C1 = 'HD-GQVL'
          AND AA.C23 = TRIM(P_MATOTK)
          AND AA.C12 IS NOT NULL
          AND SUBSTR(AA.C24, 1, 4) = TO_CHAR(TO_DATE(V_NGAYBC, 'DD/MM/YYYY'), 'YYYY')
          AND CASE 
              WHEN P_KIEUGN = '0' AND TO_DATE(AA.C16, 'DD/MM/YYYY') >= TO_DATE(P_NGAYIN, 'DD/MM/YYYY') THEN 0
              WHEN P_KIEUGN = '1' THEN 1
          END = CASE WHEN P_KIEUGN = '0' THEN 0 ELSE 1 END

        UNION ALL

        SELECT AA.*
        FROM DN_DATA_TEMP AA
        WHERE AA.C1 = 'HD-GQVL'
          AND AA.C23 = TRIM(P_MATOTK)
          AND AA.C12 IS NULL
          AND NOT EXISTS (
              SELECT 1
              FROM CURRENT_YEAR_M2 M
              WHERE M.CMT = AA.C4
          )
          AND CASE 
              WHEN P_KIEUGN = '0' AND TO_DATE(AA.C16, 'DD/MM/YYYY') >= TO_DATE(P_NGAYIN, 'DD/MM/YYYY') THEN 0
              WHEN P_KIEUGN = '1' THEN 1
          END = CASE WHEN P_KIEUGN = '0' THEN 0 ELSE 1 END
    )
    ORDER BY CMT, KHHD, SOHD;
END LAY_THONGTIN_GQVL_TOTK;


PROCEDURE IN_MAUBIEU_GQVL_TOTK_N
(
    P_MATOTK            VARCHAR2,
    P_MDVAY             VARCHAR2,
    P_DSCMT             VARCHAR2,
    P_THONGBAO      OUT     VARCHAR2
)
AS
    V_KTSL              INT;
    V_NGAYGDX       VARCHAR(2);
BEGIN
    SELECT COUNT(1) INTO V_KTSL FROM DN_NOXH WHERE TRIM(UPPER(MATO)) = TRIM(UPPER(P_MATOTK)) AND MDVAY = P_MDVAY;
    IF V_KTSL = 0 THEN
        P_THONGBAO := 'Chua nhập thông tin vay vốn GQVL của TỔ TKVV này !!';
        RETURN;
    END IF;
    
    IF P_MATOTK = '00000' THEN -- VAY TRUC TIEP IN THEO TO
        V_NGAYGDX := TO_CHAR(SYSDATE, 'DD');
    ELSE -- NGUYÊN NHÂN LOI NO DATA FOUND 
        WITH HSTO_ALL AS (
            SELECT SUBSTR(TO_MADP, 5, 2) MA_XA,
                   SUBSTR(TO_MADP, 7, 2) MA_THON
            FROM (
                SELECT TO_MADP
                FROM HSTO
                WHERE TO_MATO = TRIM(UPPER(P_MATOTK))
                  AND NG_CAPNHAT = (SELECT MAX(NG_CAPNHAT) FROM HSTO)
                UNION ALL
                SELECT TO_MADP
                FROM TMP_HSTO T
                WHERE TO_MATO = TRIM(UPPER(P_MATOTK))
                  AND NG_CAPNHAT = (SELECT MAX(NG_CAPNHAT) FROM TMP_HSTO)
                  AND NOT EXISTS (
                        SELECT 1 FROM HSTO H
                        WHERE H.TO_MATO = T.TO_MATO
                    )
                )
            )
            SELECT DISTINCT D.NGAY_GDX INTO V_NGAYGDX
            FROM TRANSPOINT_SUBCOMMUNE_MAPPING D
            JOIN HSTO_ALL H
              ON D.XA = H.MA_XA
             AND D.THON = H.MA_THON
            WHERE D.NGAY_CAPNHAT = (SELECT MAX(NGAY_CAPNHAT) FROM TRANSPOINT_SUBCOMMUNE_MAPPING);
    END IF;
     
    DELETE DN_NOXH_TEMP;
    FOR KH IN (SELECT CMT FROM DN_NOXH B WHERE TRIM(UPPER(MATO)) = TRIM(UPPER(P_MATOTK)) AND MDVAY = P_MDVAY AND
                        (LENGTH(TRIM(P_DSCMT)) = 1 OR ( LENGTH(TRIM(P_DSCMT)) > 1 AND INSTR(P_DSCMT, CMT || ';') > 0)) AND
                        NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT)
                        ORDER BY SOHD)
    LOOP
        IN_MAUBIEU_GQVL_CT(KH.CMT, P_MDVAY, V_NGAYGDX, P_THONGBAO);
        IF P_THONGBAO IS NOT NULL THEN
            DELETE DN_NOXH_TEMP;
            RETURN;
        END IF;
    END LOOP;

    EXCEPTION WHEN OTHERS THEN
        P_THONGBAO := SQLERRM();
END IN_MAUBIEU_GQVL_TOTK_N;

PROCEDURE LAY_THONGTIN_NQ33
(
    P_LOAIVAY               VARCHAR2,
    P_MANHOM                VARCHAR2,
    P_NGAYIN                VARCHAR2,
    P_KIEUGN                VARCHAR2,
    P_THONGBAO      OUT     VARCHAR2,
    P_TRAVE         OUT     SYS_REFCURSOR
)
AS
    V_NGAYBC            DATE;
    V_LSMACDINH     VARCHAR2(10) := '6.24';
BEGIN
    IF P_MANHOM IS NULL OR LENGTH(TRIM(P_MANHOM)) = 0 THEN
        P_THONGBAO := 'CHUA NHẬP MÃ NHÓM DỰ ÁN. KHÔNG THỂ THỰC HIỆN !!';
        RETURN;
    END IF;
    IF P_NGAYIN IS NULL OR LENGTH(TRIM(P_NGAYIN)) <> 10 THEN
        P_THONGBAO := 'NGÀY IN HỢP ĐỒNG KHÔNG HỢP LỆ. KHÔNG THỂ THỰC HIỆN !!';
        RETURN;
    END IF;
    
    SELECT MAX(NGAY) INTO V_NGAYBC FROM DB_RECEIVE WHERE LOAI = 'HSTDCTNGAY';
    DELETE DN_DATA_TEMP WHERE C1 = 'HD-NQ33';

    MERGE INTO DN_DATA_TEMP AA
    USING
    (
        SELECT DISTINCT INITCAP(KH_TENKH) TENKH, 
            TO_CHAR(KH_NGAYSINH, 'DD/MM/YYYY') NGAYSINH, 
            KH_CMT KHOACMT, KH_NOICAP NOICAP, 
            TO_CHAR(KH_NGAYCAP, 'DD/MM/YYYY') NGAYCAP, REPLACE(KH_MOBILE, '08-4-', '') SDT, TRIM(TO_CHAR(KU_MUCVAY, '999,999,999')) TIENVAY,
            INITCAP(LOWER(
            CASE WHEN SUBSTR(KH_DIACHI, 1) = '-' THEN SUBSTR(KH_DIACHI, 2)
                     WHEN SUBSTR(KH_DIACHI, 2) = '--' THEN SUBSTR(KH_DIACHI, 3)
            ELSE KH_DIACHI END)) HOKHAU,
            (EXTRACT(MONTH FROM KU_NGAYDHAN_1) + EXTRACT(YEAR FROM KU_NGAYDHAN_1) * 12) - 
            (EXTRACT(MONTH FROM KU_NGAYGNDT) + EXTRACT(YEAR FROM KU_NGAYGNDT) * 12) THOIHAN,
            TO_CHAR(KU_NGAYGNDT, 'DD/MM/YYYY') NGAYGN, TO_CHAR(KU_NGAYDHAN_1, 'DD/MM/YYYY') NGAYDH, 
            TRIM(NVL(TO_CHAR(KU_LSUAT, '99.9999'), V_LSMACDINH)) LSUAT, 
            (SELECT GIATRI  FROM DMKHAC WHERE KHOA_1 = '25' AND KHOA_2 = KU_MAPNKT51) PLMD,
            (EXTRACT(MONTH FROM KU_NGAY_TGOC) + EXTRACT(YEAR FROM KU_NGAY_TGOC) * 12) - 
            (EXTRACT(MONTH FROM KU_NGAYGNDT) + EXTRACT(YEAR FROM KU_NGAYGNDT) * 12) KYHAN,
            TO_CHAR(KH_NGAYHHCMT, 'DD/MM/YYYY') NGAYHHCMT
        FROM HSKH, HSCV_DAILY
        WHERE KH_MAKH = KU_MAKH AND KU_NGAYBC = V_NGAYBC AND KH_TTRANG = 'A' AND
            -- NQ 33: KU_MANDT = DECODE(P_LOAIVAY, '1', 'INV2110240077907', '2', 'INV2110240077906', '1321700000000205') AND
            -- NQ 30:
            (
                (P_LOAIVAY = '1' AND KU_MANDT IN ('INV0502260090866','INV0502260090869'))
                OR
                (P_LOAIVAY = '2' AND KU_MANDT = 'INV0502260090856')
            ) AND
            KU_GNGAN = 0 AND 
            KU_TTMONVAY <> 'CLOSE' AND KU_MATO = TRIM(P_MANHOM)                
                    
    ) BB
    ON (AA.C1 = 'HD-NQ33' AND AA.C4 = BB.KHOACMT)
    WHEN MATCHED THEN 
        UPDATE SET AA.C2 = BB.TENKH, AA.C3 = BB.NGAYSINH, AA.C5 = BB.NGAYCAP, AA.C6 = BB.NOICAP, AA.C7 = BB.HOKHAU,
                            AA.C8 = BB.SDT, AA.C13 = BB.TIENVAY, AA.C15 = BB.THOIHAN, AA.C16 = BB.NGAYGN, AA.C17 = BB.NGAYDH, 
                            AA.C18 = BB.KYHAN, AA.C19 = BB.LSUAT, AA.C20 = BB.PLMD, AA.C23 = TRIM(P_MANHOM), AA.C27 = BB.NGAYHHCMT
    WHEN NOT MATCHED THEN
        INSERT (C1, C2, C3, C4, C5, C6, C7, C8, C9, C13, C14, C15, C16, C17, C18, C19, C20, C21, C22, C23,C27)
        VALUES ('HD-NQ33', BB.TENKH, BB.NGAYSINH, BB.KHOACMT,  BB.NGAYCAP, BB.NOICAP, BB.HOKHAU, BB.SDT, NULL,
                     BB.TIENVAY, '1', BB.THOIHAN, BB.NGAYGN, BB.NGAYDH, BB.KYHAN, BB.LSUAT, BB.PLMD, NULL, NULL, TRIM(P_MANHOM), BB.NGAYHHCMT);   
    
    MERGE INTO DN_DATA_TEMP AA
    USING
    ( 
    SELECT  KHHD, SOHD, KHOACMT, 
            TO_CHAR(ROW_NUMBER() OVER (PARTITION BY KHOACMT ORDER BY KHHD, SOHD)) RN_KEY,
            MAX(TENKH) TENKH, MAX(NGAYSINH) NGAYSINH, MAX(CMT) CMT, MAX(NGAYCAP) NGAYCAP, 
            MAX(NOICAP) NOICAP, MAX(HOKHAU) HOKHAU, MAX(SDT) SDT, MAX(QDSO) QDSO, 
            MAX(TIENVAY) TIENVAY, MAX(SOLD) SOLD, MAX(THOIHAN) THOIHAN, MAX(NGAYGN) NGAYGN, 
            MAX(NGAYDH) NGAYDH, MAX(KYHAN) KYHAN, MAX(LSUAT) LSUAT, MAX(PLMD) PLMD, MAX(GHICHU) GHICHU,
            MAX(NGAYHH) NGAYHH
    FROM
    (
        SELECT KHHD, SOHD, KHOACMT,
                    DECODE(A.TRUONGDL, 'C01', D.GIATRI) TENKH,
                    DECODE(A.TRUONGDL, 'C02', D.GIATRI) NGAYSINH,
                    DECODE(A.TRUONGDL, 'C04', D.GIATRI) CMT,
                    DECODE(A.TRUONGDL, 'C05', D.GIATRI) NGAYCAP,
                    DECODE(A.TRUONGDL, 'C06', D.GIATRI) NOICAP,
                    DECODE(A.TRUONGDL, 'C07', D.GIATRI) HOKHAU,
                    DECODE(A.TRUONGDL, 'C08', REPLACE(D.GIATRI, '08-4-', '0')) SDT,
                    DECODE(A.TRUONGDL, 'A01', D.GIATRI) QDSO,
                    DECODE(A.TRUONGDL, 'D01', D.GIATRI) TIENVAY,
                    DECODE(A.TRUONGDL, 'D02', D.GIATRI) SOLD,
                    DECODE(A.TRUONGDL, 'D03', D.GIATRI) THOIHAN,
                    DECODE(A.TRUONGDL, 'D04', D.GIATRI) NGAYGN,
                    DECODE(A.TRUONGDL, 'D05', D.GIATRI) NGAYDH,
                    DECODE(A.TRUONGDL, 'D06', D.GIATRI) KYHAN,
                    DECODE(A.TRUONGDL, 'D07', D.GIATRI) LSUAT,
                    DECODE(A.TRUONGDL, 'D08', D.GIATRI) PLMD,
                    DECODE(A.TRUONGDL, 'E00', D.GIATRI) GHICHU,
                    DECODE(A.TRUONGDL, 'C061', D.GIATRI) NGAYHH
        FROM DN_NOXH_DM A 
        LEFT JOIN 
        (
        SELECT B.KHHD, B.SOHD, C.CHIMUC, C.GIATRI, B.CMT KHOACMT
        FROM DN_NOXH B INNER JOIN DN_NOXH_CT C ON B.KHHD = C.KHHD AND B.SOHD = C.SOHD 
        WHERE TRIM(B.MATO) = TRIM(P_MANHOM) AND B.MDVAY = DECODE(P_LOAIVAY, '1', 'X/DN', 'TP')  
            AND B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT)
        ) D ON A.CHIMUC = D.CHIMUC            
        WHERE A.MDVAY = DECODE(P_LOAIVAY, '1', '02A/HĐTD', '02/NH')
    )
    WHERE KHHD IS NOT NULL
    GROUP BY  KHHD, SOHD, KHOACMT 
    ) BB
    ON (AA.C1 = 'HD-NQ33' AND AA.C4 = BB.KHOACMT AND NVL(AA.C25, '1') = BB.RN_KEY)
    WHEN MATCHED THEN 
    UPDATE SET AA.C2 = BB.TENKH, AA.C3 = BB.NGAYSINH, AA.C5 = BB.NGAYCAP, AA.C6 = BB.NOICAP, AA.C7 = BB.HOKHAU,
                AA.C8 = BB.SDT, AA.C9 = BB.QDSO, AA.C13 = BB.TIENVAY, AA.C14 = BB.SOLD, AA.C15 = BB.THOIHAN, 
                AA.C16 = BB.NGAYGN, AA.C17 = BB.NGAYDH, AA.C18 = BB.KYHAN, AA.C19 = BB.LSUAT, AA.C20 = BB.PLMD, 
                AA.C21 = BB.GHICHU, AA.C22 = BB.SOHD, AA.C23 = TRIM(P_MANHOM), AA.C24 = BB.KHHD, AA.C27 = BB.NGAYHH
    WHEN NOT MATCHED THEN
    INSERT (C1, C2, C3, C4, C5, C6, C7, C8, C9, C13, C14, C15, C16, C17, C18, C19, C20, C21, C22, C23, C24, C25, C27)
    VALUES ('HD-NQ33', BB.TENKH, BB.NGAYSINH, BB.KHOACMT,  BB.NGAYCAP, BB.NOICAP, BB.HOKHAU, BB.SDT, BB.QDSO,
             BB.TIENVAY, BB.SOLD, BB.THOIHAN, BB.NGAYGN, BB.NGAYDH,  BB.KYHAN, BB.LSUAT, BB.PLMD, BB.GHICHU, BB.SOHD, TRIM(P_MANHOM), BB.KHHD, BB.RN_KEY, BB.NGAYHH); 
                 
    OPEN P_TRAVE FOR 
    SELECT C2 TENKH, C3 NGAYSINH, C4 CMT, C5 NGAYCAP, C6 NOICAP, C7 HOKHAU,
                C8 SDT, C9 QDSO, C13 TIENVAY, C14 SOLD, C15 THOIHAN, C16 NGAYGN, C17 NGAYDH, 
                C18 KYHAN, C19 LSUAT, C20 PLMD, C21 GHICHU, C22 SOHD, C24 KHHD, C27 NGAYHH
    FROM DN_DATA_TEMP
    WHERE C1 = 'HD-NQ33' AND C23 = TRIM(P_MANHOM) AND
        CASE 
        WHEN P_KIEUGN = '0' AND TO_DATE(C16, 'DD/MM/YYYY') >= TO_DATE(P_NGAYIN, 'DD/MM/YYYY') THEN 0
        WHEN P_KIEUGN = '1' THEN 1 END = CASE WHEN P_KIEUGN = '0' THEN 0 ELSE 1 
        END             
    ORDER BY CMT;
END LAY_THONGTIN_NQ33;

PROCEDURE IN_MAUBIEU_NQ33_CT
(
    P_LOAIVAY           VARCHAR2,
    P_CMTVAY            VARCHAR2,
    P_MDVAY             VARCHAR2,
    P_NGAYGDX           VARCHAR2,
    P_THONGBAO      OUT     VARCHAR2
)
AS
    V_KTSL              INT;
    V_SOKY              INT;
    V_THOIHAN           INT;
    V_KYHAN             INT;
    V_TIENKY            NUMBER(15, 0);
    V_TONGTIEN      NUMBER(15, 0);
    V_NGAYKY        DATE;
    V_SOTIEN        NUMBER(15, 0);
    V_NGAYGN        DATE;
    V_NGAYDH        DATE;
    V_THONGTINKYHAN         VARCHAR2(3000);
    V_LSUAT         NUMBER(8, 5);
    V_TUOI          INT;
    V_NGAYGDX           DATE;
    V_TIEN_KY_DAU       NUMBER(15, 0);
    V_TIEN_KY_CUOI      NUMBER(15, 0);
    
BEGIN
   
    SELECT DISTINCT EXTRACT(YEAR FROM SYSDATE) - EXTRACT(YEAR FROM TO_DATE(C.GIATRI, 'DD/MM/YYYY')) INTO V_TUOI
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.MAUHD = DECODE(P_LOAIVAY, '1', 'CBCCXA', 'CBCCTP') AND 
        A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
        TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL = 'C02' AND
        B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                
    SELECT DISTINCT TO_NUMBER(REPLACE(REPLACE(NVL(C.GIATRI, '0'), '.', ''), ',', '')) INTO V_SOTIEN
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.MAUHD = DECODE(P_LOAIVAY, '1', 'CBCCXA', 'CBCCTP') AND
        A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
            TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL = 'D01' AND
            B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                
    SELECT DISTINCT TO_NUMBER(NVL(C.GIATRI, '0')) INTO V_THOIHAN
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.MAUHD = DECODE(P_LOAIVAY, '1', 'CBCCXA', 'CBCCTP') AND 
        A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
        TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL = 'D03' AND
        B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                
    SELECT DISTINCT TO_NUMBER(NVL(C.GIATRI, '0')) INTO V_KYHAN
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.MAUHD = DECODE(P_LOAIVAY, '1', 'CBCCXA', 'CBCCTP') AND 
        A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
        TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL = 'D06' AND
        B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                
    SELECT DISTINCT TO_DATE(C.GIATRI, 'DD/MM/YYYY') INTO V_NGAYGN
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.MAUHD = DECODE(P_LOAIVAY, '1', 'CBCCXA', 'CBCCTP') AND 
        A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
        TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL = 'D04' AND
        B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
    
    IF P_NGAYGDX IS NOT NULL THEN
        V_NGAYGDX := TO_DATE(P_NGAYGDX || TO_CHAR(V_NGAYGN, '/MM/YYYY'), 'DD/MM/YYYY');
    ELSE
        V_NGAYGDX := V_NGAYGN;
    END IF;        
    V_NGAYDH := ADD_MONTHS(V_NGAYGN, V_THOIHAN);
    
    V_SOKY := CEIL(V_THOIHAN / V_KYHAN);
    V_TIENKY := FLOOR(V_SOTIEN / V_SOKY / 100000) * 100000;
      
                               
    SELECT DISTINCT TO_NUMBER(C.GIATRI, '99.99999') INTO V_LSUAT
    FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
    WHERE A.MAUHD = DECODE(P_LOAIVAY, '1', 'CBCCXA', 'CBCCTP') AND 
        A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
        TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL = 'D07' AND
        B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);       
                                       
    FOR CT IN (
            SELECT STT, CHITIEU, MAUBIEU, TTHTML, VITRI, CHOPHEPIN
            FROM DN_NOXH_MAUBIEU 
            WHERE MAUBIEU = DECODE(P_LOAIVAY, '1', 'CBCCXA', 'CBCCTP') 
            ORDER BY STT
            )
    LOOP
        IF INSTR(CT.CHITIEU, '(~') > 0 THEN
            -- KIEM TRA CO IN SO QUYET DINH 
            IF INSTR(CT.CHITIEU, '(~A01~)') > 0 THEN
                SELECT COUNT(1) INTO V_KTSL
                FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                WHERE A.MAUHD = DECODE(P_LOAIVAY, '1', 'CBCCXA', 'CBCCTP') AND 
                    A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                    TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND 
                    A.TRUONGDL = 'A01' AND LENGTH(TRIM(C.GIATRI)) > 0 AND
                    B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                IF V_KTSL = 0 THEN
                    CONTINUE;
                END IF;                            
            END IF;                      
            -- KIEM TRA CO IN THOA THUAN KHAC
            IF INSTR(CT.CHITIEU, '(~E00~)') > 0 THEN
                SELECT COUNT(1) INTO V_KTSL
                FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                WHERE A.MAUHD = DECODE(P_LOAIVAY, '1', 'CBCCXA', 'CBCCTP') AND 
                    A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                    TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND 
                    A.TRUONGDL = 'E00' AND LENGTH(TRIM(C.GIATRI)) > 0 AND
                    B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT);
                IF V_KTSL = 0 THEN
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~E00~)', 'Không có thỏa thuận khác.');
                    --CONTINUE;
                END IF;                            
            END IF; 
            -- KIEM TRA TUOI QUA 60
            IF INSTR (CT.CHITIEU, '(~C061~)') > 0 THEN
                IF V_TUOI > 60 THEN CT.CHITIEU := REPLACE(CT.CHITIEU , '(~C061~)', 'Không thời hạn.');
                END IF;
            END IF;    
                    
            FOR DL IN (
                SELECT DISTINCT A.MAUHD, C.CHIMUC, A.TRUONGDL, C.GIATRI, A.COBGCHU, A.COSOTPHAN, A.THAYDL
                FROM DN_NOXH_DM A, DN_NOXH B, DN_NOXH_CT C
                WHERE A.MAUHD = DECODE(P_LOAIVAY, '1', 'CBCCXA', 'CBCCTP') AND 
                    A.CHIMUC = C.CHIMUC AND B.KHHD = C.KHHD AND B.SOHD = C.SOHD AND
                    TRIM(UPPER(B.CMT)) = TRIM(UPPER(P_CMTVAY)) AND B.MDVAY = P_MDVAY AND A.TRUONGDL IS NOT NULL AND
                    B.NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT) 
                ORDER BY A.MAUHD, C.CHIMUC
                )
            LOOP
                
                IF DL.TRUONGDL = '000' THEN
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~' ||  DL.TRUONGDL || '~)', 
                        'ngày ' || SUBSTR(DL.GIATRI, 1, INSTR(DL.GIATRI, '/') - 1) ||
                        ' tháng ' || SUBSTR(SUBSTR(DL.GIATRI, INSTR(DL.GIATRI, '/') + 1), 1,  INSTR(SUBSTR(DL.GIATRI, INSTR(DL.GIATRI, '/') + 1), '/') - 1) ||
                        ' năm ' || SUBSTR(SUBSTR(DL.GIATRI, INSTR(DL.GIATRI, '/') + 1), INSTR(SUBSTR(DL.GIATRI, INSTR(DL.GIATRI, '/') + 1), '/') + 1));
                ELSIF DL.TRUONGDL = 'D01' THEN
                    DL.GIATRI := REPLACE(REPLACE(DL.GIATRI, '.', ''), ',', '');
                    DL.GIATRI := TRIM(REPLACE(TO_CHAR(TO_NUMBER(DL.GIATRI), '999,999,999'), ',', '.'));
                ELSIF DL.TRUONGDL = 'D05' THEN
                    DL.GIATRI := TO_CHAR(V_NGAYDH, 'DD/MM/YYYY');     
                ELSIF DL.TRUONGDL = 'C03' THEN
                    DL.GIATRI := V_TUOI;
                 ELSIF DL.TRUONGDL = 'D07' THEN
                    DL.GIATRI := REPLACE(V_LSUAT, '.', ',');
                END IF;
                
                IF INSTR(CT.CHITIEU, '(~BC' || DL.TRUONGDL || '~)') > 0 THEN
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~BC' || DL.TRUONGDL || '~)', BANGCHU(V_SOTIEN, NVL(DL.COSOTPHAN, 'N'), '') || ' đồng') ;
                END IF;
                
                IF INSTR(CT.CHITIEU, '(~LSD07~)') > 0 THEN
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~LSD07~)', REPLACE(REPLACE(TO_CHAR(V_LSUAT * 1.3, '99.9999'), '.', ','), '00', ''));
                END IF;
                           
                IF INSTR(CT.CHITIEU, '(~D061~)') > 0 AND V_THOIHAN > 0 AND V_KYHAN > 0 AND V_SOTIEN > 0 THEN
                    V_TIEN_KY_DAU := V_TIENKY;
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~D061~)', TRIM(REPLACE(TO_CHAR(TO_NUMBER(V_TIEN_KY_DAU), '999,999,999'), ',', '.')));
                    CONTINUE;
                END IF;
                
                IF INSTR(CT.CHITIEU, '(~D062~)') > 0 AND V_THOIHAN > 0 AND V_KYHAN > 0 AND V_SOTIEN > 0 THEN
                    V_TIEN_KY_CUOI := V_SOTIEN - (V_TIENKY * (V_SOKY - 1));
                    CT.CHITIEU := REPLACE(CT.CHITIEU, '(~D062~)', TRIM(REPLACE(TO_CHAR(TO_NUMBER(V_TIEN_KY_CUOI), '999,999,999'), ',', '.')));
                    CONTINUE;
                END IF;
                /*
                IF INSTR(CT.CHITIEU, '(~KHD06~)') > 0 AND V_THOIHAN > 0 AND V_KYHAN > 0 AND V_SOTIEN > 0 THEN
                    V_SOKY := FLOOR(V_THOIHAN / V_KYHAN);
                    V_TIENKY := FLOOR(V_SOTIEN / V_SOKY / 100000) * 100000;
                    V_TONGTIEN := 0;
                   
                    IF V_NGAYGN <> V_NGAYGDX THEN
                        V_NGAYKY := ADD_MONTHS(V_NGAYGDX, V_KYHAN);
                    ELSE 
                        V_NGAYKY := ADD_MONTHS(V_NGAYGN, V_KYHAN);
                    END IF;                        
                    V_THONGTINKYHAN :='';
                    FOR KY IN 1 .. V_SOKY LOOP
                        IF V_NGAYKY > V_NGAYDH OR (KY = V_SOKY AND V_NGAYKY < V_NGAYDH) OR 
                            ((KY = V_SOKY OR V_NGAYKY = V_NGAYDH) AND V_TONGTIEN < V_SOTIEN) THEN
                            V_NGAYKY := V_NGAYDH;
                            V_TIENKY := V_SOTIEN - V_TONGTIEN;
                            V_TONGTIEN := V_TONGTIEN + V_TIENKY;
                            V_THONGTINKYHAN := '- Ngày ' || TO_CHAR(V_NGAYKY, 'DD/MM/YYYY') || 
                                    ', số tiền: ' || TRIM(REPLACE(TO_CHAR(TO_NUMBER(V_TIENKY), '999,999,999'), ',', '.')) || 'đ.';
                            INSERT INTO DN_NOXH_TEMP (MAUBIEU, STT, CHITIEU, TTHTML, VITRI, MDVAY, CMT)
                            VALUES (CT.MAUBIEU, CT.STT || TRIM(TO_CHAR(KY, '099')), V_THONGTINKYHAN,  CT.TTHTML, CT.VITRI, P_MDVAY, P_CMTVAY);     
                            EXIT;
                        ELSE
                            
                            V_THONGTINKYHAN := '- Ngày ' || TO_CHAR(V_NGAYKY, 'DD/MM/YYYY') || 
                                    ', số tiền: ' || TRIM(REPLACE(TO_CHAR(TO_NUMBER(V_TIENKY), '999,999,999'), ',', '.')) || 'đ.';
                            INSERT INTO DN_NOXH_TEMP (MAUBIEU, STT, CHITIEU, TTHTML, VITRI, MDVAY, CMT)
                            VALUES (CT.MAUBIEU, CT.STT || TRIM(TO_CHAR(KY, '099')), V_THONGTINKYHAN,  CT.TTHTML, CT.VITRI, P_MDVAY, P_CMTVAY);
                        END IF;
                        V_NGAYKY := ADD_MONTHS(V_NGAYKY, V_KYHAN);
                        V_TONGTIEN := V_TONGTIEN + V_TIENKY;
                        CT.CHITIEU := REPLACE(CT.CHITIEU, '(~KHD06~)', NULL);
                        
                    END LOOP ;
                    
                    CONTINUE;
                END IF;
                */
                DL.GIATRI := REPLACE(DL.GIATRI, CHR(10), '<BR>');
                CT.CHITIEU := REPLACE(CT.CHITIEU, '(~' ||  DL.TRUONGDL || '~)', NVL(DL.GIATRI, '  '));
               
            END LOOP;
        END IF;    
            
        IF  CT.TTHTML IS NOT NULL AND LENGTH(TRIM(CT.TTHTML)) > 0 THEN
            CT.CHITIEU := CT.TTHTML || CT.CHITIEU || REPLACE(CT.TTHTML, '<', '</');
        END IF;

        INSERT INTO DN_NOXH_TEMP (MAUBIEU, STT, CHITIEU, TTHTML, VITRI, MDVAY, CMT)
        VALUES (CT.MAUBIEU, CT.STT, CT.CHITIEU,  CT.TTHTML, CT.VITRI, P_MDVAY, P_CMTVAY);
        
        
        
    END LOOP;
    
    DELETE DN_NOXH_TEMP WHERE CHITIEU IS NULL ;
    
    EXCEPTION WHEN OTHERS THEN
        P_THONGBAO := SQLERRM();
END IN_MAUBIEU_NQ33_CT;

PROCEDURE IN_MAUBIEU_NQ33
(
    P_LOAIVAY           VARCHAR2, 
    P_MANHOM            VARCHAR2,
    P_MDVAY             VARCHAR2,
    P_DSCMT             VARCHAR2,
    P_THONGBAO      OUT     VARCHAR2
)
AS
    V_KTSL              INT;
    V_NGAYGDX       VARCHAR(2);
BEGIN
    SELECT COUNT(1) INTO V_KTSL FROM DN_NOXH 
    WHERE TRIM(UPPER(MATO)) = TRIM(UPPER(P_MANHOM)) AND MDVAY = P_MDVAY;
    IF V_KTSL = 0 THEN
        P_THONGBAO := 'Chua nhập thông tin vay vốn của nhóm dự án này !!';
        RETURN;
    END IF;
    
    WITH HSTO_ALL AS (
            SELECT SUBSTR(TO_MADP, 5, 2) MA_XA,
                   SUBSTR(TO_MADP, 7, 2) MA_THON
            FROM (
                SELECT TO_MADP
                FROM HSTO
                WHERE TO_MATO = TRIM(UPPER(P_MANHOM))
                  AND NG_CAPNHAT = (SELECT MAX(NG_CAPNHAT) FROM HSTO)
                UNION ALL
                SELECT TO_MADP
                FROM TMP_HSTO T
                WHERE TO_MATO = TRIM(UPPER(P_MANHOM))
                  AND NG_CAPNHAT = (SELECT MAX(NG_CAPNHAT) FROM TMP_HSTO)
                  AND NOT EXISTS (
                        SELECT 1 FROM HSTO H
                        WHERE H.TO_MATO = T.TO_MATO
                    )
                )
            )
            SELECT DISTINCT D.NGAY_GDX INTO V_NGAYGDX
            FROM TRANSPOINT_SUBCOMMUNE_MAPPING D
            JOIN HSTO_ALL H
              ON D.XA = H.MA_XA
             AND D.THON = H.MA_THON
            WHERE D.NGAY_CAPNHAT = (SELECT MAX(NGAY_CAPNHAT) FROM TRANSPOINT_SUBCOMMUNE_MAPPING);
     
    DELETE DN_NOXH_TEMP;
    FOR KH IN (SELECT CMT FROM DN_NOXH B WHERE TRIM(UPPER(MATO)) = TRIM(UPPER(P_MANHOM)) AND MDVAY = P_MDVAY AND
            (LENGTH(TRIM(P_DSCMT)) = 1 OR ( LENGTH(TRIM(P_DSCMT)) > 1 AND INSTR(P_DSCMT, CMT || ';') > 0)) AND
            NGAYDK = (SELECT MAX(NGAYDK) FROM DN_NOXH WHERE CMT = B.CMT)
            ORDER BY SOHD)
    LOOP
        IN_MAUBIEU_NQ33_CT(P_LOAIVAY, KH.CMT, P_MDVAY, V_NGAYGDX, P_THONGBAO);
        IF P_THONGBAO IS NOT NULL THEN
            DELETE DN_NOXH_TEMP;
            RETURN;
        END IF;
    END LOOP;
    
    EXCEPTION WHEN OTHERS THEN
        P_THONGBAO := SQLERRM();
END IN_MAUBIEU_NQ33;

END PCK_DN_NOXH;
/