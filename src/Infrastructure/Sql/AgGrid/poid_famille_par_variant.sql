SELECT * FROM intranet_lcs.aggrid_option where grid_name = 'best_demand_per_style_grid';


delete FROM intranet_lcs.aggrid_option where grid_name = 'best_demand_per_style_grid';
--------

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, compatator)
VALUES


-- 1
('poid_famille_par_variant_grid','FAMILY','FAMILLE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,1,NULL,0,NULL,NULL),

('poid_famille_par_variant_grid','TYPE','TYPE','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,2,NULL,0,NULL,NULL),

('poid_famille_par_variant_grid','VARIANT','VARIANT','string',100,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,3,NULL,0,NULL,NULL),

('poid_famille_par_variant_grid','QUANTITY','QUANTITE','integer',130,1,'agNumberColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,NULL,0,'decimalFormatter','decimalComparator'),

('poid_famille_par_variant_grid','WEIGHT_PERCENT','POIDS','string',130,1,'agTextColumnFilter',
 JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
 NULL,NULL,1,4,'bg-success-light',0,'decimalFormatter','decimalComparator');

--------

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES

    ('best_demand_per_style_grid','ITEMGROUPCODE','GROUPE','string',150,1,'agTextColumnFilter',
     JSON_OBJECT('textAlign','left','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
     NULL,NULL,1,1,NULL,0,NULL,NULL),

    ('best_demand_per_style_grid','QUANTITY','QUANTITE','integer',180,1,'agNumberColumnFilter',
     JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
     NULL,NULL,1,2,'bg-success-light',0,'decimalFormatter','decimalComparator'),

    ('best_demand_per_style_grid','CA','CA','decimal',200,1,'agNumberColumnFilter',
     JSON_OBJECT('textAlign','right','borderRight','0.2px solid #CECECEFF','borderBottom','0.2px solid #CECECEFF'),
     NULL,NULL,1,3,'bg-primary-light',0,'decimalFormatter','decimalComparator');
