DELETE FROM intranet_lcs.aggrid_option WHERE grid_name = 'stock_produits_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator, editable, cell_editor, cell_editor_params)
VALUES
('stock_produits_grid','PHOTO','PHOTO','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','CODE_ARTICLE','ARTICLE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','CODE_MODELE','CODE MODELE','string',110,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','NOOS','NOOS','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','COLLECTION','COLLECTION','string',110,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','COLLECTION_DROPPEE','COLLECTION DROPPÉE','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','MOTIF_DROPPEE','MOTIF DROPPÉE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','FAMILLE','FAMILLE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','GENRE','GENRE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','GROUPE_AGE','GROUPE ÂGE','string',110,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','CODE_DOUANIER','CODE DOUANIER','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,11,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','ORIGINE','ORIGINE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,12,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','FOURNISSEUR','FOURNISSEUR','string',110,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,13,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','NOM_FOURNISSEUR','NOM FOURNISSEUR','string',160,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,14,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','DATE_INTRODUCTION','DATE D''INTRODUCTION','date',130,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,15,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','SEGMENT_OFFRE','SEGMENT OFFRE','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,16,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','SOUS_CATEGORY','SOUS-CATÉGORIE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,17,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','SMUCustomer','SMU CUSTOMER','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,18,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','Division','DIVISION','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,19,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','ProductLineCode','PRODUCT LINE CODE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,20,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','ROYALTIES','ROYALTIES','string',110,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,21,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','CODE_FEDAS','CODE FEDAS','string',110,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,22,NULL,0,NULL,NULL,NULL,NULL,NULL),

('stock_produits_grid','COMPO_MAT_GLOBALE','COMPO MATIÈRE GLOBALE','string',180,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,23,NULL,0,NULL,NULL,NULL,NULL,NULL);
