SELECT * FROM intranet_lcs.aggrid_option where grid_name = 'best_demand_per_style_grid';
delete FROM intranet_lcs.aggrid_option where grid_name = 'best_demand_per_style_grid';

INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES
('best_demand_per_style_grid','ITEMGROUPCODE','GROUPE','string',240,1,'agMultiColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,1,NULL,0,NULL,NULL),
('best_demand_per_style_grid','QUANTITY','QUANTITE','integer',240,1,'agNumberColumnFilter','{"fontSize": "16px", "textAlign": "right", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,'agg_func',1,2,'bg-success-light',0,'decimalFormatter','decimalComparator'),
('best_demand_per_style_grid','CA','CA','decimal',240,1,'agNumberColumnFilter','{"fontSize": "16px", "textAlign": "right", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,'agg_func',1,3,'bg-primary-light',0,'decimalFormatter','decimalComparator');
