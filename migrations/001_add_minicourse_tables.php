<?php
class AddMiniCourseTables extends DBMigration {

    public function description () {
        return 'create tables for the minicourse-plugin';
    }

    public function up () {
        $db = DBManager::get();
        $db->exec("CREATE  TABLE `minicourse` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(64) NULL ,
            `parent_id` INT NULL ,
            `seminar_id` VARCHAR(32) NULL ,
            `title` VARCHAR(255) NULL ,
            `position` INT NULL DEFAULT 0 ,
            `json_data` MEDIUMTEXT NULL COMMENT 'JSON' ,
            `chdate` INT NULL ,
            `mkdate` INT NULL ,
            PRIMARY KEY (`id`)
        )");

        SimpleORMap::expireTableScheme();
    }

    public function down () {
        DBManager::get()->exec("DROP TABLE minicourse");
        SimpleORMap::expireTableScheme();
    }
}
