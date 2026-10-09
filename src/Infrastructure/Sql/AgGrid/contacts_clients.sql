SELECT * FROM intranet_lcs.aggrid_option where grid_name = 'contacts_clients_grid';
delete FROM intranet_lcs.aggrid_option where grid_name = 'contacts_clients_grid';

-- Stat Contacts Clients (ADV). A charger avec --default-character-set=utf8mb4 (accents).
INSERT INTO intranet_lcs.aggrid_option
(grid_name, field, header_name, type, min_width, sortable, filter, cell_style, flex, agg_func, visible, order_index, cell_class, computed, value_formatter, comparator)
VALUES
('contacts_clients_grid','REPRESENTANT_1','Représentant 1','string',180,1,'agSetColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,1,NULL,0,NULL,NULL),
('contacts_clients_grid','REPRESENTANT_2','Représentant 2','string',180,1,'agSetColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,2,NULL,0,NULL,NULL),
('contacts_clients_grid','BUSINESS_MODEL_1','Business Model','string',200,1,'agSetColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,3,NULL,0,NULL,NULL),
('contacts_clients_grid','GROUPEMENT_INDEPENDANT','Groupement Indépendant','string',200,1,'agSetColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,4,NULL,0,NULL,NULL),
('contacts_clients_grid','CODE_CLIENT','Code Client','string',130,1,'agTextColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,5,NULL,0,NULL,NULL),
('contacts_clients_grid','NOM_CLIENT','Nom Client','string',260,1,'agTextColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,6,NULL,0,NULL,NULL),
('contacts_clients_grid','STATUT_CLIENT','Statut Client','string',130,1,'agSetColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,7,NULL,0,NULL,NULL),
('contacts_clients_grid','LIBELLE_FONCTION','Fonction','string',300,1,'agSetColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,8,NULL,0,NULL,NULL),
('contacts_clients_grid','CODE_CONTACT','Code Contact','string',130,1,'agTextColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,9,NULL,0,NULL,NULL),
('contacts_clients_grid','NOM_CONTACT','Nom Contact','string',180,1,'agTextColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,10,NULL,0,NULL,NULL),
('contacts_clients_grid','PRENOM_CONTACT','Prénom Contact','string',160,1,'agTextColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,11,NULL,0,NULL,NULL),
('contacts_clients_grid','TEL_CONTACT','Téléphone','string',150,1,'agTextColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,12,NULL,0,NULL,NULL),
('contacts_clients_grid','EMAIL','Email','string',280,1,'agTextColumnFilter','{"fontSize": "16px", "textAlign": "left", "borderRight": "0.2px solid #CECECEFF", "borderBottom": "0.2px solid #CECECEFF"}',NULL,NULL,1,13,NULL,0,NULL,NULL);
