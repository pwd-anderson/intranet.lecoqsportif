SELECT * FROM intranet_lcs.aggrid_option where grid_name = 'backlog_fournisseur_x3_grid';
delete FROM intranet_lcs.aggrid_option where grid_name = 'backlog_fournisseur_x3_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES

-- 1 (INTERSITE)
('backlog_fournisseur_x3_grid','INTERSITE','INTERSITE','string',110,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),

-- 2 (TYPE FLUX - nouveau)
('backlog_fournisseur_x3_grid','TYPE_FLUX','TYPE FLUX','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),

-- 3 (SITE EXPEDITION - nouveau)
('backlog_fournisseur_x3_grid','SITE_EXPEDITION','SITE EXPEDITION','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL),

-- 4 (SITE RECEPTION - ex PRHFCY_0)
('backlog_fournisseur_x3_grid','SITE_RECEPTION','SITE RECEPTION','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL),

-- 5
('backlog_fournisseur_x3_grid','COLLECTION','COLLECTION','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL),

-- 6
('backlog_fournisseur_x3_grid','FAMILLE','FAMILLE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL),

-- 7
('backlog_fournisseur_x3_grid','ITMREF_0','ARTICLE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL),

-- 8
('backlog_fournisseur_x3_grid','ITMDES1_0','DESIGNATION','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,NULL,0,NULL,NULL),

-- 9
('backlog_fournisseur_x3_grid','DROPPE','DROPPÉ','string',110,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,NULL,NULL),

-- 10
('backlog_fournisseur_x3_grid','BPSNUM_0','CODE FOURN.','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL),

-- 11
('backlog_fournisseur_x3_grid','BPRNAM_0','NOM FOURN.','string',160,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,11,NULL,0,NULL,NULL),

-- 12
('backlog_fournisseur_x3_grid','POHNUM_0','N° COMMANDE','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,12,NULL,0,NULL,NULL),

-- 13
('backlog_fournisseur_x3_grid','ORDDAT_0','DATE DE COMMANDE','date',120,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,13,NULL,0,NULL,NULL),

-- 14
('backlog_fournisseur_x3_grid','XSHIPDAT_0','DATE EXPÉDITION','date',120,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,14,NULL,0,NULL,NULL),

-- 15
('backlog_fournisseur_x3_grid','EXTRCPDAT_0','DATE LIVRAISON','date',120,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,15,NULL,0,NULL,NULL),

-- 16
('backlog_fournisseur_x3_grid','STATUS','STATUS','string',110,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,16,NULL,0,NULL,NULL),

-- 17
('backlog_fournisseur_x3_grid','ORDREF_0','REF. INTERNE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,17,NULL,0,NULL,NULL),

-- 18
('backlog_fournisseur_x3_grid','QUANTITE','QTÉ À LIVRER','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,18,NULL,0,'integerFormatter','integerComparator'),

-- 19
('backlog_fournisseur_x3_grid','PRIX','MONTANT DEVISE','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,19,NULL,0,'decimalFormatter','decimalComparator'),

-- 20
('backlog_fournisseur_x3_grid','CUR_0','DEVISE ACHAT','string',110,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,20,NULL,0,NULL,NULL),

-- 21
('backlog_fournisseur_x3_grid','PRIX_EUR','MONTANT EN EUR','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,21,NULL,0,'decimalFormatter','decimalComparator'),

-- 22
('backlog_fournisseur_x3_grid','MDL_0','MODE TRANSP.','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,22,NULL,0,NULL,NULL),

-- 23
('backlog_fournisseur_x3_grid','VALIDE','VALIDE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,23,NULL,0,NULL,NULL);