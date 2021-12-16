.. include:: ../../Includes.rst.txt

.. _characterslist:

Characters List
===============

Let's start with the list page. The first step is to create a new content element with the “RSRQ piquery” plugin record.

At this point we have to construct a query to retrieve the list of the fe_users which are not deleted and, for every character, the group name which the character belongs to. Use the tab "Database" to enter the following SQL:

::
   SELECT
      fe_users.uid,
      fe_users.pid,
      fe_users.crdate,
      fe_users.name,
      fe_users.address,
      fe_users.title,
      fe_groups.title AS race

   FROM fe_users

   LEFT OUTER JOIN fe_groups ON fe_users.usergroup = fe_groups.uid

   WHERE fe_users.deleted = 0

vraag van rw
------------
>> vraag (RW) waarom is het veld columnNames verplicht?
>> ik heb columnNames even niet verplicht gesteld in rsrq en kijk wat er gebeur
>> -- this comment is to be removed at a later time --

Going to the “Results” tab, we can specify how many results per page we want, a text in case of no results, a summary and a caption to describe the results. Finally we can decide if we want to provide to the site users a link to download the results in a CSV format. The other tabs will be described in the following paragraphs, now save and close the content element.

vraag van rw
------------
ik zie hier de term "results tab" in de oorspronkelijke documentatie,
en Resultaten in onze extensie. wellicht handig om de oorspronkelijke naam
results tab te her/gebruiken?

If we open the “Characters list” page in the FrontEnd we'll see the list of fe_users records stored in our table (remember to insert at least one fe_users record if you want to see something :-)) Very easy!!!
