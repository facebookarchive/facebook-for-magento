<?php

namespace Facebook\BusinessExtension\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface {
    const FBE_CONFIG_TABLE_NAME = "facebook_business_extension_config";

    public function install(SchemaSetupInterface $installer, ModuleContextInterface $context) {
        $installer->startSetup();
        $installer->getConnection()->dropTable($installer->getTable(self::FBE_CONFIG_TABLE_NAME));
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::FBE_CONFIG_TABLE_NAME)
        )->addColumn(
            'config_key',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'primary' => true],
            'Config Key'
        )->addColumn(
            'config_value',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => ''],
            'Config Value'
        )->addColumn(
            'creation_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            array (
            ),
            'Creation Time'
        )->addColumn(
            'update_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            array (
            ),
            'Modification Time'
        );
        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}
