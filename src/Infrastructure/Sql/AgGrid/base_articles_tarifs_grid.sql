-- À charger avec : mysql --default-character-set=utf8mb4 (dev, préprod, prod)
DELETE FROM intranet_lcs.aggrid_option WHERE grid_name = 'base_articles_tarifs_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator, editable, cell_editor, cell_editor_params)
VALUES
('base_articles_tarifs_grid','CODE_ARTICLE','ARTICLE','string',110,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','DESCRIPTION_ARTICLE','DESCRIPTION ARTICLE','string',220,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','CODE_MODELE','CODE MODÈLE','string',110,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','DESCRIPTION_MODELE','DESCRIPTION MODÈLE','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','NOOS','NOOS','string',90,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','COLLECTION','COLLECTION','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','COLLECTION_DROPPEE','COLLECTION DROPPÉE','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','MOTIF_DROPPEE','MOTIF DROPPÉE','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','FAMILLE','FAMILLE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','GENRE','GENRE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','GROUPE_AGE','GROUPE ÂGE','string',110,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,11,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','ORIGINE','ORIGINE','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,12,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','FOURNISSEUR','FOURNISSEUR','string',110,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,13,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','NOM_FOURNISSEUR','NOM FOURNISSEUR','string',180,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,14,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','DATE_INTRODUCTION','DATE D''INTRODUCTION','date',130,1,'agDateColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,15,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','SEGMENT_OFFRE','SEGMENT OFFRE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,16,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','SOUS_CATEGORY','SOUS-CATÉGORIE','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,17,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','SMUCustomer','SMU CUSTOMER','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,18,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','Division','DIVISION','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,19,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','ProductLineCode','PRODUCT LINE CODE','string',150,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,20,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','ROYALTIES','ROYALTIES','string',110,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,21,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','PRIX_ACHAT','PRIX ACHAT','decimal',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,22,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','DEVISE_ACHAT','DEVISE ACHAT','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('textAlign','center','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,23,NULL,0,NULL,NULL,NULL,NULL,NULL),

('base_articles_tarifs_grid','PRIX_ACHAT_EUR','PRIX ACHAT EUR','decimal',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,24,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','FRAIS_TRANSPORT_EUR','FRAIS TRANSPORT EUR','decimal',140,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,25,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','TAUX_DOUANE','TAUX DOUANE (%)','decimal',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,26,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','TAUX_COMMISSION','TAUX COMMISSION (%)','decimal',140,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,27,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','TAUX_TAXE_CUIR','TAUX TAXE CUIR (%)','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,28,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','LANDED_COST_EUR','LANDED COST EUR','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,29,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','TARIF_CSEU','TARIF CSEU','decimal',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,30,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','TARIF_FOEU','TARIF FOEU','decimal',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,31,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','TARIF_SRP_EUR','TARIF SRP EUR','decimal',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,32,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','TARIF_SRP_USD','TARIF SRP USD','decimal',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,33,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','TARIF_WSPEUR','TARIF WSPEUR','decimal',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,34,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','TARIF_EUROPRICE','TARIF EUROPRICE','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,35,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','TARIF_INTEXPUSD','TARIF INTEXPUSD','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,36,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','TARIF_T1USD','TARIF T1USD','decimal',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,37,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('base_articles_tarifs_grid','TARIF_AMZEU','TARIF AMZEU','decimal',110,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,38,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL);
