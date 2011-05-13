drop table tr_project_project_class;
drop table tr_tag_map;
drop table tr_tag;
drop table tr_tag_group;
drop table tr_project_class;
drop table tr_entry;
drop table tr_project_user;
drop table tr_user;
drop table tr_project;
     
create table tr_project
(
	id serial not null primary key,
	egs_id int,
	start_date date,
	name varchar(256) not null,
	open boolean not null default true,
	is_resource boolean default false
);

create table tr_user
(
	id serial not null primary key,
	name varchar(256) not null,
	password varchar(256) not null,
	fullname varchar(256) not null,
	deleted boolean not null default false
);
	
create table tr_project_user
(
	id serial not null primary key,
	project_id int not null references tr_project(id),
	user_id int not null references tr_user(id)
);


create table tr_entry
(
	id serial not null primary key,
	project_id int not null references tr_project(id),
	user_id int not null references tr_user(id),
	minutes int not null,
	perform_date date not null,
	description varchar(65536) not null
);


create table tr_project_class
(
	id serial not null primary key,
	name varchar(64) not null,
	color_r int,
	color_g int,
	color_b int,
	deleted boolean not null default false
);

create table tr_tag_group
(
	id serial not null primary key,
	name varchar(256) not null,
	required boolean not null default false
);	

create table tr_tag
(
	id serial not null primary key,
	name varchar(256) not null,
	color_r int,
	color_g int,
	color_b int,
	visibility integer,
	project_class_id int references tr_project_class(id),
	project_id int references tr_project(id),
	group_id int references tr_tag_group(id),
	recommended boolean not null default false,
	deleted boolean not null default false
);


create table tr_tag_map
(
	entry_id int not null references tr_entry(id),
	tag_id int not null references tr_tag(id)
);

create table tr_project_project_class
(
	id serial not null primary key,
	project_id int not null references tr_project(id),
	project_class_id int not null references tr_project_class(id)
);	

create table tr_report
(
	id serial not null primary key,
	name varchar(256) not null,
	query varchar not null
);

insert into tr_user(name, password, fullname) values ('admin',md5('admin'),'Admin Adminsson');

insert into tr_tag(name, color_r, color_g, color_b, visibility) values ('Billable',0,255,0,0);
insert into tr_tag(name, color_r, color_g, color_b, visibility) values ('40 % overtime',127,127,127,0);
insert into tr_tag(name, color_r, color_g, color_b, visibility) values ('100 % overtime',191,191,191,0);
insert into tr_tag(name, visibility) values ('Travel',0);

insert into tr_project_class (name) values ('Internal');
insert into tr_project_class (name) values ('External');
insert into tr_project_class (name) values ('Technical');
insert into tr_project_class (name) values ('Administrative');

