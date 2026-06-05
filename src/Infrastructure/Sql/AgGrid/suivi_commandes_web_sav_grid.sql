INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES

-- 1
('suivi_commandes_web_sav_grid','Date Création','DATE CRÉATION','date',120,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),

-- 2
('suivi_commandes_web_sav_grid','Type Commande','TYPE COMMANDE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),

-- 3
('suivi_commandes_web_sav_grid','Commande','COMMANDE','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL),

-- 4
('suivi_commandes_web_sav_grid','Ligne','LIGNE','integer',90,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,'integerFormatter','integerComparator'),

-- 5
('suivi_commandes_web_sav_grid','Séquence','SÉQUENCE','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,'integerFormatter','integerComparator'),

-- 6
('suivi_commandes_web_sav_grid','Article','ARTICLE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL),

-- 7
('suivi_commandes_web_sav_grid','Statut Ligne','STATUT LIGNE','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL),

-- 8
('suivi_commandes_web_sav_grid','Reference Interne','RÉFÉRENCE INTERNE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,NULL,0,NULL,NULL),

-- 9
('suivi_commandes_web_sav_grid','Bon de Préparation','BON DE PRÉPARATION','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,NULL,NULL),

-- 10
('suivi_commandes_web_sav_grid','Statut Bon de Préparation','STATUT BP','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL),

-- 11
('suivi_commandes_web_sav_grid','Statut PREPS','STATUT PREPS','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,11,NULL,0,NULL,NULL),

-- 12
('suivi_commandes_web_sav_grid','Log Envoyé','LOG ENVOYÉ','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,12,NULL,0,NULL,NULL),

-- 13
('suivi_commandes_web_sav_grid','DATE_RETOUR','DATE RETOUR','date',120,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,13,NULL,0,NULL,NULL),

-- 14
('suivi_commandes_web_sav_grid','Etat commande','ÉTAT COMMANDE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,14,NULL,0,NULL,NULL),

-- 15
('suivi_commandes_web_sav_grid','Reference Commande','RÉFÉRENCE COMMANDE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,15,NULL,0,NULL,NULL),

-- 16
('suivi_commandes_web_sav_grid','Numéro Tracking','N° TRACKING','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,16,NULL,0,NULL,NULL);
