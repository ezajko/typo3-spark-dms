#
# Table structure for table 'tx_sparkdms_domain_model_document'
#
CREATE TABLE tx_sparkdms_domain_model_document (
    uid int(10) unsigned NOT NULL AUTO_INCREMENT,
    pid int(10) unsigned NOT NULL DEFAULT 0,
    tstamp int(10) unsigned NOT NULL DEFAULT 0,
    crdate int(10) unsigned NOT NULL DEFAULT 0,
    deleted smallint(5) unsigned NOT NULL DEFAULT 0,
    hidden smallint(5) unsigned NOT NULL DEFAULT 0,
    starttime int(10) unsigned NOT NULL DEFAULT 0,
    endtime int(10) unsigned NOT NULL DEFAULT 0,
    sorting int(11) NOT NULL DEFAULT 0,
    sys_language_uid int(11) NOT NULL DEFAULT 0,
    l10n_parent int(10) unsigned NOT NULL DEFAULT 0,
    l10n_state text DEFAULT NULL,
    l10n_diffsource mediumblob DEFAULT NULL,
    t3ver_oid int(10) unsigned NOT NULL DEFAULT 0,
    t3ver_wsid int(10) unsigned NOT NULL DEFAULT 0,
    t3ver_state smallint(6) NOT NULL DEFAULT 0,
    t3ver_stage int(11) NOT NULL DEFAULT 0,
    title varchar(255) NOT NULL DEFAULT '',
    type int(10) unsigned NOT NULL DEFAULT 0,
    category int(10) unsigned NOT NULL DEFAULT 0,
    document_date bigint(20) NOT NULL DEFAULT 0,
    is_protected smallint(5) unsigned NOT NULL DEFAULT 0,
    versions int(10) unsigned NOT NULL DEFAULT 0,
    uuid varchar(36) NOT NULL DEFAULT '',
    registry_number varchar(255) NOT NULL DEFAULT '',
    related_documents int(10) unsigned NOT NULL DEFAULT 0,
    description longtext DEFAULT NULL,
    related_by int(10) unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (uid),
    KEY parent (pid,deleted,hidden),
    KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);

#
# Table structure for table 'tx_sparkdms_domain_model_documentcategory'
#
CREATE TABLE tx_sparkdms_domain_model_documentcategory (
    uid int(10) unsigned NOT NULL AUTO_INCREMENT,
    pid int(10) unsigned NOT NULL DEFAULT 0,
    tstamp int(10) unsigned NOT NULL DEFAULT 0,
    crdate int(10) unsigned NOT NULL DEFAULT 0,
    deleted smallint(5) unsigned NOT NULL DEFAULT 0,
    hidden smallint(5) unsigned NOT NULL DEFAULT 0,
    sorting int(11) NOT NULL DEFAULT 0,
    sys_language_uid int(11) NOT NULL DEFAULT 0,
    l10n_parent int(10) unsigned NOT NULL DEFAULT 0,
    l10n_state text DEFAULT NULL,
    l10n_diffsource mediumblob DEFAULT NULL,
    t3ver_oid int(10) unsigned NOT NULL DEFAULT 0,
    t3ver_wsid int(10) unsigned NOT NULL DEFAULT 0,
    t3ver_state smallint(6) NOT NULL DEFAULT 0,
    t3ver_stage int(11) NOT NULL DEFAULT 0,
    title varchar(255) NOT NULL DEFAULT '',
    parent int(10) unsigned NOT NULL DEFAULT 0,
    slug text DEFAULT NULL,
    uuid varchar(36) NOT NULL DEFAULT '',
    allowed_be_groups int(10) unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (uid),
    KEY parent (pid,deleted,hidden),
    KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);

#
# Table structure for table 'tx_sparkdms_domain_model_documenttype'
#
CREATE TABLE tx_sparkdms_domain_model_documenttype (
    uid int(10) unsigned NOT NULL AUTO_INCREMENT,
    pid int(10) unsigned NOT NULL DEFAULT 0,
    tstamp int(10) unsigned NOT NULL DEFAULT 0,
    crdate int(10) unsigned NOT NULL DEFAULT 0,
    deleted smallint(5) unsigned NOT NULL DEFAULT 0,
    hidden smallint(5) unsigned NOT NULL DEFAULT 0,
    sorting int(11) NOT NULL DEFAULT 0,
    sys_language_uid int(11) NOT NULL DEFAULT 0,
    l10n_parent int(10) unsigned NOT NULL DEFAULT 0,
    l10n_state text DEFAULT NULL,
    l10n_diffsource mediumblob DEFAULT NULL,
    t3ver_oid int(10) unsigned NOT NULL DEFAULT 0,
    t3ver_wsid int(10) unsigned NOT NULL DEFAULT 0,
    t3ver_state smallint(6) NOT NULL DEFAULT 0,
    t3ver_stage int(11) NOT NULL DEFAULT 0,
    title varchar(255) NOT NULL DEFAULT '',
    slug text DEFAULT NULL,
    uuid varchar(36) NOT NULL DEFAULT '',
    description longtext DEFAULT NULL,
    PRIMARY KEY (uid),
    KEY parent (pid,deleted,hidden),
    KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);

#
# Table structure for table 'tx_sparkdms_domain_model_documentversion'
#
CREATE TABLE tx_sparkdms_domain_model_documentversion (
    uid int(10) unsigned NOT NULL AUTO_INCREMENT,
    pid int(10) unsigned NOT NULL DEFAULT 0,
    tstamp int(10) unsigned NOT NULL DEFAULT 0,
    crdate int(10) unsigned NOT NULL DEFAULT 0,
    deleted smallint(5) unsigned NOT NULL DEFAULT 0,
    hidden smallint(5) unsigned NOT NULL DEFAULT 0,
    sorting int(11) NOT NULL DEFAULT 0,
    sys_language_uid int(11) NOT NULL DEFAULT 0,
    l10n_parent int(10) unsigned NOT NULL DEFAULT 0,
    l10n_state text DEFAULT NULL,
    l10n_diffsource mediumblob DEFAULT NULL,
    t3ver_oid int(10) unsigned NOT NULL DEFAULT 0,
    t3ver_wsid int(10) unsigned NOT NULL DEFAULT 0,
    t3ver_state smallint(6) NOT NULL DEFAULT 0,
    t3ver_stage int(11) NOT NULL DEFAULT 0,
    document int(10) unsigned NOT NULL DEFAULT 0,
    version_label varchar(255) NOT NULL DEFAULT '',
    file int(10) unsigned NOT NULL DEFAULT 0,
    uuid varchar(36) NOT NULL DEFAULT '',
    created_at bigint(20) NOT NULL DEFAULT 0,
    PRIMARY KEY (uid),
    KEY parent (pid,deleted,hidden),
    KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);

#
# Table structure for table 'tx_sparkdms_documentcategory_mm'
#
CREATE TABLE tx_sparkdms_documentcategory_mm (
    uid_local int(10) unsigned NOT NULL DEFAULT 0,
    uid_foreign int(10) unsigned NOT NULL DEFAULT 0,
    sorting int(10) unsigned NOT NULL DEFAULT 0,
    sorting_foreign int(10) unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (uid_local,uid_foreign),
    KEY uid_local (uid_local),
    KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_sparkdms_document_related_mm'
#
CREATE TABLE tx_sparkdms_document_related_mm (
    uid_local int(10) unsigned NOT NULL DEFAULT 0,
    uid_foreign int(10) unsigned NOT NULL DEFAULT 0,
    sorting int(10) unsigned NOT NULL DEFAULT 0,
    sorting_foreign int(10) unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (uid_local,uid_foreign),
    KEY uid_local (uid_local),
    KEY uid_foreign (uid_foreign)
);
