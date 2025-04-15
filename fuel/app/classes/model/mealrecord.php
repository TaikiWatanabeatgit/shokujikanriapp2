<?php

class Model_Mealrecord extends Orm\Model
{
    protected static $_table_name = 'meal_records'; // Laravelのテーブル名に合わせる (複数形が一般的)

    protected static $_primary_key = array('id');

    protected static $_properties = array(
        'id',
        'date' => [
            'data_type' => 'date',
        ],
        'meal_type' => [
            'data_type' => 'enum',
            'options' => ['breakfast', 'lunch', 'dinner', 'snack'],
        ],
        'food_name' => [
            'data_type' => 'varchar',
            'validation' => ['required', 'max_length[255]'],
        ],
        'calories' => [
            'data_type' => 'int',
            'validation' => ['is_numeric', 'numeric_min[0]'], // null許容はORM側でよしなに扱われることが多い
            'default' => null, // デフォルト値を設定
        ],
        'notes' => [
            'data_type' => 'text',
            'default' => null,
        ],
        'created_at' => [
            'data_type' => 'timestamp',
            'default' => null, // ORMが自動設定する場合
        ],
        'updated_at' => [
            'data_type' => 'timestamp',
            'default' => null, // ORMが自動設定する場合
        ],
    );

    // created_at と updated_at を自動更新する場合
    protected static $_observers = array(
        'Orm\Observer_CreatedAt' => array(
            'events' => array('before_insert'),
            'mysql_timestamp' => true,
            'property' => 'created_at',
        ),
        'Orm\Observer_UpdatedAt' => array(
            'events' => array('before_save'), // insert と update 両方
            'mysql_timestamp' => true,
            'property' => 'updated_at',
        ),
    );

    // バリデーションルール (コントローラ側でValidationクラスを使う方が一般的)
    // 必要であればここで定義も可能
    // public static function validate($factory)
    // {
    //     $val = Validation::forge($factory);
    //     $val->add_field('date', 'Date', 'required');
    //     // ... 他のフィールド
    //     return $val;
    // }
} 