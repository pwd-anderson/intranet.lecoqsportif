-- OC : remise client en % (type REMCLI de SPRICLIST) pour un client donné.
-- Reprend la requête fournie par l'utilisateur, réduite au client demandé (:code) : DCGVAL_0 = remise en %.
-- Vérifié en direct : 520 lignes REMCLI pour 520 clients (une seule par client), toutes valables du
-- 2027-01-01 au 2050-12-31 -> aucun filtre de date (un filtre "valable aujourd'hui" ne renverrait rien).
SELECT TOP 1
    R.DCGVAL_0                           AS REMISE,
    CONVERT(varchar(10), R.PLISTRDAT_0, 23) AS DATE_DEBUT,
    CONVERT(varchar(10), R.PLIENDDAT_0, 23) AS DATE_FIN
FROM X3_LCS.SPRICLIST R
WHERE R.PLI_0 = 'REMCLI'
  AND R.PLICRI1_0 = :code
ORDER BY R.PLISTRDAT_0 DESC
