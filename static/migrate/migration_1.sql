
create table tr_project_class
(
	id serial not null primary key,
	name varchar(64) not null,
	deleted boolean not null default false
);

create table tr_project_project_class
(
	id serial not null primary key,
	project_id int not null references tr_project(id),
	project_class_id int not null references tr_project_class(id)
);	

insert into tr_project_class (name) values ('Internal');
insert into tr_project_class (name) values ('External');
insert into tr_project_class (name) values ('Technical');
insert into tr_project_class (name) values ('Administrative');

insert into tr_project_project_class (project_id, project_class_id) 
   select id, (select id from tr_project_class where name = 'Internal')
   from tr_project where external=false;

insert into tr_project_project_class (project_id, project_class_id) 
   select id, (select id from tr_project_class where name = 'External')
   from tr_project where external=true;


alter table tr_project drop column external;

alter table tr_tag add column project_class_id int references tr_project_class(id);
update tr_tag set project_class_id = 2 where visibility=2;
update tr_tag set project_class_id = 1 where visibility=1;
alter table tr_tag drop column visibility;


