-- Configuration AG Grid du Backlog Client v2.
-- Colonnes, intitules, ordre et visibilite strictement identiques au Backlog Client X3
-- (backlog_client_x3.sql) : v2 a vocation a le remplacer.
-- Le bloc stock par site n'est charge que si l'option "Inclure le stock" est cochee.

SELECT * FROM intranet_lcs.aggrid_option where grid_name = 'backlog_client_v2_grid';
delete FROM intranet_lcs.aggrid_option where grid_name = 'backlog_client_v2_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES

-- REP1/REP2 en premier (demande utilisateur)
('backlog_client_v2_grid','REP1','REPRESENTANT 1','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),

('backlog_client_v2_grid','REP2','REPRESENTANT 2','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),

-- 1
('backlog_client_v2_grid','SITE','SITE','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL),

('backlog_client_v2_grid','MAINNETWORK','MAINNETWORK','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL),

('backlog_client_v2_grid','ZCLASSE_0','TYPE CMD.','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL),

-- 2
('backlog_client_v2_grid','COLLECTION','COLLECTION','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL),

-- 3
('backlog_client_v2_grid','FAMILLE','FAMILLE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL),

-- 4
('backlog_client_v2_grid','SKU','SKU','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,NULL,0,NULL,NULL),

-- 4
('backlog_client_v2_grid','ARTICLE','ARTICLE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,NULL,NULL),

-- 4
('backlog_client_v2_grid','VARIANT','VARIANT','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL),

-- 5
('backlog_client_v2_grid','EAN','EAN','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,11,NULL,0,NULL,NULL),

-- GENRE (nouveau)
('backlog_client_v2_grid','GENRE','GENRE','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,12,NULL,0,NULL,NULL),

-- AGE (nouveau)
('backlog_client_v2_grid','AGE','AGE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,13,NULL,0,NULL,NULL),

-- 6
('backlog_client_v2_grid','ITMDES1_0','DESIGNATION','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,14,NULL,0,NULL,NULL),

-- 6
('backlog_client_v2_grid','DROPPE','DROPPÉ','string',200,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,15,NULL,0,NULL,NULL),

-- NOOS (nouveau)
('backlog_client_v2_grid','NOOS','NOOS','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,16,NULL,0,NULL,NULL),

-- GROUP_CODE
('backlog_client_v2_grid','GROUP_CODE','GROUP CODE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,17,NULL,0,NULL,NULL),

-- 7
('backlog_client_v2_grid','CLIENT_COMMANDE','CODE CLIENT CMD.','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,18,NULL,0,NULL,NULL),

-- 8
('backlog_client_v2_grid','NOM_CLIENT_COMMANDE','NOM CLIENT CMD.','string',190,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,19,NULL,0,NULL,NULL),

-- 7
('backlog_client_v2_grid','CLIENT','CODE CLIENT','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,20,NULL,0,NULL,NULL),

-- 8
('backlog_client_v2_grid','NOM_CLIENT','NOM CLIENT','string',180,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,21,NULL,0,NULL,NULL),

-- REFERENCE_INTERNE (nouveau, à côté de NOM_CLIENT)
('backlog_client_v2_grid','REFERENCE_INTERNE','REF. INTERNE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,22,NULL,0,NULL,NULL),

-- 9
('backlog_client_v2_grid','ADRESSE_LIVRAISON','ADRR. LIVRAISON','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,23,NULL,0,NULL,NULL),

-- 21
('backlog_client_v2_grid','CODE_POSTAL','CODE POSTAL','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,24,NULL,0,NULL,NULL),

-- 8
('backlog_client_v2_grid','VILLE','VILLE','string',180,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,25,NULL,0,NULL,NULL),

-- 9
('backlog_client_v2_grid','INDEPENDANT_GROUPMENT','NOM GROUPEMENT','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,26,NULL,0,NULL,NULL),

-- 10
('backlog_client_v2_grid','NUM_COMMANDE','N° COMMANDE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,27,NULL,0,NULL,NULL),

-- REF CLIENT (nouveau)
('backlog_client_v2_grid','REF_CLIENT','REF. COMMANDE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,28,NULL,0,NULL,NULL),

-- 11
('backlog_client_v2_grid','DATE_COMMANDE','DATE COMMANDE','date',130,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,29,NULL,0,NULL,NULL),

-- 12
('backlog_client_v2_grid','DATE_LIVRAISON','DATE LIVRAISON','date',130,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,30,NULL,0,NULL,NULL),

-- 14
('backlog_client_v2_grid','QUANTITE','QUANTITE','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,31,NULL,0,'integerFormatter','integerComparator'),

-- PO EN COURS
('backlog_client_v2_grid','PO_EN_COURS','PO EN COURS','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,32,NULL,0,'integerFormatter','integerComparator'),

-- 15
('backlog_client_v2_grid','PRIX','PRIX DEVISE','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,33,NULL,0,'decimalFormatter','decimalComparator'),

-- 16
('backlog_client_v2_grid','CUR_0','DEVISE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,34,NULL,0,NULL,NULL),

-- REMISE AUTO (nouveau)
('backlog_client_v2_grid','REMISE_AUTO','REMISE AUTO','percent',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,35,NULL,0,'percentRawFormatter','decimalComparator'),

-- REMISE MANU (nouveau)
('backlog_client_v2_grid','REMISE_MANU','REMISE MANU','percent',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,36,NULL,0,'percentRawFormatter','decimalComparator'),

-- 17
('backlog_client_v2_grid','PRIX_EUR','PRIX EUR','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,37,NULL,0,'decimalFormatter','decimalComparator'),

-- 18 — WLOGM (5 colonnes)
('backlog_client_v2_grid','STOCK_INTERNE_WLOGM','Stock Logtex Physique','integer',110,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,38,'bg-info',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','STOCK_REEL_WLOGM','Stock Logtex physique - commande client','integer',110,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,39,'bg-info',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','EN_TRANSIT_WLOGM','En transit vers Stock Logtex','integer',110,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,40,'bg-info',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','STOCK_A_TERME_TRANSIT_WLOGM','Stock à terme post-transit Logtex','integer',150,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,41,'bg-info',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','STOCK_A_TERME_BACKLOG_FOURNISSEUR_WLOGM','Stock à terme avec Backlog fournisseur Logtex','integer',160,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,42,'bg-info',0,'integerFormatter','integerComparator'),

-- 18 — WSFCN (5 colonnes)
('backlog_client_v2_grid','STOCK_INTERNE_WSFCN','Stock WSFCN Physique','integer',110,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,43,'bg-info-light',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','STOCK_REEL_WSFCN','Stock WSFCN physique - commande client','integer',110,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,0,44,'bg-info-light',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','EN_TRANSIT_WSFCN','En transit vers Stock WSFCN','integer',110,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,0,45,'bg-info-light',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','STOCK_A_TERME_TRANSIT_WSFCN','Stock à terme post-transit WSFCN','integer',150,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,0,46,'bg-info-light',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','STOCK_A_TERME_BACKLOG_FOURNISSEUR_WSFCN','Stock à terme avec Backlog fournisseur WSFCN','integer',160,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,47,'bg-info-light',0,'integerFormatter','integerComparator'),

-- 18 — WTAKH (5 colonnes)
('backlog_client_v2_grid','STOCK_INTERNE_WTAKH','Stock WTAKH Physique','integer',110,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,48,'bg-success-light',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','STOCK_REEL_WTAKH','Stock WTAKH physique - commande client','integer',110,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,0,49,'bg-success-light',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','EN_TRANSIT_WTAKH','En transit vers Stock WTAKH','integer',110,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,0,50,'bg-success-light',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','STOCK_A_TERME_TRANSIT_WTAKH','Stock à terme post-transit WTAKH','integer',150,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,0,51,'bg-success-light',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','STOCK_A_TERME_BACKLOG_FOURNISSEUR_WTAKH','Stock à terme avec Backlog fournisseur WTAKH','integer',160,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,52,'bg-success-light',0,'integerFormatter','integerComparator'),

-- 18 — WDTTH (5 colonnes)
('backlog_client_v2_grid','STOCK_INTERNE_WDTTH','Stock WDTTH Physique','integer',110,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,53,'bg-primary-light',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','STOCK_REEL_WDTTH','Stock WDTTH physique - commande client','integer',110,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,0,54,'bg-primary-light',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','EN_TRANSIT_WDTTH','En transit vers Stock WDTTH','integer',110,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,0,55,'bg-primary-light',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','STOCK_A_TERME_TRANSIT_WDTTH','Stock à terme post-transit WDTTH','integer',150,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,0,56,'bg-primary-light',0,'integerFormatter','integerComparator'),

('backlog_client_v2_grid','STOCK_A_TERME_BACKLOG_FOURNISSEUR_WDTTH','Stock à terme avec Backlog fournisseur WDTTH','integer',160,0,NULL,
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,57,'bg-primary-light',0,'integerFormatter','integerComparator'),

-- DATE ARRIVEE PREVUE (déplacée en toute dernière colonne)
('backlog_client_v2_grid','DATE_COMMANDE_FOURNISSEUR','Date arrivée prévue','date',150,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,58,NULL,0,NULL,NULL);
