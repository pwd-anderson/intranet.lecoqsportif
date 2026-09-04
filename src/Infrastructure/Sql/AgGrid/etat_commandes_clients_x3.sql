SELECT * FROM intranet_lcs.aggrid_option where grid_name = 'etat_commandes_clients_x3_grid';
delete FROM intranet_lcs.aggrid_option where grid_name = 'etat_commandes_clients_x3_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES

-- 1
('etat_commandes_clients_x3_grid','SITE','SITE','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),

-- 2
('etat_commandes_clients_x3_grid','MAINNETWORK','MAINNETWORK','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),

-- 3
('etat_commandes_clients_x3_grid','ZCLASSE_0','TYPE CMD.','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL),

-- 4
('etat_commandes_clients_x3_grid','COLLECTION','COLLECTION','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL),

-- 5
('etat_commandes_clients_x3_grid','FAMILLE','FAMILLE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL),

-- 6
('etat_commandes_clients_x3_grid','SKU','SKU','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL),

-- 7
('etat_commandes_clients_x3_grid','ARTICLE','ARTICLE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL),

-- 8
('etat_commandes_clients_x3_grid','VARIANT','VARIANT','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,NULL,0,NULL,NULL),

-- 9
('etat_commandes_clients_x3_grid','EAN','EAN','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,NULL,NULL),

-- 10
('etat_commandes_clients_x3_grid','GENRE','GENRE','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL),

-- 11
('etat_commandes_clients_x3_grid','AGE','AGE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,11,NULL,0,NULL,NULL),

-- 12
('etat_commandes_clients_x3_grid','ITMDES1_0','DESIGNATION','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,12,NULL,0,NULL,NULL),

-- 13
('etat_commandes_clients_x3_grid','DROPPE','DROPPÉ','string',200,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,13,NULL,0,NULL,NULL),

-- 14 (équivalent GROUP_CODE)
('etat_commandes_clients_x3_grid','DISTRIBUTION_CHANNEL','DISTRIBUTION CHANNEL','string',150,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,14,NULL,0,NULL,NULL),

-- 15
('etat_commandes_clients_x3_grid','CLIENT_COMMANDE','CODE CLIENT CMD.','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,15,NULL,0,NULL,NULL),

-- 16
('etat_commandes_clients_x3_grid','NOM_CLIENT_COMMANDE','NOM CLIENT CMD.','string',190,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,16,NULL,0,NULL,NULL),

-- 17
('etat_commandes_clients_x3_grid','CLIENT','CODE CLIENT','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,17,NULL,0,NULL,NULL),

-- 18
('etat_commandes_clients_x3_grid','NOM_CLIENT','NOM CLIENT','string',180,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,18,NULL,0,NULL,NULL),

-- 19
('etat_commandes_clients_x3_grid','REFERENCE_INTERNE','REF. INTERNE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,19,NULL,0,NULL,NULL),

-- 20
('etat_commandes_clients_x3_grid','ADRESSE_LIVRAISON','ADRR. LIVRAISON','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,20,NULL,0,NULL,NULL),

-- 21
('etat_commandes_clients_x3_grid','VILLE','VILLE','string',180,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,21,NULL,0,NULL,NULL),

-- 22
('etat_commandes_clients_x3_grid','INDEPENDANT_GROUPMENT','NOM GROUPEMENT','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,22,NULL,0,NULL,NULL),

-- 23
('etat_commandes_clients_x3_grid','NUM_COMMANDE','N° COMMANDE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,23,NULL,0,NULL,NULL),

-- 24
('etat_commandes_clients_x3_grid','REF_CLIENT','REF. COMMANDE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,24,NULL,0,NULL,NULL),

-- 25
('etat_commandes_clients_x3_grid','DATE_COMMANDE','DATE COMMANDE','date',130,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,25,NULL,0,NULL,NULL),

-- 26
('etat_commandes_clients_x3_grid','DATE_LIVRAISON_DEMANDEE','DATE LIVRAISON DEMANDÉE','date',150,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,26,NULL,0,NULL,NULL),

-- 27
('etat_commandes_clients_x3_grid','REP1','REPRESENTANT 1','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,27,NULL,0,NULL,NULL),

-- 28
('etat_commandes_clients_x3_grid','REP2','REPRESENTANT 2','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,28,NULL,0,NULL,NULL),

-- 29 : Quantité commandée
('etat_commandes_clients_x3_grid','QUANTITE_COMMANDE','QTÉ COMMANDÉE','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,29,NULL,0,'integerFormatter','integerComparator'),

-- 30 : Montant Commande Devise
('etat_commandes_clients_x3_grid','MONTANT_COMMANDE_DEVISE','MONTANT CMD. DEVISE','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,30,NULL,0,'decimalFormatter','decimalComparator'),

-- 31 : Montant Commande EUR
('etat_commandes_clients_x3_grid','MONTANT_COMMANDE_EUR','MONTANT CMD. EUR','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,31,NULL,0,'decimalFormatter','decimalComparator'),

-- 32 : Quantité livrée
('etat_commandes_clients_x3_grid','QUANTITE_LIVREE','QTÉ LIVRÉE','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,32,NULL,0,'integerFormatter','integerComparator'),

-- 33 : Montant Livraison Devise
('etat_commandes_clients_x3_grid','MONTANT_LIVREE_DEVISE','MONTANT LIVRÉ DEVISE','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,33,NULL,0,'decimalFormatter','decimalComparator'),

-- 34 : Montant Livraison EUR
('etat_commandes_clients_x3_grid','MONTANT_LIVREE_EUR','MONTANT LIVRÉ EUR','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,34,NULL,0,'decimalFormatter','decimalComparator'),

-- 35 : Date Expédition (string concaténée)
('etat_commandes_clients_x3_grid','DATES_EXPEDITION','DATES EXPÉDITION','string',220,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,35,NULL,0,NULL,NULL),

-- 36 : Quantité Reste à Livrer
('etat_commandes_clients_x3_grid','QUANTITE_A_LIVRER','QTÉ RESTE À LIVRER','integer',140,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,36,NULL,0,'integerFormatter','integerComparator'),

-- 37 : Montant Reste à Livrer Devise
('etat_commandes_clients_x3_grid','MONTANT_A_LIVRER_DEVISE','MONTANT RESTE À LIVRER DEVISE','decimal',180,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,37,NULL,0,'decimalFormatter','decimalComparator'),

-- 38 : Montant Reste à Livrer EUR
('etat_commandes_clients_x3_grid','MONTANT_A_LIVRER_EUR','MONTANT RESTE À LIVRER EUR','decimal',180,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,38,NULL,0,'decimalFormatter','decimalComparator'),

-- 39 : Pays
('etat_commandes_clients_x3_grid','PAYS','PAYS','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,39,NULL,0,NULL,NULL),

-- 40 : Client livré
('etat_commandes_clients_x3_grid','CLIENT_LIVRE','CLIENT LIVRÉ','string',180,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,40,NULL,0,NULL,NULL),

-- 41 : Paiement
('etat_commandes_clients_x3_grid','PAIEMENT','PAIEMENT','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,41,NULL,0,NULL,NULL),

-- 42 : Ligne
('etat_commandes_clients_x3_grid','LIGNE','LIGNE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,42,NULL,0,NULL,NULL),

-- 43 : Statut Article
('etat_commandes_clients_x3_grid','STATUT_ARTICLE','STATUT ARTICLE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,43,NULL,0,NULL,NULL),

-- 44 : Quantité Allouée
('etat_commandes_clients_x3_grid','QUANTITE_ALLOUEE','QTÉ ALLOUÉE','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,44,NULL,0,'integerFormatter','integerComparator'),

-- 45 : Quantité en rupture
('etat_commandes_clients_x3_grid','QUANTITE_EN_RUPTURE','QTÉ EN RUPTURE','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,45,NULL,0,'integerFormatter','integerComparator'),

-- 46 : Reste à allouer
('etat_commandes_clients_x3_grid','RESTE_A_ALLOUER','RESTE À ALLOUER','integer',140,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,46,NULL,0,'integerFormatter','integerComparator'),

-- 47 : Devise
('etat_commandes_clients_x3_grid','CUR_0','DEVISE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,47,NULL,0,NULL,NULL),

-- 48 : Prix HT
('etat_commandes_clients_x3_grid','PRICE_HT','PRIX HT','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,48,NULL,0,'decimalFormatter','decimalComparator'),

-- 49 : Prix Brut HT
('etat_commandes_clients_x3_grid','GROSS_PRICE_HT','PRIX BRUT UNITAIRE HT','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,49,NULL,0,'decimalFormatter','decimalComparator'),

-- 49 : Remise Auto
('etat_commandes_clients_x3_grid','REMISE_AUTO','REMISE AUTO','percent',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,50,NULL,0,'percentRawFormatter','decimalComparator'),

-- 50 : Remise Manu
('etat_commandes_clients_x3_grid','REMISE_MANU','REMISE MANU','percent',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,51,NULL,0,'percentRawFormatter','decimalComparator'),

-- 51 : Remise Globale
('etat_commandes_clients_x3_grid','REMISE_GLOBAL','REMISE GLOBALE','percent',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,52,NULL,0,'percentRawFormatter','decimalComparator'),

-- 52 : Prix Net Unitaire HT (remise globale appliquée au Prix HT)
('etat_commandes_clients_x3_grid','PRIX_NET_UNITAIRE_HT','PRIX NET UNITAIRE HT','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,53,NULL,0,'decimalFormatter','decimalComparator');
