-- Vérification (optionnel)
-- SELECT * FROM intranet_lcs.aggrid_option WHERE grid_name = 'suivi_perf_wholesale_fr_grid';

DELETE FROM intranet_lcs.aggrid_option
WHERE grid_name = 'suivi_perf_wholesale_fr_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator, editable, cell_editor, cell_editor_params)
VALUES

('suivi_perf_wholesale_fr_grid','REPRESENTANT','Représentant','string',200,1,'agTextColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL,NULL,NULL,NULL),

('suivi_perf_wholesale_fr_grid','PS_ORDER_SS26_EOS','SS26 EOS','decimal',160,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('suivi_perf_wholesale_fr_grid','OBJECTIF_PS_SS27','Objectif SS27','decimal',160,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','backgroundColor','#ffff00','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('suivi_perf_wholesale_fr_grid','PS_ORDER_SS27_TD','SS27 TD','decimal',160,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('suivi_perf_wholesale_fr_grid','EVOLUTION_VS_SS26','Evol vs SS26','percent',140,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,'excelPercentColumn',0,'percentFormatter','percentComparator',NULL,NULL,NULL),

('suivi_perf_wholesale_fr_grid','POURCENTAGE_ATTEINTE','% Atteinte obj.','percent',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,'excelPercentColumn',0,'percentFormatter','percentComparator',NULL,NULL,NULL);

-- NOTE : ROW_TYPE n'est PAS dans aggrid_option. Il est présent dans le JSON retourné par le service
-- et lu directement via params.data.ROW_TYPE dans les rowClassRules du template.
-- AgGridColumnBuilder n'expose pas visible=0 comme hide:true, donc on évite l'ajout ici.
