<?php

namespace convergine\craftbastion\migrations;

use convergine\craftbastion\records\DependencyAuditRecord;
use convergine\craftbastion\records\ScannerRecord;
use convergine\craftbastion\records\SettingsRecord;
use Craft;
use craft\db\Migration;
use craft\db\Table as CraftTable;

class Install extends Migration {
	/**
	 * @inheritdoc
	 */
	public function safeUp(): bool {
		// Place migration code here...
		if ( ! $this->db->tableExists( SettingsRecord::tableName() ) ) {
			$this->createTable( SettingsRecord::tableName(), [
				'id'          => $this->primaryKey(),
				'siteId'      => $this->integer()->null(),
				'type'        => $this->string(50),
				'name'        => $this->string( 100 ),
				'value'       => $this->text(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid'         => $this->uid(),
			] );
			$this->addForeignKey(null, SettingsRecord::tableName(), ['siteId'], CraftTable::SITES, ['id'], 'SET NULL');
		}

		if ( ! $this->db->tableExists( ScannerRecord::tableName() ) ) {
			$this->createTable(ScannerRecord::tableName(), [
				'id' => $this->primaryKey(),
				'siteId' => $this->integer()->notNull(),
				'pass' => $this->boolean()->notNull(),
				'warning' => $this->boolean()->notNull(),
				'results' => $this->text(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid(),
			]);

			$this->createIndex(null, ScannerRecord::tableName(), 'siteId');

			$this->addForeignKey(null, ScannerRecord::tableName(), 'siteId', ScannerRecord::tableName(), 'id', 'CASCADE', 'CASCADE');
		}

		if (!$this->db->tableExists(DependencyAuditRecord::tableName())) {
			$this->createTable(DependencyAuditRecord::tableName(), [
				'id' => $this->primaryKey(),
				'siteId' => $this->integer()->notNull(),
				'totalPackages' => $this->integer()->notNull()->defaultValue(0),
				'vulnerablePackages' => $this->integer()->notNull()->defaultValue(0),
				'totalAdvisories' => $this->integer()->notNull()->defaultValue(0),
				'criticalCount' => $this->integer()->notNull()->defaultValue(0),
				'highCount' => $this->integer()->notNull()->defaultValue(0),
				'mediumCount' => $this->integer()->notNull()->defaultValue(0),
				'lowCount' => $this->integer()->notNull()->defaultValue(0),
				'scanDuration' => $this->decimal(10, 3)->notNull()->defaultValue(0),
				'vulnerabilities' => $this->mediumText(),
				'packages' => $this->mediumText(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid(),
			]);

			$this->createIndex(null, DependencyAuditRecord::tableName(), 'siteId');

			$this->addForeignKey(
				null,
				DependencyAuditRecord::tableName(),
				'siteId',
				CraftTable::SITES,
				'id',
				'CASCADE',
				'CASCADE'
			);
		}

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown(): bool {
		$this->dropAllForeignKeysToTable('{{%bastion_settings}}');
		$this->dropTableIfExists('{{%bastion_settings}}');

		$this->dropAllForeignKeysToTable('{{%bastion_scanner}}');
		$this->dropTableIfExists('{{%bastion_scanner}}');

		$this->dropAllForeignKeysToTable(DependencyAuditRecord::tableName());
		$this->dropTableIfExists(DependencyAuditRecord::tableName());

		return true;
	}
}
