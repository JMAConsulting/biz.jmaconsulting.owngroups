CREATE TABLE IF NOT EXISTS `civicrm_preference_group` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique PreferenceGroup ID',
     `group_id` int unsigned    COMMENT 'FK to Group',
     `is_preference` int unsigned    COMMENT 'Is this group shown on preference page' 
,
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_preference_group_group_id FOREIGN KEY (`group_id`) REFERENCES `civicrm_group`(`id`) ON DELETE CASCADE  
);
