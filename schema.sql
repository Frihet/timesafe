drop table tr_tag_map;
drop table tr_tag_group;
drop table tr_tag;
drop table tr_entry;
drop table tr_user;
drop table tr_project;
     
create table tr_project
(
	id serial not null primary key,
	egs_id int,
	start_date date,
	external boolean not null default false,
	name varchar(64) not null,
	open boolean not null default true
);

create table tr_user
(
	id serial not null primary key,
	name varchar(64) not null,
	fullname varchar(128) not null,
	deleted boolean not null default false
);
	

create table tr_entry
(
	id serial not null primary key,
	project_id int not null references tr_project(id),
	user_id int not null references tr_user(id),
	minutes int not null,
	perform_date date not null,
	description varchar(16000) not null
);

create table tr_tag_group
(
	id serial not null primary key,
	name varchar(64) not null,
	required boolean not null default false
);	

create table tr_tag
(
	id serial not null primary key,
	name varchar(64) not null,
	visibility int not null,
	project_id int references tr_project(id),
	group_id int references tr_tag_group(id),
	deleted boolean not null default false
);	


create table tr_tag_map
(
	entry_id int not null references tr_entry(id),
	tag_id int not null references tr_tag(id)
);

insert into tr_user(name, fullname) values ('nooslilaxe','Axel Liljencrantz');

insert into tr_project (name) values ('Div7 - Executive'); 
insert into tr_project (name) values ('Div1 - Admin');
insert into tr_project (name) values ('Div2 - Marketing and Sales');
insert into tr_project (name) values ('Div3 - Treasury');
insert into tr_project (name) values ('Div4 - Delivery');
insert into tr_project (name) values ('Div5 - Quality'); 
insert into tr_project (name) values ('Div6 - Ideology'); 
insert into tr_project (name) values ('Div4A:Doozerland');
insert into tr_project (name) values ('Div4G:Curriculum');
insert into tr_project (name) values ('Elkjøp:Greencycle');
insert into tr_project (name) values ('Elkjøp:ESC3');	
insert into tr_project (name) values ('Elkjøp:ELOG');	
insert into tr_project (name) values ('Elkjøp:Ecco');
insert into tr_project (name) values ('Elkjøp:Ecco Statistics module');
insert into tr_project (name) values ('Steen&Strøm:MAC police');
insert into tr_project (name) values ('Ericsson:TLA');
insert into tr_project (name) values ('ColorLine:NBI');
insert into tr_project (name) values ('RealTech:Support');
insert into tr_project (name) values ('Zamboni:Triumf');


insert into tr_tag(name, visibility) values ('Billable',0);
insert into tr_tag(name, visibility) values ('40 % overtime',0);
insert into tr_tag(name, visibility) values ('100 % overtime',0);
insert into tr_tag(name, visibility) values ('Travel',0);




