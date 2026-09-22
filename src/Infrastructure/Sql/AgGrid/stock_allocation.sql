-- Configuration AG Grid de la stat Stock allocation (grid_name = stock_allocation_grid).
-- A rejouer sur chaque environnement (local, preprod, prod) apres deploiement.

SELECT * FROM intranet_lcs.aggrid_option WHERE grid_name = 'stock_allocation_grid';
DELETE FROM intranet_lcs.aggrid_option WHERE grid_name = 'stock_allocation_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator, editable, cell_editor, cell_editor_params)
VALUES
 ('stock_allocation_grid','SITE','Site','string',180,1,'agTextColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'',1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','DESCRIPTION_SITE','Description Site','string',220,1,'agTextColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'',1,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','FAMILLE','Famille','string',180,1,'agTextColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'',1,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','DERNIERE_COLLECTION','Derniere Collection','string',200,1,'agTextColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'',1,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','ARTICLE','Article','string',180,1,'agTextColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'',1,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','DESCRIPTION_ARTICLE','Description Article','string',250,1,'agTextColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'',1,6,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','NOOS','NOOS','string',110,1,'agMultiColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'',1,7,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','STATUS_STOCK','Status Stock','string',150,1,'agTextColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'',1,8,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','STOCK_INTERNE','Stock Interne','integer',150,1,'agNumberColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'aggFunc',1,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','VALORISATION_STOCK_INTERNE','Valorisation Stock Interne','integer',180,1,'agNumberColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'aggFunc',1,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','STOCK_ALLOUE','Stock Alloue','integer',150,1,'agNumberColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'aggFunc',1,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','VALORISATION_STOCK_ALLOUE','Valorisation Stock Alloue','integer',180,1,'agNumberColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'aggFunc',1,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','STOCK_DISPONIBLE','Stock Disponible','integer',150,1,'agNumberColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'aggFunc',1,13,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','VALORISATION_STOCK_DISPONIBLE','Valorisation Stock Disponible','integer',180,1,'agNumberColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'aggFunc',1,14,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','STOCK_RESERVE','Stock Reserve','integer',150,1,'agNumberColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'aggFunc',1,15,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','VALORISATION_STOCK_RESERVE','Valorisation Stock Reserve','integer',180,1,'agNumberColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'aggFunc',1,16,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','STOCK_REEL','Stock Reel','integer',150,1,'agNumberColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'aggFunc',1,17,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 ('stock_allocation_grid','VALORISATION_STOCK_REEL','Valorisation Stock Reel','integer',180,1,'agNumberColumnFilter','{"textAlign": "center", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',1,'aggFunc',1,18,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
