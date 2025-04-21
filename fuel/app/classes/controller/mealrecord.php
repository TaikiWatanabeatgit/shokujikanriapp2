<?php

class Controller_Mealrecord extends Controller // 必要なら Controller_Template などに変更
{
    /**
     * 一覧表示
     */
    public function before()
    {
        parent::before(); // 親クラスのbeforeメソッドを呼び出す (必要に応じて)

        $hour = (int)date('G'); // 現在の時を取得 (0-23)
        $greeting = '';

        if ($hour >= 5 && $hour < 12) { // 5:00 - 11:59
            $greeting = 'おはようございます☀️';
        } elseif ($hour >= 12 && $hour < 18) { // 12:00 - 17:59
            $greeting = 'こんにちは☀️';
        } else { // 18:00 - 4:59
            $greeting = 'こんばんは🌙';
        }

        // ビュー全体で $greeting 変数を使えるように設定
        View::set_global('greeting', $greeting);
    }
    public function action_index()
    {
        // 現在のページ番号を取得 (uri_segment 'page' の次、通常は3番目)
        $current_page = Request::active()->uri->segment(3, 1); // Request クラスを使用するように修正

        // pagination.php 設定ファイルを読み込み (念のため)
        Config::load('pagination.php');

        // 設定ファイルから bootstrap3 テンプレートを取得 (キーを修正)
        $bootstrap3_template = Config::get('bootstrap3', array());

        // 基本的なページネーション設定
        $total_items_count = Model_Mealrecord::count_all(); // 値を一時変数に
        $base_config = array(
            'pagination_url' => Uri::create('mealrecord'),
            'total_items'    => $total_items_count, // 一時変数を使用
            'per_page'       => 10, // 1ページあたりの表示件数
            'uri_segment'    => 'page', // URLセグメント名 (例: /mealrecord/page/2)
            'current_page'   => (int)$current_page, // 現在のページを明示的に設定
            // 'name' => 'bootstrap3', // ここでは指定しない
        );

        // テンプレートと基本設定をマージ
        $final_config = array_merge($bootstrap3_template, $base_config);
        $final_config['name'] = 'bootstrap3'; // ★マージ後に name を追加

        // ページネーションインスタンス生成 (マージした設定を使用)
        $pagination = Pagination::forge('mealrecord_pagination', $final_config);

        // データ取得
        $mealRecords = Model_Mealrecord::get_paged(
            $pagination->per_page,
            $pagination->offset
        );

        // Viewに渡すデータ
        $data = array();
        $data['title'] = '食事記録一覧';
        $data['mealRecords'] = $mealRecords; // 取得したデータをセット
        $data['pagination'] = $pagination;

        // Viewを生成して返す
        return Response::forge(View::forge('mealrecord/index', $data));
    }

    /**
     * 作成フォーム表示
     */
    public function action_create()
    {
        // Viewに渡すデータ
        $data = array();
        $data['title'] = '食事記録作成';

        // Validationオブジェクトをビューに渡す
        $data['val'] = Validation::forge('mealrecord_create');

        // Viewを生成して返す
        return Response::forge(View::forge('mealrecord/create', $data));
    }

    /**
     * 新規保存処理
     */
    public function action_store()
    {
        // POSTリクエストでない場合は create へリダイレクト
        if (Input::method() !== 'POST') {
            Response::redirect('mealrecord/create');
        }

        // バリデーション設定
        $val = Validation::forge('mealrecord_create');
        $val->add_field('date', '日付', 'required|valid_date[Y-m-d]');
        $val->add_field('meal_type', '種類', 'required')->add_rule('in_array', array('breakfast', 'lunch', 'dinner', 'snack'));
        $val->add_field('food_name', '料理名', 'required|max_length[255]');
        $val->add_field('calories', 'カロリー', 'valid_string[numeric]|numeric_min[0]'); // null許容
        $val->add_field('notes', 'メモ', 'max_length[1000]'); // 例: 最大1000文字

        // CSRFトークン検証
        if (!Security::check_token()) {
            Session::set_flash('error', '不正なリクエストです。ページを再読み込みしてもう一度お試しください。');
            $data['title'] = '食事記録作成';
            $data['val'] = $val;
            return Response::forge(View::forge('mealrecord/create', $data), 403);
        }
        Log::warning(Session::get('csrf_token'));
        

        // バリデーション実行
        if ($val->run()) {
            // バリデーションOK
            try {
                $validated_data = $val->validated();

                // calories が空文字列の場合は null にする
                if (isset($validated_data['calories']) && $validated_data['calories'] === '') {
                    $validated_data['calories'] = null;
                }

                if (Model_Mealrecord::create_record($validated_data)) {
                    Session::set_flash('success', '食事記録が正常に保存されました。');
                    Response::redirect('mealrecord');
                } else {
                    Session::set_flash('error', '食事記録の保存に失敗しました。');
                }
            } catch (\Database_Exception $e) {
                Session::set_flash('error', 'データベースエラーが発生しました。' /* . $e->getMessage() - 詳細なエラーはログへ */);
                Log::error('Database error in store: ' . $e->getMessage());
            } catch (\Exception $e) {
                Session::set_flash('error', '予期せぬエラーが発生しました。');
                Log::error('Unexpected error in store: ' . $e->getMessage());
            }
        } else {
            // バリデーションNG
            Session::set_flash('error', $val->show_errors());
        }

        // バリデーションNGまたは保存失敗時はフォーム再表示
        $data['title'] = '食事記録作成';
        $data['val'] = $val;
        return Response::forge(View::forge('mealrecord/create', $data));
    }

    /**
     * 詳細表示 (今回は未使用)
     */
    public function action_show($id = null)
    {
        // 必要であれば実装
        return Response::forge("Show Meal Record: {$id} (TODO)");
    }

    /**
     * 編集フォーム表示
     */
    public function action_edit($id = null)
    {
        // IDチェック
        if ($id === null || !ctype_digit((string)$id)) {
            Session::set_flash('error', '無効なIDです。');
            Response::redirect('mealrecord');
        }

        // レコード検索 (★変更: クエリビルダ版メソッド)
        $mealRecord = Model_Mealrecord::find_by_id($id);

        // レコードが見つからない場合
        if (!$mealRecord) {
            Session::set_flash('error', '指定された食事記録が見つかりません。');
            Response::redirect('mealrecord');
        }

        // Viewに渡すデータ
        $data = array();
        $data['title'] = '食事記録編集';
        $data['mealRecord'] = $mealRecord; // ★結果は配列

        // Validationオブジェクト (変更なしだが、populateは使えない)
        $val = Validation::forge('mealrecord_edit');
        // populate() は使わない。ビュー側で配列から値を取り出す。
        $data['val'] = $val;

        // Viewを生成して返す
        return Response::forge(View::forge('mealrecord/edit', $data));
    }

    /**
     * 更新処理
     */
    public function action_update($id = null)
    {
        // IDチェック
        if ($id === null || !ctype_digit((string)$id)) {
            Session::set_flash('error', '無効なIDです。');
            Response::redirect('mealrecord');
        }

        // ★変更: クエリビルダ版メソッドで存在確認のみ行う
        $mealRecordExists = Model_Mealrecord::find_by_id($id);
        if (!$mealRecordExists) {
            Session::set_flash('error', '指定された食事記録が見つかりません。');
            Response::redirect('mealrecord');
        }

        // POSTリクエストでない場合は edit へリダイレクト
        if (Input::method() !== 'POST') {
            Response::redirect('mealrecord/edit/' . $id);
        }

        // バリデーション設定
        $val = Validation::forge('mealrecord_edit');
        $val->add_field('date', '日付', 'required|valid_date[Y-m-d]');
        $val->add_field('meal_type', '種類', 'required')->add_rule('in_array', array('breakfast', 'lunch', 'dinner', 'snack'));
        $val->add_field('food_name', '料理名', 'required|max_length[255]');
        $val->add_field('calories', 'カロリー', 'valid_string[numeric]|numeric_min[0]');
        $val->add_field('notes', 'メモ', 'max_length[1000]');

        // CSRFトークン検証
        if (!Security::check_token()) {
            Session::set_flash('error', '不正なリクエストです。ページを再読み込みしてもう一度お試しください。');
            // 編集フォーム再表示に必要なデータをセット
            $data['title'] = '食事記録編集';
            $data['mealRecord'] = $mealRecordExists; // ★元のデータを配列で渡す
            $data['val'] = $val;
            return Response::forge(View::forge('mealrecord/edit', $data), 403);
        }

        // バリデーション実行
        if ($val->run()) {
            // バリデーションOK
            try {
                $validated_data = $val->validated();

                // calories が空文字列の場合は null にする
                if (isset($validated_data['calories']) && $validated_data['calories'] === '') {
                    $validated_data['calories'] = null;
                }

                // ★変更: クエリビルダ版のモデルメソッドで更新
                if (Model_Mealrecord::update_record($id, $validated_data)) {
                    Session::set_flash('success', '食事記録が正常に更新されました。');
                    Response::redirect('mealrecord');
                } else {
                    // Model_Mealrecord::update_record は変更があった場合のみtrueを返す想定
                    // エラーでなければ「変更がなかった」か「失敗」
                    // ここではシンプルに「更新されなかった」とするか、詳細なエラーハンドリングを行う
                    Session::set_flash('notice', 'データに変更がないか、更新に失敗しました。');
                     // 更新されなくても一覧に戻す場合
                    Response::redirect('mealrecord');
                }
            } catch (\Database_Exception $e) {
                Session::set_flash('error', 'データベースエラーが発生しました。');
                Log::error('Database error in update: ' . $e->getMessage());
            } catch (\Exception $e) { // 予期せぬエラー
                 Session::set_flash('error', '予期せぬエラーが発生しました。');
                 Log::error('Unexpected error in update: ' . $e->getMessage());
            }
        } else {
            // バリデーションNG
            Session::set_flash('error', $val->show_errors());
        }

        // バリデーションNGまたはDBエラー時はフォーム再表示
        $data['title'] = '食事記録編集';
        // ★変更: 再表示用にデータを再取得して配列で渡す
        $data['mealRecord'] = Model_Mealrecord::find_by_id($id); // エラー時の入力値復元のためにも元のデータは必要
        $data['val'] = $val; // エラー情報や入力値を保持
        return Response::forge(View::forge('mealrecord/edit', $data));
    }

    /**
     * 削除処理
     */
    public function action_destroy($id = null)
    {
        Log::debug('------ [ACTION_DESTROY START] id: ' . $id); // ★目印を変更

        // IDチェック
        if ($id === null || !ctype_digit((string)$id)) {
            Log::warning('[ACTION_DESTROY] Invalid ID provided: ' . $id); // ★目印を変更
            Session::set_flash('error', '無効なIDです。');
            Response::redirect('mealrecord');
        }

        // POSTリクエスト & CSRFチェック
        if (Input::method() === 'POST') {
            Log::debug('[ACTION_DESTROY] POST request received.'); // ★目印を変更
            if (!Security::check_token()) {
                Log::warning(Session::get('csrf_token'));
               Log::warning('[ACTION_DESTROY] CSRF token mismatch.'); // ★目印を変更
                Session::set_flash('error', '不正なリクエストです。もう一度お試しください。');
                Response::redirect('mealrecord');
            }

            Log::debug('[ACTION_DESTROY] CSRF check passed. Finding record...'); // ★目印を変更
            // ★変更: 存在確認 (delete_record内でidチェックはされるが、事前に確認)
            $mealRecordExists = Model_Mealrecord::find_by_id($id);

            if ($mealRecordExists) {
                Log::debug('[ACTION_DESTROY] Record found. Attempting delete...'); // ★目印を変更
                try {
                    // ★変更: クエリビルダ版の削除メソッド呼び出し
                    if (Model_Mealrecord::delete_record($id)) {
                        Log::info('[ACTION_DESTROY] Record deleted successfully. Setting success flash.'); // ★目印を変更
                        Session::set_flash('success', '食事記録が正常に削除されました。');
                    } else {
                        Log::error('[ACTION_DESTROY] Model_Mealrecord::delete_record returned false.'); // ★目印を変更
                        // delete_record が false を返すのは、DBエラーか影響行数0の場合
                        Session::set_flash('error', '食事記録の削除に失敗しました。');
                    }
                } catch (\Database_Exception $e) { // モデル側で catch してるが念のため
                    Log::error('[ACTION_DESTROY] Database exception during delete: ' . $e->getMessage()); // ★目印を変更
                    Session::set_flash('error', 'データベースエラーが発生しました。');
                    // Log::error('Database error in destroy: ' . $e->getMessage()); // ★コメントアウト (上でログ出力)
                } catch (\Exception $e) { // 予期せぬエラー
                    Log::error('[ACTION_DESTROY] Unexpected exception during delete: ' . $e->getMessage()); // ★目印を変更
                    Session::set_flash('error', '予期せぬエラーが発生しました。');
                    // Log::error('Unexpected error in destroy: ' . $e->getMessage()); // ★コメントアウト (上でログ出力)
                }
            } else {
                Log::warning('[ACTION_DESTROY] Record not found for deletion.'); // ★目印を変更
                Session::set_flash('error', '指定された食事記録が見つかりません。');
            }
        } else {
            Log::warning('[ACTION_DESTROY] Non-POST request received for destroy action.'); // ★目印を変更
            // POST以外でのアクセスは許可しない (変更なし)
            Session::set_flash('error', '不正なアクセス方法です。');
        }

        Log::debug('[ACTION_DESTROY] Redirecting to mealrecord...'); // ★目印を変更
        // 処理後、一覧へリダイレクト (変更なし)
        Response::redirect('mealrecord');

        // ★念のためリダイレクト後にdie()を追加して、この行が表示されないことを確認
        // die('Should have redirected!');
    }

    /**
     * サマリー表示
     */
    public function action_summary()
    {
        $today = date('Y-m-d');
        $currentYear = date('Y');
        $currentMonth = date('m');
        $initialLimit = 5; // 初期表示件数

        // 今日の記録
        $todayRecords = Model_Mealrecord::find_by_date($today);

        // 今月の合計カロリー
        $monthlyTotalCalories = Model_Mealrecord::sum_calories_for_month($currentYear, $currentMonth);

        // 過去の平均カロリー
        $pastStats = Model_Mealrecord::get_past_calories_stats($today);
        $pastAverageCalories = ($pastStats['count'] > 0) ? ($pastStats['total'] / $pastStats['count']) : 0;

        // 過去の記録 (初期表示分)
        $pastRecords = Model_Mealrecord::find_before_date($today, $initialLimit, 0);

        // Viewに渡すデータ
        $data = array();
        $data['title'] = '食事記録サマリー';
        $data['todayRecords'] = $todayRecords;
        $data['monthlyTotalCalories'] = $monthlyTotalCalories;
        $data['pastAverageCalories'] = $pastAverageCalories;
        $data['pastRecords'] = $pastRecords;
        $data['initialPastRecordCount'] = count($pastRecords); // 初期表示件数をビューに渡す
        $data['recordsPerPage'] = $initialLimit; // 1ページあたりの件数も渡す

        // Viewを生成して返す
        return Response::forge(View::forge('mealrecord/summary', $data));
    }

    /**
     * 追加の過去記録を読み込む (Ajax用)
     */
    public function get_load_more_past_records()
    {
        $offset = Input::get('offset', 0);
        $limit = Input::get('limit', 5); // デフォルトの取得件数
        $today = date('Y-m-d');

        if (!ctype_digit((string)$offset) || !ctype_digit((string)$limit) || $offset < 0 || $limit <= 0) {
             return Response::forge(json_encode(['error' => 'Invalid parameters']), 400, ['Content-Type' => 'application/json']);
        }

        try {
            $moreRecords = Model_Mealrecord::find_before_date($today, (int)$limit, (int)$offset);
            return Response::forge(json_encode($moreRecords), 200, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            Log::error('Error loading more past records: ' . $e->getMessage());
            return Response::forge(json_encode(['error' => 'Server error']), 500, ['Content-Type' => 'application/json']);
        }
    }

    /**
     * 検索ページ表示・処理 (料理名検索)
     */
    public function action_search()
    {
        $data = array();
        $data['title'] = '食事記録検索';
        $data['search_name'] = '';
        $data['start_date'] = ''; // Add for range search
        $data['end_date'] = '';   // Add for range search
        $data['search_type'] = null; // To identify which search was performed
        $data['records'] = null;
        $data['error_message'] = '';

        if (Input::method() === 'POST') {
            // Check CSRF token once for any POST request
            if (!Security::check_token()) {
                 $data['error_message'] = '不正なリクエストです。ページを再読み込みしてください。';
            } else {
                $search_type = Input::post('search_type');
                $data['search_type'] = $search_type;

                if ($search_type === 'name') {
                    // --- Name Search Logic --- 
                    $search_name = Input::post('search_name');
                    $data['search_name'] = $search_name;

                    if (empty($search_name)) {
                        $data['error_message'] = '料理名を入力してください。';
                    } else {
                        try {
                            // Assuming Model_Mealrecord::search_by_name exists
                            $data['records'] = Model_Mealrecord::search_by_name($search_name);
                            // No need to set error message here if empty, view handles it
                            // if (empty($data['records'])) {
                            //     $data['error_message'] = Security::htmlentities($search_name) . ' を含む記録は見つかりませんでした。';
                            // }
                        } catch (\Database_Exception $e) {
                            $data['error_message'] = '検索中にデータベースエラーが発生しました。';
                            Log::error('Database error in search (name): ' . $e->getMessage());
                        } catch (\Exception $e) {
                            $data['error_message'] = '検索中に予期せぬエラーが発生しました。';
                            Log::error('Unexpected error in search (name): ' . $e->getMessage());
                        }
                    }
                    // --- End Name Search Logic --- 

                } elseif ($search_type === 'range') {
                    // --- Date Range Search Logic --- 
                    $start_date = Input::post('start_date');
                    $end_date = Input::post('end_date');
                    $data['start_date'] = $start_date;
                    $data['end_date'] = $end_date;

                    // Basic validation for dates
                    if (empty($start_date) || empty($end_date)) {
                        $data['error_message'] = '開始日と終了日の両方を入力してください。';
                    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                         $data['error_message'] = '日付は YYYY-MM-DD 形式で入力してください。';
                    } elseif (strtotime($start_date) > strtotime($end_date)) {
                        $data['error_message'] = '開始日は終了日より前の日付を指定してください。';
                    } else {
                        try {
                            // Assuming Model_Mealrecord::search_by_date_range exists
                            $data['records'] = Model_Mealrecord::search_by_date_range($start_date, $end_date);
                            // View handles the "no results" message
                        } catch (\Database_Exception $e) {
                            $data['error_message'] = '検索中にデータベースエラーが発生しました。';
                            Log::error('Database error in search (range): ' . $e->getMessage());
                        } catch (\Exception $e) {
                            $data['error_message'] = '検索中に予期せぬエラーが発生しました。';
                            Log::error('Unexpected error in search (range): ' . $e->getMessage());
                        }
                    }
                    // --- End Date Range Search Logic --- 
                }
                // Only other POST type is AJAX date search, handled by post_search_date()
            }
        }

        // Always load the search view, passing data (including results or errors)
        return Response::forge(View::forge('mealrecord/search', $data));
    }

    /**
     * 日付検索API (AJAX用)
     */
    public function post_search_date()
    {
        // AJAXリクエストかつPOSTかチェック
        if (!Input::is_ajax() || Input::method() !== 'POST') {
            // Invalid request: Return 400 Bad Request
            return Response::forge(json_encode(['error' => 'Invalid request']), 400, ['Content-Type' => 'application/json']);
        }

        // CSRF チェック (実装する場合はここに)
        // if (!Security::check_token()) {
        //     return Response::forge(json_encode(['error' => 'Invalid CSRF token']), 403, ['Content-Type' => 'application/json']);
        // }

        $search_date = Input::post('search_date');

        // 日付フォーマット検証
        if ($search_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $search_date)) {
            try {
                // モデルを使って日付で検索
                $records = Model_Mealrecord::find_by_date($search_date);

                // 成功: JSON形式で記録を返す (空配列の場合も含む)
                return Response::forge(json_encode($records), 200, ['Content-Type' => 'application/json']);

            } catch (\Database_Exception $e) {
                 // データベースエラー
                 Log::error('Database error in search (date): ' . $e->getMessage());
                 return Response::forge(json_encode(['error' => 'データベース検索中にエラーが発生しました。']), 500, ['Content-Type' => 'application/json']);
            } catch (\Exception $e) {
                 // その他の予期せぬエラー
                 Log::error('Unexpected error in search (date): ' . $e->getMessage());
                 return Response::forge(json_encode(['error' => '予期せぬエラーが発生しました。']), 500, ['Content-Type' => 'application/json']);
            }
        } else {
            // 不正な日付フォーマット: Return 400 Bad Request
            return Response::forge(json_encode(['error' => '日付の形式が無効です (YYYY-MM-DD)。']), 400, ['Content-Type' => 'application/json']);
        }
    }

} 