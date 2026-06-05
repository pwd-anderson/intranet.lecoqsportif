DELETE FROM intranet_lcs.aggrid_option WHERE grid_name = 'stock_produits_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator, editable, cell_editor, cell_editor_params)
VALUES
-- ============ COLONNES EXISTANTES (réinsérées avec nouveaux order_index) ============
('stock_produits_grid','PHOTO','PHOTO','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','CODE_ARTICLE','ARTICLE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','COLLECTION','COLLECTION','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','FAMILLE','FAMILLE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','GENRE','GENRE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','CODE_DOUANIER','CODE DOUANIER','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','ORIGINE','ORIGINE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL,NULL,NULL,NULL),

-- ============ 3 NOUVELLES COLONNES (PLM) ============
('stock_produits_grid','SMUCustomer','SMU CUSTOMER','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','Division','DIVISION','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','ProductLineCode','PRODUCT LINE CODE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL,NULL,NULL,NULL);
