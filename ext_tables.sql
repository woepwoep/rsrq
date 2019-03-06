#
# Table structure for table 'tx_rsrq_domain_model_query'
#
CREATE TABLE tx_rsrq_domain_model_query (
    uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,

    title tinytext NOT NULL,
    description text NOT NULL,
    query text NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);
