<?php

/**
 * Meal Record Model (using Query Builder)
 */
class Model_Mealrecord // extends は不要
{
    // テーブル名は定義しておくと便利
    protected static $_table_name = 'meal_records';

    /**
     * IDでレコードを取得
     * @param int $id
     * @return array|null 取得したレコード(連想配列) or null
     */
    public static function find_by_id($id)
    {
        if (empty($id) || !ctype_digit((string)$id)) {
            return null;
        }
        try {
            return DB::select('*')
                ->from(static::$_table_name)
                ->where('id', '=', $id)
                ->as_assoc()
                ->execute()
                ->current();
        } catch (\Database_Exception $e) {
            // Log::error('Database error: ' . $e->getMessage());
            // エラーハンドリング: 例外を再スローするか、nullを返すなど
            return null;
        }
    }

    /**
     * 全件取得 (ページネーション対応)
     * @param int $limit
     * @param int $offset
     * @return array 取得したレコードの配列
     */
    public static function get_paged($limit, $offset)
    {
        try {
            return DB::select('*')
                ->from(static::$_table_name)
                ->order_by('date', 'desc')
                ->order_by('id', 'desc') // 日付が同じ場合の順序を保証
                ->limit((int)$limit)
                ->offset((int)$offset)
                ->as_assoc()
                ->execute()
                ->as_array();
        } catch (\Database_Exception $e) {
            // Log::error('Database error: ' . $e->getMessage());
            return array(); // 空配列を返す
        }
    }

    /**
     * レコード総数取得
     * @return int
     */
    public static function count_all()
    {
        try {
            $result = DB::select(array(DB::expr('COUNT(id)'), 'total_count'))
                        ->from(static::$_table_name)
                        ->execute()
                        ->current();
            return $result ? (int)$result['total_count'] : 0;
        } catch (\Database_Exception $e) {
            // Log::error('Database error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * レコード作成
     * @param array $data (カラム名 => 値 の連想配列)
     * @return int|false 挿入したレコードのID or false
     */
    public static function create_record(array $data)
    {
        // created_at, updated_at を手動で追加 (必要であれば)
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        try {
            list($insert_id, $rows_affected) = DB::insert(static::$_table_name)
                ->set($data)
                ->execute();
            return ($rows_affected > 0) ? $insert_id : false;
        } catch (\Database_Exception $e) {
            // Log::error('Database error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * レコード更新
     * @param int $id
     * @param array $data (カラム名 => 値 の連想配列)
     * @return bool 更新に影響があったか (true/false)
     */
    public static function update_record($id, array $data)
    {
        if (empty($id) || !ctype_digit((string)$id)) {
            return false;
        }
        // updated_at を手動で追加 (必要であれば)
        $data['updated_at'] = date('Y-m-d H:i:s');

        try {
            $rows_affected = DB::update(static::$_table_name)
                ->set($data)
                ->where('id', '=', $id)
                ->execute();
            // execute() は影響を受けた行数を返す
            return ($rows_affected > 0);
        } catch (\Database_Exception $e) {
            // Log::error('Database error: ' . $e->getMessage());
            return false;
        }
    }

     /**
     * レコード削除
     * @param int $id
     * @return bool 削除に影響があったか (true/false)
     */
    public static function delete_record($id)
    {
        if (empty($id) || !ctype_digit((string)$id)) {
            return false;
        }
        try {
            $rows_affected = DB::delete(static::$_table_name)
                ->where('id', '=', $id)
                ->execute();
            return ($rows_affected > 0);
        } catch (\Database_Exception $e) {
            // Log::error('Database error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 指定日のレコード取得
     * @param string $date (Y-m-d)
     * @return array
     */
    public static function find_by_date($date)
    {
        try {
            return DB::select('*')
                ->from(static::$_table_name)
                ->where('date', '=', $date)
                ->order_by('meal_type', 'asc')
                ->as_assoc()
                ->execute()
                ->as_array();
        } catch (\Database_Exception $e) {
           // Log::error('Database error: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * 指定日より前のレコード取得
     * @param string $date (Y-m-d)
     * @return array
     */
    public static function find_before_date($date)
    {
        try {
             return DB::select('*')
                ->from(static::$_table_name)
                ->where('date', '<', $date)
                ->order_by('date', 'desc')
                ->as_assoc()
                ->execute()
                ->as_array();
        } catch (\Database_Exception $e) {
            // Log::error('Database error: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * 指定年月の合計カロリー取得
     * @param int $year
     * @param int $month
     * @return int
     */
    public static function sum_calories_for_month($year, $month)
    {
        try {
            $result = DB::select(array(DB::expr('SUM(calories)'), 'total_calories'))
                ->from(static::$_table_name)
                ->where(DB::expr('YEAR(date)'), '=', $year)
                ->where(DB::expr('MONTH(date)'), '=', $month)
                ->execute()
                ->current();
            return $result ? (int)$result['total_calories'] : 0;
        } catch (\Database_Exception $e) {
            // Log::error('Database error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 指定日より前のカロリーの平均と件数を取得
     * @param string $date (Y-m-d)
     * @return array ('total' => 合計カロリー, 'count' => 件数)
     */
    public static function get_past_calories_stats($date)
    {
         try {
            $result = DB::select(
                    array(DB::expr('SUM(calories)'), 'total'),
                    array(DB::expr('COUNT(id)'), 'count')
                )
                ->from(static::$_table_name)
                ->where('date', '<', $date)
                ->where('calories', 'is not', null)
                ->execute()
                ->current();
            return $result ? $result : ['total' => 0, 'count' => 0];
        } catch (\Database_Exception $e) {
            // Log::error('Database error: ' . $e->getMessage());
            return ['total' => 0, 'count' => 0];
        }
    }

    /**
     * 料理名で検索
     * @param string $name
     * @return array
     */
    public static function search_by_name($name)
    {
        try {
            return DB::select('*')
                ->from(static::$_table_name)
                ->where('food_name', 'like', '%' . trim($name) . '%')
                ->order_by('date', 'desc')
                ->as_assoc()
                ->execute()
                ->as_array();
        } catch (\Database_Exception $e) {
            // Log::error('Database error: ' . $e->getMessage());
            return array();
        }
    }

} 