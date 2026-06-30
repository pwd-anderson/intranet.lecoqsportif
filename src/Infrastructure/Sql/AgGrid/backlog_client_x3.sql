SELECT * FROM intranet_lcs.aggrid_option where grid_name = 'backlog_client_x3_grid';
delete FROM intranet_lcs.aggrid_option where grid_name = 'backlog_client_x3_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES

-- 1
('backlog_client_x3_grid','SITE','SITE','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),

('backlog_client_x3_grid','MAINNETWORK','MAINNETWORK','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),

('backlog_client_x3_grid','ZCLASSE_0','TYPE CMD.','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL),

-- 2
('backlog_client_x3_grid','COLLECTION','COLLECTION','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL),

-- 3
('backlog_client_x3_grid','FAMILLE','FAMILLE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL),

-- 4
('backlog_client_x3_grid','SKU','SKU','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL),

-- 4
('backlog_client_x3_grid','ARTICLE','ARTICLE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL),

-- 4
('backlog_client_x3_grid','VARIANT','VARIANT','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,NULL,0,NULL,NULL),

-- 5
('backlog_client_x3_grid','EAN','EAN','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,NULL,NULL),

-- GENRE (nouveau)
('backlog_client_x3_grid','GENRE','GENRE','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL),

-- AGE (nouveau)
('backlog_client_x3_grid','AGE','AGE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,11,NULL,0,NULL,NULL),

-- 6
('backlog_client_x3_grid','ITMDES1_0','DESIGNATION','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,12,NULL,0,NULL,NULL),

-- 6
('backlog_client_x3_grid','DROPPE','DROPPÉ','string',200,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,13,NULL,0,NULL,NULL),

-- 7
('backlog_client_x3_grid','CLIENT_COMMANDE','CODE CLIENT CMD.','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,14,NULL,0,NULL,NULL),

-- 8
('backlog_client_x3_grid','NOM_CLIENT_COMMANDE','NOM CLIENT CMD.','string',190,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,15,NULL,0,NULL,NULL),

-- 7
('backlog_client_x3_grid','CLIENT','CODE CLIENT','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,16,NULL,0,NULL,NULL),

-- 8
('backlog_client_x3_grid','NOM_CLIENT','NOM CLIENT','string',180,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,17,NULL,0,NULL,NULL),

-- 9
('backlog_client_x3_grid','ADRESSE_LIVRAISON','ADRR. LIVRAISON','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,18,NULL,0,NULL,NULL),

-- 8
('backlog_client_x3_grid','VILLE','VILLE','string',180,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,19,NULL,0,NULL,NULL),

-- 9
('backlog_client_x3_grid','INDEPENDANT_GROUPMENT','NOM GROUPEMENT','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,20,NULL,0,NULL,NULL),

-- 10
('backlog_client_x3_grid','NUM_COMMANDE','N° COMMANDE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,21,NULL,0,NULL,NULL),

-- REF CLIENT (nouveau)
('backlog_client_x3_grid','REF_CLIENT','REF. COMMANDE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,22,NULL,0,NULL,NULL),

-- 11
('backlog_client_x3_grid','DATE_COMMANDE','DATE COMMANDE','date',130,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,23,NULL,0,NULL,NULL),

-- 12
('backlog_client_x3_grid','DATE_LIVRAISON','DATE LIVRAISON','date',130,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,24,NULL,0,NULL,NULL),

-- 13
('backlog_client_x3_grid','REP1','REPRESENTANT 1','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,25,NULL,0,NULL,NULL),

-- 13
('backlog_client_x3_grid','REP2','REPRESENTANT 2','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,26,NULL,0,NULL,NULL),

-- 14
('backlog_client_x3_grid','QUANTITE','QUANTITE','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,27,NULL,0,'integerFormatter','integerComparator'),

-- 15
('backlog_client_x3_grid','PRIX','PRIX DEVISE','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,28,NULL,0,'decimalFormatter','decimalComparator'),

-- 16
('backlog_client_x3_grid','CUR_0','DEVISE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,29,NULL,0,NULL,NULL),

-- REMISE AUTO (nouveau)
('backlog_client_x3_grid','REMISE_AUTO','REMISE AUTO','percent',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,30,NULL,0,'percentRawFormatter','decimalComparator'),

-- REMISE MANU (nouveau)
('backlog_client_x3_grid','REMISE_MANU','REMISE MANU','percent',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,31,NULL,0,'percentRawFormatter','decimalComparator'),

-- 17
('backlog_client_x3_grid','PRIX_EUR','PRIX EUR','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,32,NULL,0,'decimalFormatter','decimalComparator'),

-- 18
('backlog_client_x3_grid','STOCK_INTERNE_WLOGM','STK INT. WLOGM','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,33,'bg-info',0,'integerFormatter','integerComparator'),

-- 18
('backlog_client_x3_grid','STOCK_REEL_WLOGM','STK REEL WLOGM','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,34,'bg-info',0,'integerFormatter','integerComparator'),

-- 18
('backlog_client_x3_grid','STOCK_INTERNE_WSFCN','STK INT. WSFCN','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,35,'bg-info-light',0,'integerFormatter','integerComparator'),

-- 18
('backlog_client_x3_grid','STOCK_REEL_WSFCN','STK REEL WSFCN','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,36,'bg-info-light',0,'integerFormatter','integerComparator'),

-- 18
('backlog_client_x3_grid','STOCK_INTERNE_WTAKH','STK INT. WTAKH','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,37,'bg-success-light',0,'integerFormatter','integerComparator'),

-- 18
('backlog_client_x3_grid','STOCK_REEL_WTAKH','STK REEL WTAKH','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,38,'bg-success-light',0,'integerFormatter','integerComparator'),

-- 18
('backlog_client_x3_grid','STOCK_INTERNE_WDTTH','STK INT. WDTTH','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,39,'bg-primary-light',0,'integerFormatter','integerComparator'),

-- 18
('backlog_client_x3_grid','STOCK_REEL_WDTTH','STK REEL WDTTH','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,40,'bg-primary-light',0,'integerFormatter','integerComparator');
