-- OC : adresse du client pour le PDF de l'offre (lue au moment de la generation, pas figee dans l'OC).
-- Une seule adresse : celle par defaut (BPAADDFLG_0) puis la premiere par code, comme la recherche client.
SELECT TOP 1
    A.BPAADDLIG_0 AS LIGNE_1,
    A.BPAADDLIG_1 AS LIGNE_2,
    A.BPAADDLIG_2 AS LIGNE_3,
    A.POSCOD_0    AS CODE_POSTAL,
    A.CTY_0       AS VILLE,
    A.CRYNAM_0    AS PAYS
FROM X3_LCS.BPADDRESS A
WHERE A.BPANUM_0 = :code
ORDER BY A.BPAADDFLG_0 DESC, A.BPAADD_0
