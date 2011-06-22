import xmlrpclib
import psycopg2


timesafe_dsn = "dbname=timesafe host=localhost user=timesafe password=timesafe"
openerp_user = 'admin'
openerp_pwd = 'admin'
openerp_dbname = 'xyzzy'
sync_delete = True

def parent_project_to_path(project_id):
    path = []
    project = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "project.project", 'read', project_id)
    if project["parent_id"] and project["parent_id"][0] != project_id:
        parent_ids = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "project.project", 'search', [("analytic_account_id", "=", project["parent_id"][0])])
        if parent_ids:
            assert len(parent_ids) == 1
            path.extend(parent_project_to_path(parent_ids[0]))
    path.append(project["name"])
    return path

def project_to_path(project_id):
    path = []
    project = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "project.project", 'read', project_id)
    analytic_account = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "account.analytic.account", 'read', project['analytic_account_id'][0])
    if analytic_account["partner_id"]:
        path.append(analytic_account["partner_id"][1])
    else:
        path.append(project["company_id"][1])
    path.extend(parent_project_to_path(project_id))
    return path

def project_members(project_id):
    project = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "project.project", 'read', project_id)
    yield project['user_id'][0], project['user_id'][1]
    for id in project["members"]:
        user = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "res.users", "read", id)
        yield id, user["name"]

def project_classes(project_id):
    project = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "project.project", 'read', project_id)
    analytic_account = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "account.analytic.account", 'read', project['analytic_account_id'][0])
    if analytic_account["partner_id"]:
        return ['External']
    else:
        return ['Internal']

def task_to_path(task_id):
    path = []
    task = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "project.task", 'read', task_id)
    path.extend(project_to_path(task["project_id"][0]))
    path.append(task["name"])
    return path

def task_members(task_id):
    task = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "project.task", 'read', task_id)
    yield task['manager_id'][0], task['manager_id'][1]
    yield task['user_id'][0], task['user_id'][1]
    for x in project_members(task["project_id"][0]):
        yield x

def task_classes(task_id):
    task = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "project.task", 'read', task_id)
    for x in project_classes(task["project_id"][0]):
        yield x

def list_projects():
    ids = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "project.project", 'search', [])
    for id in ids:
        yield id, project_to_path(id)

def list_tasks():
    ids = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "project.task", 'search', [])
    for id in ids:
        yield id, task_to_path(id)

def list_users():
    ids = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "res.users", 'search', [])
    for id in ids:
        usr = openerp_sock.execute(openerp_dbname, openerp_uid, openerp_pwd, "res.users", 'read', id)
        yield id, usr['login'], usr['name'], usr['password']


timesafe_sock = psycopg2.connect(timesafe_dsn).cursor()

openerp_sock = xmlrpclib.ServerProxy('http://localhost:8069/xmlrpc/common')
openerp_uid = openerp_sock.login(openerp_dbname, openerp_user, openerp_pwd)
openerp_sock = xmlrpclib.ServerProxy('http://localhost:8069/xmlrpc/object')


# Sync users
for id, login, name, password in list_users():
     timesafe_sock.execute("select count(*) from tr_user where integration_openerp_id = %(id)s", {"id": id})
     if timesafe_sock.next()[0] > 0:
         timesafe_sock.execute("update tr_user set name = %(name)s, fullname = %(fullname)s, password = %(password)s where integration_openerp_id = %(id)s", {"id": id, "name": login, "fullname": name, "password": password})
     else:
         timesafe_sock.execute("insert into tr_user (name, fullname, password, integration_openerp_id) values (%(name)s, %(fullname)s, %(password)s, %(id)s)", {"id": id, "name": login, "fullname": name, "password": password})
if sync_delete:
    timesafe_sock.execute("delete from tr_project_user where user_id in (select id from tr_user where integration_openerp_id is null)")
    timesafe_sock.execute("delete from tr_tag_map where entry_id in (select id from tr_entry where user_id in (select id from tr_user where integration_openerp_id is null))")
    timesafe_sock.execute("delete from tr_entry where user_id in (select id from tr_user where integration_openerp_id is null)")
    timesafe_sock.execute("delete from tr_user where id in (select id from tr_user where integration_openerp_id is null)")


# Sync projects
for id, name in list_tasks():
    name = "/".join(name)
    timesafe_sock.execute("select count(*) from tr_project where integration_openerp_id = %(id)s", {"id": id})
    if timesafe_sock.next()[0] > 0:
        timesafe_sock.execute("update tr_project set name = %(name)s where integration_openerp_id = %(id)s", {"id": id, "name": name})
    else:
        timesafe_sock.execute("insert into tr_project (name, integration_openerp_id) values (%(name)s, %(id)s)", {"id": id, "name": name})

    timesafe_sock.execute("delete from tr_project_user where project_id = (select id from tr_project where integration_openerp_id = %(project_id)s)", {"project_id": id})
    for user_id, name in dict(task_members(id)).iteritems():
        timesafe_sock.execute("insert into tr_project_user (project_id, user_id) select tr_project.id, tr_user.id from tr_project, tr_user where tr_project.integration_openerp_id = %(project_id)s and tr_user.integration_openerp_id = %(user_id)s", {"project_id": id, "user_id": user_id})

    timesafe_sock.execute("delete from tr_project_project_class where project_id = (select id from tr_project where integration_openerp_id = %(project_id)s)", {"project_id": id})
    for class_name in set(task_classes(id)):
        timesafe_sock.execute("insert into tr_project_project_class (project_id, project_class_id) select tr_project.id, tr_project_class.id from tr_project, tr_project_class where tr_project.integration_openerp_id = %(project_id)s and tr_project_class.name = %(class_name)s", {"project_id": id, "class_name": class_name})
        

if sync_delete:
    timesafe_sock.execute("delete from tr_tag where project_id in (select id from tr_project where integration_openerp_id is null)")
    timesafe_sock.execute("delete from tr_project_user where project_id in (select id from tr_project where integration_openerp_id is null)")
    timesafe_sock.execute("delete from tr_tag_map where entry_id in (select id from tr_entry where project_id in (select id from tr_project where integration_openerp_id is null))")
    timesafe_sock.execute("delete from tr_entry where project_id in (select id from tr_project where integration_openerp_id is null)")
    timesafe_sock.execute("delete from tr_project_project_class where project_id in (select id from tr_project where integration_openerp_id is null)")
    timesafe_sock.execute("delete from tr_project where integration_openerp_id is null")

timesafe_sock.execute("commit")

