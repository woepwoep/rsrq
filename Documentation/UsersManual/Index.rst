.. include:: ../Includes.rst.txt

.. _usersmanual:

Users Manual
============

In this chapter of the manual we are going to see how to setup a database management tool in the FrontEnd. This is only a simple example of what you can obtain with this extension.

First let's describe what we are going to do. We want to provide to the website users the possibility to see the list of the characters of our preferred telefilm, to search for the preferred character, to see a detailed page for each character, to insert a new character, edit an existing character and to delete an existing character from the database.

First of all we have to configure the plugin (see “Administration” chapter) and then the first step is to create the page tree:

.. figure:: ../Images/startrek.png
   :alt: Database Startrek

      Database Startrek

As you can see we have to create 5 pages. The last one is a system folder where we can store our credentials for the DB connections and where we have to store the characters records (fe_user records).

In this case we don't need to create a  DB credentials record due to the fact that we are going to use the fe_users table to store our characters (and this table is located in the TYPO3 db which is available by default). In the same way, after creating any DB credentials you need, you can use a different table in a different DB in a different DBMS, the only limit is that ADODB has to support the DBMS you want to use.

In the “Characters DB” page we are going to store the characters. Every character is stored in the fe_users table and has a relation with the fe_groups table where the groups are stored (in our example every group represents a race and each race can have a number of characters).

The “Characters list” page is the page where the visitors of our website can see the list of the characters.

The “Character details” page is the page where it's possible to see the details of the individual characters (you can take a look to the screenshots in the previous pages of this manual). If you pay attention to the icon of the “Character details” page, you'll notice that this page is a “not in menu” page, this is due to the fact that if you don't provide a character id, this page doesn't show anything, like the single page for the tt_news extension.

The “Search” page contains a search form to search inside the characters db, just to restrict the list query we are going to construct for the list page.

Finally, the “Characters management” page provides the form to insert, edit and delete characters in the db. In the following pages we'll see that it's possible to create these functionalities with a good knowledge of SQL and TypoScript and a small knowledge of HTML, no PHP knowledge is needed (but it can be necessary to customize some options).

.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   CharactersList/Index
   Filters/Index
