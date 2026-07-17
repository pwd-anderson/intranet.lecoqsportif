SELECT * FROM intranet_lcs.aggrid_option WHERE grid_name = 'stock_a_terme_x3_grid';
DELETE FROM intranet_lcs.aggrid_option WHERE grid_name = 'stock_a_terme_x3_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES

('stock_a_terme_x3_grid','SITE','SITE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),

('stock_a_terme_x3_grid','DESCRIPTION_SITE','DESC. SITE','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),

('stock_a_terme_x3_grid','FAMILLE','FAMILLE','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL),

('stock_a_terme_x3_grid','DERNIERE_COLLECTION','COLLECTION','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL),

('stock_a_terme_x3_grid','ARTICLE','ARTICLE','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL),

('stock_a_terme_x3_grid','DESCRIPTION_ARTICLE','DESIGNATION','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL),

('stock_a_terme_x3_grid','STATUT','STATUT','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL),

('stock_a_terme_x3_grid','CARRY_OVER','CARRY OVER','string',110,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,NULL,0,NULL,NULL),

('stock_a_terme_x3_grid','STOCK_INTERNE','STK INTERNE','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,9,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','ACHAT_M0','ACHAT M0','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,10,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','VENTE_M0','VENTE M0','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,11,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_ETAIL_M0','RESA ETAIL M0','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,12,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_RETAIL_M0','RESA RETAIL M0','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,13,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','STOCK_TERME_M0','STK TERME M0','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,14,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','ACHAT_M1','ACHAT M1','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,15,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','VENTE_M1','VENTE M1','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,16,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_ETAIL_M1','RESA ETAIL M1','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,17,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_RETAIL_M1','RESA RETAIL M1','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,18,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','STOCK_TERME_M1','STK TERME M1','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,19,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','ACHAT_M2','ACHAT M2','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,20,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','VENTE_M2','VENTE M2','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,21,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_ETAIL_M2','RESA ETAIL M2','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,22,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_RETAIL_M2','RESA RETAIL M2','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,23,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','STOCK_TERME_M2','STK TERME M2','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,24,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','ACHAT_M3','ACHAT M3','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,25,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','VENTE_M3','VENTE M3','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,26,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_ETAIL_M3','RESA ETAIL M3','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,27,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_RETAIL_M3','RESA RETAIL M3','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,28,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','STOCK_TERME_M3','STK TERME M3','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,29,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','ACHAT_M4','ACHAT M4','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,30,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','VENTE_M4','VENTE M4','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,31,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_ETAIL_M4','RESA ETAIL M4','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,32,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_RETAIL_M4','RESA RETAIL M4','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,33,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','STOCK_TERME_M4','STK TERME M4','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,34,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','ACHAT_M5','ACHAT M5','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,35,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','VENTE_M5','VENTE M5','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,36,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_ETAIL_M5','RESA ETAIL M5','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,37,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_RETAIL_M5','RESA RETAIL M5','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,38,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','STOCK_TERME_M5','STK TERME M5','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,39,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','ACHAT_M6','ACHAT M6','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,40,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','VENTE_M6','VENTE M6','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,41,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_ETAIL_M6','RESA ETAIL M6','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,42,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','RESA_RETAIL_M6','RESA RETAIL M6','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,43,NULL,0,'integerFormatter','integerComparator'),

('stock_a_terme_x3_grid','STOCK_TERME_M6','STK TERME M6','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,44,NULL,0,'integerFormatter','integerComparator');
