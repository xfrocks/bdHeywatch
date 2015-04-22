<?php

class bdHeywatch_Installer
{
    /* Start auto-generated lines of code. Change made will be overwriten... */

    protected static $_tables = array(
        'log' => array(
            'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdheywatch_log` (
                `log_id` INT(10) UNSIGNED AUTO_INCREMENT
                ,`log_date` INT(10) UNSIGNED NOT NULL
                ,`data_id` INT(10) UNSIGNED NOT NULL
                ,`sent` MEDIUMBLOB
                ,`received` MEDIUMBLOB
                , PRIMARY KEY (`log_id`)
                
            ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
            'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdheywatch_log`',
        ),
    );
    protected static $_patches = array(
        array(
            'table' => 'xf_attachment_data',
            'field' => 'bdheywatch_options',
            'showTablesQuery' => 'SHOW TABLES LIKE \'xf_attachment_data\'',
            'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_attachment_data` LIKE \'bdheywatch_options\'',
            'alterTableAddColumnQuery' => 'ALTER TABLE `xf_attachment_data` ADD COLUMN `bdheywatch_options` MEDIUMBLOB',
            'alterTableDropColumnQuery' => 'ALTER TABLE `xf_attachment_data` DROP COLUMN `bdheywatch_options`',
        ),
    );

    public static function install($existingAddOn, $addOnData)
    {
        $db = XenForo_Application::get('db');

        foreach (self::$_tables as $table) {
            $db->query($table['createQuery']);
        }

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['showTablesQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['showColumnsQuery']);
            if (empty($existed)) {
                $db->query($patch['alterTableAddColumnQuery']);
            }
        }

        self::installCustomized($existingAddOn, $addOnData);
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['showTablesQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['showColumnsQuery']);
            if (!empty($existed)) {
                $db->query($patch['alterTableDropColumnQuery']);
            }
        }

        foreach (self::$_tables as $table) {
            $db->query($table['dropQuery']);
        }

        self::uninstallCustomized();
    }

    /* End auto-generated lines of code. Feel free to make changes below */

    public static function installCustomized($existingAddOn, $addOnData)
    {
        if (XenForo_Application::$versionId < 1020000) {
            throw new XenForo_Exception('[bd] Heywatch Integration requires XenForo 1.2.0+');
        }
    }

    public static function uninstallCustomized()
    {
        // customized uninstall script goes here
    }

}
