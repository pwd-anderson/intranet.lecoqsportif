DELETE FROM intranet_lcs.aggrid_option WHERE grid_name = 'stock_a_terme_segmentation_produits_x3_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator, editable, cell_editor, cell_editor_params)
VALUES
('stock_a_terme_segmentation_produits_x3_grid','SITE','SITE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','DESCRIPTION_SITE','DESCRIPTION SITE','string',160,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','FAMILLE','FAMILLE','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','DERNIERE_COLLECTION','DERNIERE COLLECTION','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','ARTICLE','ARTICLE','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','DESCRIPTION_ARTICLE','DESCRIPTION ARTICLE','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','STATUT','STATUT','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','LARGEUR','LARGEUR','decimal',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','LONGUEUR','LONGUEUR','decimal',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','HAUTEUR','HAUTEUR','decimal',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','UNITE','UNITE','string',80,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,11,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','POIDS','POIDS','decimal',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,12,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','PRIX_MARCHE','PRIX MARCHE','decimal',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,13,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','DEVISE_MARCHE','DEVISE','string',80,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,14,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','PDM_MATERIAL_CODE','PDM MATERIAL CODE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,15,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','PAYS_ORIGINE','PAYS ORIGINE','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,16,'bg-lightest',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','STOCK_INTERNE','STOCK INTERNE','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,17,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','ACHAT_M0','ACHAT M0','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,18,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','VENTE_M0','VENTE M0','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,19,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_ETAIL_M0','RESA ETAIL M0','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,20,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_RETAIL_M0','RESA RETAIL M0','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,21,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','STOCK_TERME_M0','STOCK TERME M0','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,22,'bg-success-light',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','ACHAT_M1','ACHAT M1','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,23,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','VENTE_M1','VENTE M1','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,24,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_ETAIL_M1','RESA ETAIL M1','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,25,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_RETAIL_M1','RESA RETAIL M1','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,26,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','STOCK_TERME_M1','STOCK TERME M1','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,27,'bg-success-light',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','ACHAT_M2','ACHAT M2','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,28,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','VENTE_M2','VENTE M2','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,29,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_ETAIL_M2','RESA ETAIL M2','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,30,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_RETAIL_M2','RESA RETAIL M2','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,31,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','STOCK_TERME_M2','STOCK TERME M2','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,32,'bg-success-light',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','ACHAT_M3','ACHAT M3','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,33,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','VENTE_M3','VENTE M3','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,34,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_ETAIL_M3','RESA ETAIL M3','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,35,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_RETAIL_M3','RESA RETAIL M3','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,36,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','STOCK_TERME_M3','STOCK TERME M3','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,37,'bg-success-light',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','ACHAT_M4','ACHAT M4','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,38,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','VENTE_M4','VENTE M4','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,39,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_ETAIL_M4','RESA ETAIL M4','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,40,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_RETAIL_M4','RESA RETAIL M4','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,41,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','STOCK_TERME_M4','STOCK TERME M4','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,42,'bg-success-light',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','ACHAT_M5','ACHAT M5','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,43,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','VENTE_M5','VENTE M5','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,44,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_ETAIL_M5','RESA ETAIL M5','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,45,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_RETAIL_M5','RESA RETAIL M5','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,46,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','STOCK_TERME_M5','STOCK TERME M5','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,47,'bg-success-light',0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','ACHAT_M6','ACHAT M6','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,48,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','VENTE_M6','VENTE M6','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,49,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_ETAIL_M6','RESA ETAIL M6','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,50,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','RESA_RETAIL_M6','RESA RETAIL M6','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,51,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_a_terme_segmentation_produits_x3_grid','STOCK_TERME_M6','STOCK TERME M6','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'agg_func',1,52,'bg-success-light',0,NULL,NULL,NULL,NULL,NULL);
