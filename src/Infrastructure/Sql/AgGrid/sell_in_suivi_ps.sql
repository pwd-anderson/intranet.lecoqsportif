select * from intranet_lcs.aggrid_option where grid_name = 'sell_in_suivi_ps_grid';

-- Étape 1 : Suppression des anciennes données
DELETE FROM intranet_lcs.aggrid_option
WHERE grid_name = 'sell_in_suivi_ps_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator, editable, cell_editor, cell_editor_params)
VALUES

-- ============ COLONNES D'IDENTIFICATION (texte) ============
('sell_in_suivi_ps_grid','SALESMANN1','SALESMAN 1','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL,NULL,NULL,NULL),

('sell_in_suivi_ps_grid','SALESMANN2','SALESMAN 2','string',140,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL,NULL,NULL,NULL),

('sell_in_suivi_ps_grid','BUSINESS_MODEL','BUSINESS MODEL','string',150,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL,NULL,NULL,NULL),

('sell_in_suivi_ps_grid','REPORTING_DIMENSION','REPORTING DIM.','string',150,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,NULL,NULL,NULL,NULL,NULL),

('sell_in_suivi_ps_grid','DISTRIBUTION_CHANNEL','DISTRIBUTION CHANNEL','string',180,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,5,NULL,0,NULL,NULL,NULL,NULL,NULL),

('sell_in_suivi_ps_grid','GROUP_CODE','GROUP CODE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,6,NULL,0,NULL,NULL,NULL,NULL,NULL),

('sell_in_suivi_ps_grid','SOUS_GROUP_CODE','SUBGROUP CODE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,7,NULL,0,NULL,NULL,NULL,NULL,NULL),

('sell_in_suivi_ps_grid','INDEPENDANT_GROUPMENT','INDEPENDANT GROUPMENT','string',180,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,8,NULL,0,NULL,NULL,NULL,NULL,NULL),

-- ============ CUSTOMER (CODE -> NAME -> CITY) ============
('sell_in_suivi_ps_grid','CUSTOMER_CODE','CUSTOMER CODE','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,9,NULL,0,NULL,NULL,NULL,NULL,NULL),

('sell_in_suivi_ps_grid','CUSTOMER_NAME','CUSTOMER NAME','string',200,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,10,NULL,0,NULL,NULL,NULL,NULL,NULL),

('sell_in_suivi_ps_grid','CITY','CITY','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,11,NULL,0,NULL,NULL,NULL,NULL,NULL),

-- ============ FAMILY / GENDER / SERIESNO ============
('sell_in_suivi_ps_grid','FAMILY','FAMILY','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,12,NULL,0,NULL,NULL,NULL,NULL,NULL),

('sell_in_suivi_ps_grid','GENDER','GENDER','string',100,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,13,NULL,0,NULL,NULL,NULL,NULL,NULL),

('sell_in_suivi_ps_grid','SERIESNO','SERIESNO','string',120,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,14,NULL,0,NULL,NULL,NULL,NULL,NULL),

-- ============ ÉDITABLES : SIMPLE_STATUS + FORECAST ============
('sell_in_suivi_ps_grid','SIMPLE_STATUS','SIMPLE STATUS','string',130,1,'agMultiColumnFilter',
 JSON_OBJECT('borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF','backgroundColor','#fff7e6'),
 NULL,NULL,1,15,NULL,0,NULL,NULL,1,'agSelectCellEditor',JSON_OBJECT('values',JSON_ARRAY('OPEN','COMPLETE'))),

('sell_in_suivi_ps_grid','FORECAST','FORECAST','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF','backgroundColor','#fff7e6'),
 NULL,'sum',1,16,NULL,0,'integerFormatter','integerComparator',1,'agNumberCellEditor',JSON_OBJECT('min',0,'precision',0)),

-- ============ PS ORDERS ============
('sell_in_suivi_ps_grid','PS_ORDER_SS25_EOS','PS ORDER SS25 EOS','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,17,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','PS_ORDER_SS26_EOS','PS ORDER SS26 EOS','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,18,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','PS_ORDER_SS27_TD','PS ORDER SS27 TD','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,19,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','PS_ORDER_SS26_TD','PS ORDER SS26 TD','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,20,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

-- ============ VS_LY_TD ============
('sell_in_suivi_ps_grid','VS_LY_TD','VS LY TD','percent',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,21,'excelPercentColumn',0,'percentFormatter','percentComparator',NULL,NULL,NULL),

-- ============ LFL ============
('sell_in_suivi_ps_grid','SS27_TD_LFL_COMPLETE','SS27 TD LFL COMPLETE','decimal',180,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,22,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','SS26_EOS_LFL','SS26 EOS LFL','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,23,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','COMPLETE_VS_LY_LFL_EOS','COMPLETE VS LY LFL EOS','percent',180,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,24,'excelPercentColumn',0,'percentFormatter','percentComparator',NULL,NULL,NULL),

-- ============ LANDING / VS_SS26 / TARGET / TOGO / FCST_VS_TARGET ============
('sell_in_suivi_ps_grid','LANDING_FCST','LANDING FCST','decimal',140,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,25,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','VS_SS26_EOS','VS SS26 EOS','percent',140,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,26,'excelPercentColumn',0,'percentFormatter','percentComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','TARGET','TARGET','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF','backgroundColor','#fff7e6'),
 NULL,'sum',1,27,NULL,0,'integerFormatter','integerComparator',1,'agNumberCellEditor',JSON_OBJECT('min',0,'precision',0)),

('sell_in_suivi_ps_grid','TOGO_VS_FCST_EN_VALO','TOGO VS FCST (VALO)','decimal',170,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,28,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','FCST_VS_TARGET_EN_VALO','FCST VS TARGET (VALO)','decimal',180,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,29,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

-- ============ COMPLETE / CUST ============
('sell_in_suivi_ps_grid','SS26_EOS_VALO_COMPLETE','SS26 EOS VALO COMPLETE','decimal',190,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,30,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','CUST','#CUST','integer',100,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,31,NULL,0,'integerFormatter','integerComparator',NULL,NULL,NULL),

-- ============ OPEN ============
('sell_in_suivi_ps_grid','TTL_PS_LY','TTL PS LY','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,32,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','CUST_OPEN','CUST OPEN','integer',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,33,NULL,0,'integerFormatter','integerComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','FCST_REPS','FCST REPS','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,34,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','LW_FCST_REPS','LW FCST REPS','decimal',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,35,'bg-info-light',0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','VS_LW','VS LW','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,36,'bg-info-light',0,NULL,NULL,NULL,NULL,NULL),

('sell_in_suivi_ps_grid','FCST_SALES_VS_EOS_SS26','FCST SALES VS EOS SS26','percent',190,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,37,'excelPercentColumn',0,'percentFormatter','percentComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','FCST_SS27_LFL','FCST SS27 LFL','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,38,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','DIFF','DIFF','decimal',120,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,39,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

-- ============ COMPLETE / EOS FINAL ============
('sell_in_suivi_ps_grid','SS27_COMPLETE','SS27 COMPLETE','decimal',150,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,40,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','LY_COMPLETE_EOS','LY COMPLETE EOS','decimal',160,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,'sum',1,41,NULL,0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','EVO_COMPLETE_VS_LY','EVO COMPLETE VS LY','percent',170,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,42,'excelPercentColumn',0,'percentFormatter','percentComparator',NULL,NULL,NULL),

-- ============ BOH ============
('sell_in_suivi_ps_grid','BOH_FIGE_LW','BOH FIGE LW','decimal',140,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,43,'bg-info-light',0,'decimalFormatter','decimalComparator',NULL,NULL,NULL),

('sell_in_suivi_ps_grid','EVO_BOH_VS_LW','EVO BOH VS LW','string',140,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,44,'bg-info-light',0,NULL,NULL,NULL,NULL,NULL);