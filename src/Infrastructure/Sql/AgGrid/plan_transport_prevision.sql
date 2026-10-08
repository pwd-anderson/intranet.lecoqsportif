SELECT * FROM intranet_lcs.aggrid_option where grid_name = 'plan_transport_prevision_grid';
delete FROM intranet_lcs.aggrid_option where grid_name = 'plan_transport_prevision_grid';

-- Plan transport prévision (Achats). Colonnes ARTICLE / VARIANT masquées par défaut (visible = 0),
-- disponibles dans le sélecteur de colonnes. À charger avec --default-character-set=utf8mb4 (accents).
INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES
('plan_transport_prevision_grid','FOURNISSEUR','Fournisseur','string',200,1,'agSetColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),
('plan_transport_prevision_grid','NUM_PO','N° PO','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),
('plan_transport_prevision_grid','ARTICLE_SKU','Article (SKU)','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL),
('plan_transport_prevision_grid','DESIGNATION','Désignation','string',260,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL),
('plan_transport_prevision_grid','QTE','Qté (pcs)','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,5,NULL,0,'integerFormatter','integerComparator'),
('plan_transport_prevision_grid','MOYEN_TRANSPORT','Moyen de transport','string',200,1,'agSetColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL),
('plan_transport_prevision_grid','DATE_XF','Départ usine (XF)','date',130,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL),
('plan_transport_prevision_grid','DATE_ETD','ETD','date',110,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,NULL,0,NULL,NULL),
('plan_transport_prevision_grid','DATE_ETA_FRANCE','ETA France (Logtex)','date',150,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,NULL,NULL),
('plan_transport_prevision_grid','STATUT_DEPART','Statut départ','string',170,1,'agSetColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL),
('plan_transport_prevision_grid','ARTICLE','Article (parent)','string',120,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,0,11,NULL,0,NULL,NULL),
('plan_transport_prevision_grid','VARIANT','Variant','string',90,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,0,12,NULL,0,NULL,NULL);
