INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES
('tcd_cmd_non_soldees_grid','TYPE_COMMANDE','TYPE COMMANDE','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),

('tcd_cmd_non_soldees_grid','DATE_CREATION','DATE CRÉATION','date',130,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),

('tcd_cmd_non_soldees_grid','NON_TRANSMIS','NON TRANSMIS','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,3,NULL,0,'integerFormatter','integerComparator'),

('tcd_cmd_non_soldees_grid','A_ENVOYER','À ENVOYER','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,4,NULL,0,'integerFormatter','integerComparator'),

('tcd_cmd_non_soldees_grid','ENVOYE','ENVOYÉ','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,5,NULL,0,'integerFormatter','integerComparator'),

('tcd_cmd_non_soldees_grid','INTEGRATION','INTÉGRATION','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,6,NULL,0,'integerFormatter','integerComparator'),

('tcd_cmd_non_soldees_grid','ANNULATION','ANNULATION','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,7,NULL,0,'integerFormatter','integerComparator'),

('tcd_cmd_non_soldees_grid','LANCEMENT_EN_PREPARATION','LANCEMENT EN PRÉPARATION','integer',180,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,8,NULL,0,'integerFormatter','integerComparator'),

('tcd_cmd_non_soldees_grid','FIN_DE_PREPARATION','FIN DE PRÉPARATION','integer',160,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,9,NULL,0,'integerFormatter','integerComparator'),

('tcd_cmd_non_soldees_grid','TOTAL','TOTAL','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,10,'bg-success-light',0,'integerFormatter','integerComparator');