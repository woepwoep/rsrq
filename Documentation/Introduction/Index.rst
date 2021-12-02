.. include:: ../Includes.txt

.. |extensions-screenshot-8x|      image:: /Images/Extension_Screenshot_8x.png
.. :border: 0
.. :align: left
.. :name: Example Screenshot of phpMyAdmin in TYPO3 8.x Backend

============
Introduction
============

This extension provides the third party database integration tool »rsrq« integrated and preconfigured for the TYPO3 installation and database. 
The extension is PHP 7.4 compatible. It is greatly inspired by the existing extension wfqbe, which unfortunately is no longer maintained and 
does not work for the newer TYPO3 versions. The extension is completely rebuild and is now based on Extbase and Fluid.

What does it do?
================

This extension allows you to connect to a table in the current DBMS and to insert, search or retrieve data from this table. The
extension provides you with two different plugins. The plugins allow you to:

*  create a query for retrieving data in a List (table) format
*  create a search form for searching in an existing query
*  create a module to insert, edit data into a specific table or delete specific data from the table

All the FE outputs are template based, this means that it is possible to create a custom template to show your query results. 
The template are fully fluid based, so it is relative easy to extend or change them.


.. _Screenshots:

**Screenshots**
^^^^^^^^^^^^^^^

Query creation with the wizard

|img-1|

Results displaying

|img-2| |img-3|

Insert form creation with the wizard

|img-4|


.. _Support:

Support
^^^^^^^

You can get support from the authors by mailing them at info@red-seadog.com
