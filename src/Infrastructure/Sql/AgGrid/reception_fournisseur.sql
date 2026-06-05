delete from intranet_lcs.aggrid_option where grid_name = 'reception_fournisseur_grid';
select * from intranet_lcs.aggrid_option where grid_name = 'reception_fournisseur_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator, editable, cell_editor, cell_editor_params)
VALUES
-- ============ COLONNES D'IDENTIFICATION (texte / date) ============
('reception_fournisseur_grid','SITE','SITE','string',110,1,'agTextColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL,NULL,NULL,NULL),

('reception_fournisseur_grid','DATE_RECEPTION','DATE RECEPTION','date',140,1,'agDateColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,'dateFormatter','dateComparator',NULL,NULL,NULL),

('reception_fournisseur_grid','POHNUM_0','N° COMMANDE','string',150,1,'agTextColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL,NULL,NULL,NULL),

('reception_fournisseur_grid','FAMILLE','FAMILLE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL,NULL,NULL,NULL),

('reception_fournisseur_grid','COLLECTION','COLLECTION','string',180,1,'agTextColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL,NULL,NULL,NULL),

('reception_fournisseur_grid','GENRE','GENRE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL,NULL,NULL,NULL),

('reception_fournisseur_grid','ARTICLE','ARTICLE','string',160,1,'agTextColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL,NULL,NULL,NULL),

-- ============ QUANTITE ============
('reception_fournisseur_grid','QUANTITE','QUANTITE','integer',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,8,NULL,0,'integerFormatter','integerComparator',NULL,NULL,NULL),

-- ============ INFOS PRIX (à la fin) ============
('reception_fournisseur_grid','PRIX_UNITAIRE','PRIX UNITAIRE','decimal',140,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('reception_fournisseur_grid','DEVISE','DEVISE','string',90,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL,NULL,NULL,NULL),

('reception_fournisseur_grid','MONTANT_TOT_LIGNE','MONTANT TOTAL','decimal',160,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,11,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL);