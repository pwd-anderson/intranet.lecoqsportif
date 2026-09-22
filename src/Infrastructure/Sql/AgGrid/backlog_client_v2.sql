SELECT * FROM intranet_lcs.aggrid_option where grid_name = 'backlog_client_v2_grid';
delete FROM intranet_lcs.aggrid_option where grid_name = 'backlog_client_v2_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES

-- 1
('backlog_client_v2_grid','SITE','SITE','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),

-- 2
('backlog_client_v2_grid','MAINNETWORK','MAINNETWORK','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),

-- 3
('backlog_client_v2_grid','ZCLASSE_0','TYPE CMD.','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL),

-- 4
('backlog_client_v2_grid','COLLECTION','COLLECTION','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL),

-- 5
('backlog_client_v2_grid','FAMILLE','FAMILLE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL),

-- 6
('backlog_client_v2_grid','SKU','SKU','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL),

-- 7
('backlog_client_v2_grid','ARTICLE','ARTICLE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL),

-- 8
('backlog_client_v2_grid','VARIANT','VARIANT','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,NULL,0,NULL,NULL),

-- 9
('backlog_client_v2_grid','EAN','EAN','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,NULL,NULL),

-- 10
('backlog_client_v2_grid','GENRE','GENRE','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL),

-- 11
('backlog_client_v2_grid','AGE','AGE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,11,NULL,0,NULL,NULL),

-- 12
('backlog_client_v2_grid','ITMDES1_0','DESIGNATION','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,12,NULL,0,NULL,NULL),

-- 13
('backlog_client_v2_grid','DROPPE','DROPPÉ','string',200,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,13,NULL,0,NULL,NULL),

-- NOOS
('backlog_client_v2_grid','NOOS','NOOS','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'), NULL,NULL,1,14,NULL,0,NULL,NULL),

-- 14 (équivalent GROUP_CODE)
('backlog_client_v2_grid','DISTRIBUTION_CHANNEL','DISTRIBUTION CHANNEL','string',150,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,15,NULL,0,NULL,NULL),

-- GROUP CODE
('backlog_client_v2_grid','GROUP_CODE','GROUP CODE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,16,NULL,0,NULL,NULL),

-- 15
('backlog_client_v2_grid','CLIENT_COMMANDE','CODE CLIENT CMD.','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,17,NULL,0,NULL,NULL),

-- 16
('backlog_client_v2_grid','NOM_CLIENT_COMMANDE','NOM CLIENT CMD.','string',190,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,18,NULL,0,NULL,NULL),

-- 17
('backlog_client_v2_grid','CLIENT','CODE CLIENT','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,19,NULL,0,NULL,NULL),

-- 18
('backlog_client_v2_grid','NOM_CLIENT','NOM CLIENT','string',180,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,20,NULL,0,NULL,NULL),

-- 19
('backlog_client_v2_grid','REFERENCE_INTERNE','REF. INTERNE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,21,NULL,0,NULL,NULL),

-- 20
('backlog_client_v2_grid','ADRESSE_LIVRAISON','ADRR. LIVRAISON','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,22,NULL,0,NULL,NULL),

-- CODE POSTAL
('backlog_client_v2_grid','CODE_POSTAL','CODE POSTAL','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,23,NULL,0,NULL,NULL),

-- 21
('backlog_client_v2_grid','VILLE','VILLE','string',180,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,24,NULL,0,NULL,NULL),

-- 22
('backlog_client_v2_grid','INDEPENDANT_GROUPMENT','NOM GROUPEMENT','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,25,NULL,0,NULL,NULL),

-- 23
('backlog_client_v2_grid','NUM_COMMANDE','N° COMMANDE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,26,NULL,0,NULL,NULL),

-- 24
('backlog_client_v2_grid','REF_CLIENT','REF. COMMANDE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,27,NULL,0,NULL,NULL),

-- 25
('backlog_client_v2_grid','DATE_COMMANDE','DATE COMMANDE','date',130,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,28,NULL,0,NULL,NULL),

-- 26
('backlog_client_v2_grid','DATE_LIVRAISON','DATE LIVRAISON','date',150,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,29,NULL,0,NULL,NULL),

-- 27
('backlog_client_v2_grid','REP1','REPRESENTANT 1','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,30,NULL,0,NULL,NULL),

-- 28
('backlog_client_v2_grid','REP2','REPRESENTANT 2','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,31,NULL,0,NULL,NULL),

-- 29 : Quantité commandée
('backlog_client_v2_grid','QUANTITE_COMMANDE','QTÉ COMMANDÉE','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,32,NULL,0,'integerFormatter','integerComparator'),

-- 30 : Montant Commande Devise
('backlog_client_v2_grid','MONTANT_COMMANDE_DEVISE','MONTANT CMD. DEVISE','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,33,NULL,0,'decimalFormatter','decimalComparator'),

-- 31 : Montant Commande EUR
('backlog_client_v2_grid','MONTANT_COMMANDE_EUR','MONTANT CMD. EUR','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,34,NULL,0,'decimalFormatter','decimalComparator'),

-- 32 : Quantité livrée
('backlog_client_v2_grid','QUANTITE_LIVREE','QTÉ LIVRÉE','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,35,NULL,0,'integerFormatter','integerComparator'),

-- 33 : Montant Livraison Devise
('backlog_client_v2_grid','MONTANT_LIVREE_DEVISE','MONTANT LIVRÉ DEVISE','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,36,NULL,0,'decimalFormatter','decimalComparator'),

-- 34 : Montant Livraison EUR
('backlog_client_v2_grid','MONTANT_LIVREE_EUR','MONTANT LIVRÉ EUR','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,37,NULL,0,'decimalFormatter','decimalComparator'),

-- 35 : Date Expédition (string concaténée)
('backlog_client_v2_grid','DATES_EXPEDITION','DATES EXPÉDITION','string',220,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,38,NULL,0,NULL,NULL),

-- 36 : Quantité Reste à Livrer
('backlog_client_v2_grid','QUANTITE','QUANTITE','integer',140,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,39,NULL,0,'integerFormatter','integerComparator'),

-- PO EN COURS
('backlog_client_v2_grid','PO_EN_COURS','PO EN COURS','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,40,NULL,0,'integerFormatter','integerComparator'),

-- 37 : Montant Reste à Livrer Devise
('backlog_client_v2_grid','MONTANT_A_LIVRER_DEVISE','PRIX DEVISE','decimal',180,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,41,NULL,0,'decimalFormatter','decimalComparator'),

-- 38 : Montant Reste à Livrer EUR
('backlog_client_v2_grid','MONTANT_A_LIVRER_EUR','PRIX EUR','decimal',180,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,42,NULL,0,'decimalFormatter','decimalComparator'),

-- 39 : Pays
('backlog_client_v2_grid','PAYS','PAYS','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,43,NULL,0,NULL,NULL),

-- 40 : Client livré
('backlog_client_v2_grid','CLIENT_LIVRE','CLIENT LIVRÉ','string',180,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,44,NULL,0,NULL,NULL),

-- 41 : Paiement
('backlog_client_v2_grid','PAIEMENT','PAIEMENT','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,45,NULL,0,NULL,NULL),

-- 42 : Ligne
('backlog_client_v2_grid','LIGNE','LIGNE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,46,NULL,0,NULL,NULL),

-- 43 : Statut Article
('backlog_client_v2_grid','STATUT_ARTICLE','STATUT ARTICLE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,47,NULL,0,NULL,NULL),

-- 44 : Quantité Allouée
('backlog_client_v2_grid','QUANTITE_ALLOUEE','QTÉ ALLOUÉE','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,48,NULL,0,'integerFormatter','integerComparator'),

-- 45 : Quantité en rupture
('backlog_client_v2_grid','QUANTITE_EN_RUPTURE','QTÉ EN RUPTURE','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,49,NULL,0,'integerFormatter','integerComparator'),

-- 46 : Reste à allouer
('backlog_client_v2_grid','RESTE_A_ALLOUER','RESTE À ALLOUER','integer',140,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,50,NULL,0,'integerFormatter','integerComparator'),

-- 47 : Devise
('backlog_client_v2_grid','CUR_0','DEVISE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,51,NULL,0,NULL,NULL),

-- 48 : Prix HT
('backlog_client_v2_grid','PRICE_HT','PRIX HT','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,52,NULL,0,'decimalFormatter','decimalComparator'),

-- 49 : Prix Brut HT
('backlog_client_v2_grid','GROSS_PRICE_HT','PRIX BRUT UNITAIRE HT','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,53,NULL,0,'decimalFormatter','decimalComparator'),

-- 49 : Remise Auto
('backlog_client_v2_grid','REMISE_AUTO','REMISE AUTO','percent',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,54,NULL,0,'percentRawFormatter','decimalComparator'),

-- 50 : Remise Manu
('backlog_client_v2_grid','REMISE_MANU','REMISE MANU','percent',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,55,NULL,0,'percentRawFormatter','decimalComparator'),

-- 51 : Remise Globale
('backlog_client_v2_grid','REMISE_GLOBAL','REMISE GLOBALE','percent',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,56,NULL,0,'percentRawFormatter','decimalComparator'),

-- 52 : Prix Net Unitaire HT (remise globale appliquée au Prix HT)
('backlog_client_v2_grid','PRIX_NET_UNITAIRE_HT','PRIX NET UNITAIRE HT','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,57,NULL,0,'decimalFormatter','decimalComparator'),

-- Date arrivée prévue (fournisseur, repli intersite)
('backlog_client_v2_grid','DATE_COMMANDE_FOURNISSEUR','Date arrivée prévue','date',150,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,58,NULL,0,NULL,NULL);
