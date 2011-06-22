alter table tr_project add column integration_openerp_id integer;
alter table tr_project add column integration_openerp_synced boolean;
alter table tr_entry add column integration_openerp_id integer;
alter table tr_user add column integration_openerp_id integer;
alter table tr_user add column integration_openerp_synced boolean;
