SELECT * FROM intranet_lcs.aggrid_option WHERE grid_name = 'stock_logtex_grid';
DELETE FROM intranet_lcs.aggrid_option WHERE grid_name = 'stock_logtex_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES
('stock_logtex_grid','PHOTO','Photo','string',90,0,NULL,'{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,1,NULL,0,NULL,NULL),
('stock_logtex_grid','DERNIERE_COLLECTION','Dernière Collection','string',140,1,'agSetColumnFilter','{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,2,NULL,0,NULL,NULL),
('stock_logtex_grid','FAMILLE','Famille','string',100,1,'agSetColumnFilter','{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,3,NULL,0,NULL,NULL),
('stock_logtex_grid','ARTICLE_BASE','Article','string',130,1,'agTextColumnFilter','{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,4,NULL,0,NULL,NULL),
('stock_logtex_grid','ARTICLE','SKU','string',160,1,'agTextColumnFilter','{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,5,NULL,0,NULL,NULL),
('stock_logtex_grid','DESCRIPTION_ARTICLE','Description','string',220,1,'agTextColumnFilter','{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,6,NULL,0,NULL,NULL),
('stock_logtex_grid','VARIANT','Variante','string',120,1,'agSetColumnFilter','{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,7,NULL,0,NULL,NULL),
('stock_logtex_grid','FLAG_SHOPIFY','Shopify','string',100,1,'agSetColumnFilter','{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,8,NULL,0,NULL,NULL),
('stock_logtex_grid','CANAL_PRIX','Canal Prix','string',110,1,'agSetColumnFilter','{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,9,NULL,0,NULL,NULL),
('stock_logtex_grid','STATUS_STOCK','Statut Stock','string',100,1,'agSetColumnFilter','{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,10,NULL,0,NULL,NULL),
('stock_logtex_grid','STOCK_REEL','Stock Réel','integer',110,1,'agNumberColumnFilter','{"textAlign":"right","borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,'agg_func',1,11,'bg-success-light',0,'integerFormatter','integerComparator'),
('stock_logtex_grid','DEVISE_SRP','Devise SRP','string',90,1,'agSetColumnFilter','{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,12,NULL,0,NULL,NULL),
('stock_logtex_grid','PRIX_SRP','Prix SRP','decimal',100,1,'agNumberColumnFilter','{"textAlign":"right","borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,13,NULL,0,'decimalFormatter','decimalComparator'),
('stock_logtex_grid','DEVISE_BOUTIQUE','Devise Boutique','string',90,1,'agSetColumnFilter','{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,14,NULL,0,NULL,NULL),
('stock_logtex_grid','PRIX_BOUTIQUE','Prix Boutique','decimal',110,1,'agNumberColumnFilter','{"textAlign":"right","borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,15,NULL,0,'decimalFormatter','decimalComparator'),
('stock_logtex_grid','DEVISE_OUTLET','Devise Outlet','string',90,1,'agSetColumnFilter','{"borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,16,NULL,0,NULL,NULL),
('stock_logtex_grid','PRIX_OUTLET','Prix Outlet','decimal',100,1,'agNumberColumnFilter','{"textAlign":"right","borderRight":"0.2px solid #CECECEFF","borderBottom":"0.2px solid #CECECEFF"}',NULL,NULL,1,17,NULL,0,'decimalFormatter','decimalComparator');
