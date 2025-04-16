<?php

namespace Fuel\Migrations;

class Create_meal_records
{
	public function up()
	{
		\DBUtil::create_table('meal_records', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true, 'unsigned' => true),
			'date' => array('type' => 'date'),
			'meal_type' => array('constraint' => "'breakfast','lunch','dinner','snack'", 'type' => 'enum'),
			'food_name' => array('constraint' => 255, 'type' => 'varchar'),
			'calories' => array('constraint' => 11, 'type' => 'int', 'null' => true),
			'notes' => array('type' => 'text', 'null' => true),
			'created_at' => array('constraint' => 11, 'type' => 'int', 'null' => true),
			'updated_at' => array('constraint' => 11, 'type' => 'int', 'null' => true),

		), array('id'));

        // meal_type カラムにデフォルト値を追加 (任意ですが、エラーを防ぐために推奨)
        \DB::query("ALTER TABLE `meal_records` CHANGE `meal_type` `meal_type` ENUM('breakfast','lunch','dinner','snack') DEFAULT 'breakfast'")->execute();

	}

	public function down()
	{
		\DBUtil::drop_table('meal_records');
	}
} 