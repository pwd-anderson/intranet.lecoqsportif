INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, compatator)
VALUES
('commandes_allouees_sans_bp_grid','NO_COMMANDE','N° COMMANDE','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),

('commandes_allouees_sans_bp_grid','TYPE_COMMANDE','TYPE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),

('commandes_allouees_sans_bp_grid','DATE_CREATION','DATE CRÉATION','date',120,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL),

('commandes_allouees_sans_bp_grid','CLIENT_COMMANDE','CLIENT','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL),

('commandes_allouees_sans_bp_grid','ARTICLE','ARTICLE','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL),

('commandes_allouees_sans_bp_grid','QTE_COMMANDEE','QTÉ COMMANDÉE','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,6,NULL,0,'decimalFormatter','decimalComparator'),

('commandes_allouees_sans_bp_grid','QTE_ALLOUEE','QTÉ ALLOUÉE','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,7,NULL,0,'decimalFormatter','decimalComparator'),

('commandes_allouees_sans_bp_grid','QTE_LIVREE','QTÉ LIVRÉE','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,8,NULL,0,'decimalFormatter','decimalComparator'),

('commandes_allouees_sans_bp_grid','SITE_EXPEDITION','SITE EXPÉDITION','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,NULL,NULL),

('commandes_allouees_sans_bp_grid','STATUT_ALLOCATION','STATUT ALLOCATION','string',160,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL);
 
 ------
 INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, compatator)
VALUES
('commandes_bp_sans_livraison_grid','NO_COMMANDE','N° COMMANDE','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),

('commandes_bp_sans_livraison_grid','TYPE_COMMANDE','TYPE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),

('commandes_bp_sans_livraison_grid','DATE_CREATION','DATE CRÉATION','date',120,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL),

('commandes_bp_sans_livraison_grid','ANCIENNETE_JOURS','ANCIENNETÉ (JOURS)','integer',140,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,'decimalFormatter','decimalComparator'),

('commandes_bp_sans_livraison_grid','CLIENT_COMMANDE','CLIENT','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL),

('commandes_bp_sans_livraison_grid','ARTICLE','ARTICLE','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL),

('commandes_bp_sans_livraison_grid','QTE_COMMANDEE','QTÉ COMMANDÉE','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,7,NULL,0,'decimalFormatter','decimalComparator'),

('commandes_bp_sans_livraison_grid','QTE_ALLOUEE','QTÉ ALLOUÉE','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,8,NULL,0,'decimalFormatter','decimalComparator'),

('commandes_bp_sans_livraison_grid','QTE_LIVREE','QTÉ LIVRÉE','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,9,NULL,0,'decimalFormatter','decimalComparator'),

('commandes_bp_sans_livraison_grid','SITE_EXPEDITION','SITE EXPÉDITION','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL),

('commandes_bp_sans_livraison_grid','NO_BP','N° BP','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,11,NULL,0,NULL,NULL),

('commandes_bp_sans_livraison_grid','REGLE_ANCIENNETE','RÈGLE ANCIENNETÉ','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,12,NULL,0,NULL,NULL);
 -----
 
 INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, compatator)
VALUES
('commandes_non_soldees_par_date_grid','DATE_CREATION','DATE CRÉATION','date',140,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),

('commandes_non_soldees_par_date_grid','ESHOP','ESHOP','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,2,NULL,0,'decimalFormatter','decimalComparator'),

('commandes_non_soldees_par_date_grid','GLOBALE','GLOBALE','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,3,NULL,0,'decimalFormatter','decimalComparator'),

('commandes_non_soldees_par_date_grid','MARK','MARK','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,4,NULL,0,'decimalFormatter','decimalComparator'),

('commandes_non_soldees_par_date_grid','TOTAL','TOTAL','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,5,'bg-success-light',0,'decimalFormatter','decimalComparator');
 
 
 -----
 
 
 INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, compatator)
VALUES

 
 ('stock_produits_shop_non_coches_grid','YCOLLECT','COLLECTION','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),
 
-- 1
('stock_produits_shop_non_coches_grid','FAMILLE','FAMILLE','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),

 
 ('stock_produits_shop_non_coches_grid','ARTICLE','ARTICLE','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL),
 
 ('stock_produits_shop_non_coches_grid','STOCK_INTERNE_LOGTEX','LOGTEX','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,'bg-primary-light',0,'decimalFormatter','decimalComparator'),
 
  ('stock_produits_shop_non_coches_grid','STOCK_INTERNE_MAGASIN','MAGASINS','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,'bg-success-light',0,'decimalFormatter','decimalComparator');