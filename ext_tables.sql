#
# Table structure for table 'tx_vcc_log'
#
CREATE TABLE tx_vcc_log (
    uid int(11) NOT NULL auto_increment,
    pid int(11) unsigned DEFAULT '0' NOT NULL,

    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    be_user int(11) unsigned DEFAULT '0' NOT NULL,

    type tinyint(4) unsigned DEFAULT '0' NOT NULL,
    message text,
    log_data text,

    caller text,
    hash varchar(255) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY hash (hash)
);
