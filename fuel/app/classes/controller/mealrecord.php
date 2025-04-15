<?php

class Controller_Mealrecord extends Controller // 必要なら Controller_Template などに変更
{
    /**
     * 一覧表示
     */
    public function action_index()
    {
        // ページネーション設定
        $config = array(
            'pagination_url' => Uri::create('meal-records'),
            'total_items'    => Model_Mealrecord::count_all(),
            'per_page'       => 10, // 1ページあたりの表示件数
            'uri_segment'    => 'page', // URLセグメント名 (例: /meal-records/page/2)
            // 必要に応じて Bootstrap 用の設定を追加
            // 'num_links' => 5,
            // 'name' => 'bootstrap3',
            // 'template' => array(
            //     'wrapper_start' => '<ul class="pagination">', 
            //     'wrapper_end' => '</ul>', 
            //     'page_start' => '<li>', 
            //     'page_end' => '</li>', 
            //     'active_start' => '<li class="active"><span>', 
            //     'active_end' => '</span></li>', 
            //     'previous_start' => '<li>', 
            //     'previous_end' => '</li>', 
            //     'next_start' => '<li>', 
            //     'next_end' => '</li>', 
            // ),
        );

        // ページネーションインスタンス生成
        $pagination = Pagination::forge('mealrecord_pagination', $config);

        // データ取得
        $mealRecords = Model_Mealrecord::get_paged(
            $pagination->per_page,
            $pagination->offset
        );

        // Viewに渡すデータ
        $data = array();
        $data['title'] = '食事記録一覧';
        $data['mealRecords'] = $mealRecords;
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
            Response::redirect('meal-records/create');
        }

        // バリデーション設定
        $val = Validation::forge('mealrecord_create');
        $val->add_field('date', '日付', 'required|valid_date[Y-m-d]');
        $val->add_field('meal_type', '種類', 'required|in_array[breakfast,lunch,dinner,snack]');
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
                    Response::redirect('meal-records');
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
            Response::redirect('meal-records');
        }

        // レコード検索 (★変更: クエリビルダ版メソッド)
        $mealRecord = Model_Mealrecord::find_by_id($id);

        // レコードが見つからない場合
        if (!$mealRecord) {
            Session::set_flash('error', '指定された食事記録が見つかりません。');
            Response::redirect('meal-records');
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
            Response::redirect('meal-records');
        }

        // ★変更: クエリビルダ版メソッドで存在確認のみ行う
        $mealRecordExists = Model_Mealrecord::find_by_id($id);
        if (!$mealRecordExists) {
            Session::set_flash('error', '指定された食事記録が見つかりません。');
            Response::redirect('meal-records');
        }

        // POSTリクエストでない場合は edit へリダイレクト
        if (Input::method() !== 'POST') {
            Response::redirect('meal-records/edit/' . $id);
        }

        // バリデーション設定
        $val = Validation::forge('mealrecord_edit');
        $val->add_field('date', '日付', 'required|valid_date[Y-m-d]');
        $val->add_field('meal_type', '種類', 'required|in_array[breakfast,lunch,dinner,snack]');
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
                    Response::redirect('meal-records');
                } else {
                    // Model_Mealrecord::update_record は変更があった場合のみtrueを返す想定
                    // エラーでなければ「変更がなかった」か「失敗」
                    // ここではシンプルに「更新されなかった」とするか、詳細なエラーハンドリングを行う
                    Session::set_flash('notice', 'データに変更がないか、更新に失敗しました。');
                     // 更新されなくても一覧に戻す場合
                     Response::redirect('meal-records');
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
        // IDチェック
        if ($id === null || !ctype_digit((string)$id)) {
            Session::set_flash('error', '無効なIDです。');
            Response::redirect('meal-records');
        }

        // POSTリクエスト & CSRFチェック
        if (Input::method() === 'POST') {
            if (!Security::check_token()) {
                Session::set_flash('error', '不正なリクエストです。もう一度お試しください。');
                Response::redirect('meal-records');
            }

            // ★変更: 存在確認 (delete_record内でidチェックはされるが、事前に確認)
            $mealRecordExists = Model_Mealrecord::find_by_id($id);

            if ($mealRecordExists) {
                try {
                    // ★変更: クエリビルダ版の削除メソッド呼び出し
                    if (Model_Mealrecord::delete_record($id)) {
                        Session::set_flash('success', '食事記録が正常に削除されました。');
                    } else {
                        // delete_record が false を返すのは、DBエラーか影響行数0の場合
                        Session::set_flash('error', '食事記録の削除に失敗しました。');
                    }
                } catch (\Database_Exception $e) { // モデル側で catch してるが念のため
                    Session::set_flash('error', 'データベースエラーが発生しました。');
                    Log::error('Database error in destroy: ' . $e->getMessage());
                } catch (\Exception $e) { // 予期せぬエラー
                    Session::set_flash('error', '予期せぬエラーが発生しました。');
                    Log::error('Unexpected error in destroy: ' . $e->getMessage());
                }
            } else {
                Session::set_flash('error', '指定された食事記録が見つかりません。');
            }
        } else {
            // POST以外でのアクセスは許可しない (変更なし)
            Session::set_flash('error', '不正なアクセス方法です。');
        }

        // 処理後、一覧へリダイレクト (変更なし)
        Response::redirect('meal-records');
    }

    /**
     * サマリー表示
     */
    public function action_summary()
    {
        $today = date('Y-m-d');
        $current_year = date('Y');
        $current_month = date('m');

        // ★変更: クエリビルダ版メソッド呼び出し
        $todayRecords = Model_Mealrecord::find_by_date($today);

        // ★変更: クエリビルダ版メソッド呼び出し
        $pastRecords = Model_Mealrecord::find_before_date($today);

        // ★変更: クエリビルダ版メソッド呼び出し
        $monthlyTotalCalories = Model_Mealrecord::sum_calories_for_month($current_year, $current_month);

        // ★変更: クエリビルダ版メソッド呼び出し
        $past_stats = Model_Mealrecord::get_past_calories_stats($today);
        $past_total_calories = $past_stats['total'];
        $past_count = $past_stats['count'];

        $pastAverageCalories = ($past_count > 0) ? round($past_total_calories / $past_count) : 0;


        // Viewに渡すデータ (変更なし、中身は配列になる)
        $data = array();
        $data['title'] = '食事記録サマリー';
        $data['todayRecords'] = $todayRecords;
        $data['pastRecords'] = $pastRecords;
        $data['monthlyTotalCalories'] = $monthlyTotalCalories;
        $data['pastAverageCalories'] = $pastAverageCalories;

        // Viewを生成して返す (変更なし)
        return Response::forge(View::forge('mealrecord/summary', $data));
    }

    /**
     * 検索ページ表示・処理 (料理名検索)
     */
    public function action_search()
    {
        $data = array();
        $data['title'] = '食事記録検索';
        $data['search_name'] = '';
        $data['records'] = null;
        $data['error_message'] = '';

        // 料理名検索のPOSTリクエスト処理
        if (Input::method() === 'POST' && Input::post('search_type') === 'name') {
            if (!Security::check_token()) {
                 $data['error_message'] = '不正なリクエストです。ページを再読み込みしてください。';
            } else {
                $search_name = Input::post('search_name');
                $data['search_name'] = $search_name;

                if (empty($search_name)) {
                    $data['error_message'] = '料理名を入力してください。';
                } else {
                    try {
                        // ★変更: クエリビルダ版メソッドで検索
                        $data['records'] = Model_Mealrecord::search_by_name($search_name);

                        if (empty($data['records'])) {
                             // ★変更: Html::chars を Security::htmlentities に
                             $data['error_message'] = Security::htmlentities($search_name) . ' を含む記録は見つかりませんでした。';
                        }
                    } catch (\Database_Exception $e) {
                        $data['error_message'] = '検索中にエラーが発生しました。';
                        Log::error('Database error in search (name): ' . $e->getMessage());
                    } catch (\Exception $e) {
                        $data['error_message'] = '検索中に予期せぬエラーが発生しました。';
                        Log::error('Unexpected error in search (name): ' . $e->getMessage());
                    }
                }
            }
        }

        // Viewを生成して返す (変更なし)
        return Response::forge(View::forge('mealrecord/search', $data));
    }

    /**
     * 日付検索API (AJAX用)
     */
    public function post_search_date()
    {
        // AJAXリクエストかつPOSTかチェック (変更なし)
        if (!Input::is_ajax() || Input::method() !== 'POST') {
            return $this->response(array('error' => 'Invalid request'), 400);
        }

        // CSRF チェック (省略)

        $search_date = Input::post('search_date');

        // 日付フォーマット検証 (変更なし)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search_date)) {
            try {
                 // ★変更: クエリビルダ版メソッドで検索
                $records = Model_Mealrecord::find_by_date($search_date);

                // ★変更: 既に配列なのでそのまま返す
                return $this->response($records, 200);

            } catch (\Database_Exception $e) {
                 Log::error('Database error in search (date): ' . $e->getMessage());
                 return $this->response(array('error' => 'Search error occurred'), 500);
            } catch (\Exception $e) {
                 Log::error('Unexpected error in search (date): ' . $e->getMessage());
                 return $this->response(array('error' => 'Unexpected error occurred'), 500);
            }
        } else {
            return $this->response(array('error' => 'Invalid date format'), 400);
        }
    }

} 