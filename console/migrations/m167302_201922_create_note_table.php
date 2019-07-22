<?php

use yii\db\Migration;

class m167302_201922_create_note_table extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%note}}', [
            'id' => $this->primaryKey(),
            'sid' => $this->string(50)->notNull()->unique(),
            'title' => $this->string(255)->null()->defaultValue(null),
            'text' => $this->string()->null()->defaultValue(null),
            'author' => $this->string()->null()->defaultValue(null),
            'created_at' => $this->timestamp()->notNull(),
            'updated_at' => $this->timestamp()->notNull(),
            'deleted_at' => $this->timestamp()->null()->defaultValue(null),
        ], $tableOptions);

        $this->createIndex('note_unique_sid_idx', 'note', 'note_sid');
        $this->createIndex('note_created_idx', 'note', 'created_at');
        $this->createIndex('note_author_idx', 'note', 'author');
    }

    public function down()
    {
        $this->dropIndex('note_unique_sid_idx', 'note');
        $this->dropIndex('note_created_idx', 'note');
        $this->dropIndex('note_author_idx', 'note');
        $this->dropTable('{{%note}}');
    }
}
