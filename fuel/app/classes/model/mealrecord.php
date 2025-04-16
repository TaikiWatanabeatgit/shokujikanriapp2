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
                ->order_by('id', 'desc') // 日付が同じ場合の順序
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
        // created_at, updated_at にUnixタイムスタンプを設定
        $now = time(); // <-- time() を使用してUnixタイムスタンプを取得
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        try {
            list($insert_id, $rows_affected) = DB::insert(static::$_table_name)
                ->set($data)
                ->execute();
            // 挿入成功（影響行数>0）なら挿入ID、失敗ならfalseを返す
            return ($rows_affected > 0) ? $insert_id : false;
        } catch (\Database_Exception $e) {
            Log::error('Database error in create_record: ' . $e->getMessage());
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
        // ★変更: updated_at を Unixタイムスタンプで設定
        $data['updated_at'] = time(); // date('Y-m-d H:i:s'); から変更

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
     * 指定日より前のレコード取得 (ページネーション対応)
     * @param string $date (Y-m-d)
     * @param int $limit 取得件数
     * @param int $offset 開始位置
     * @return array
     */
    public static function find_before_date($date, $limit = null, $offset = 0)
    {
        try {
             $query = DB::select('*')
                ->from(static::$_table_name)
                ->where('date', '<', $date)
                ->order_by('date', 'desc')
                ->order_by('id', 'desc'); // 日付が同じ場合の順序

             if ($limit !== null) {
                 $query->limit((int)$limit);
             }
             $query->offset((int)$offset);

             return $query->as_assoc()
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

    /**
     * 指定された期間の食事記録を検索する
     *
     * @param string $start_date 開始日 (Y-m-d)
     * @param string $end_date   終了日 (Y-m-d)
     * @return array             検索結果の配列、見つからない場合は空配列
     * @throws \Database_Exception データベースエラーが発生した場合 (コントローラで捕捉するため)
     */
    public static function search_by_date_range($start_date, $end_date)
    {
        try {
            // クエリビルダを使用して指定された日付範囲のデータを取得
            $query = DB::select('*') // SELECT * FROM meal_records
                        ->from(static::$_table_name)
                        ->where('date', '>=', $start_date) // WHERE date >= start_date
                        ->where('date', '<=', $end_date)   // AND date <= end_date
                        ->order_by('date', 'asc')          // ORDER BY date ASC
                        ->order_by('id', 'asc');            // , id ASC (日付が同じ場合の順序)

            // クエリを実行し、結果を連想配列の配列として取得
            $result = $query->as_assoc()->execute()->as_array();

            return $result;

        } catch (\Database_Exception $e) {
            // エラーログを記録
            Log::error('Database error in search_by_date_range: ' . $e->getMessage() . ' with dates: ' . $start_date . ' to ' . $end_date);
            // エラーをコントローラーに通知するために再スロー
            throw $e;
        }
    }

} 