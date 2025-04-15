<!-- fuel/app/views/mealrecord/search.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo Security::htmlentities($title); ?></title>
    <?php echo Asset::css('style.css'); // アセットパス ?>
    <?php echo Asset::js('search.js'); // アセットパス ?>
    <style>
        /* search.php 固有スタイル */
        body { font-family: sans-serif; }
        .search-forms { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .search-form { border: 1px solid #eee; padding: 15px; border-radius: 5px; flex: 1; min-width: 250px; }
        .form-group { margin-bottom: 10px; }
        label { display: inline-block; margin-right: 5px; }
        input[type="date"], input[type="text"] { padding: 5px; border: 1px solid #ccc; border-radius: 3px; }
        button { padding: 6px 12px; background-color: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        #dateResults { margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; }
        #dateResults.loading::before { content: '読み込み中...'; display: block; text-align: center; color: #888; }
        .results-list { list-style: none; padding: 0; }
        .results-list li { border-bottom: 1px dotted #ccc; padding: 10px 0; }
        .meal-type { font-weight: bold; text-transform: capitalize; }
        .calories { color: #555; font-size: 0.9em; }
        .error-message { color: #dc3545; margin-top: 10px; padding: 10px; border: 1px solid #f5c6cb; background-color: #f8d7da; border-radius: 4px; }
        .no-results { margin-top: 20px; font-style: italic; color: #666; }
        nav > a { margin-right: 15px; text-decoration: none; color: #007bff; }
        nav { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1><?php echo Security::htmlentities($title); ?></h1>

    <nav>
        <?php echo Html::anchor('meal-records', '一覧に戻る'); ?>
        <?php echo Html::anchor('meal-records/create', '新規作成'); ?>
        <?php echo Html::anchor('meal-records/summary', 'サマリー表示'); ?>
    </nav>

    <?php // エラーメッセージ表示 (コントローラから渡されたもの)
        if (!empty($error_message) && Input::method() === 'POST'): ?>
        <div class="error-message">
            <?php echo $error_message; // コントローラ側でエスケープ済み想定 (要確認)
                 // もし未エスケープなら Security::htmlentities($error_message) にする
            ?>
        </div>
    <?php endif; ?>

    <div class="search-forms">
        <!-- 日付検索フォーム (AJAX) -->
        <?php echo Form::open(array('action' => 'api/search', 'method' => 'post', 'id' => 'dateSearchForm', 'class' => 'search-form')); ?>
            <h3>日付で検索</h3>
            <?php // CSRFトークンはJS側で処理するか、API側で不要にする ?>
            <div class="form-group">
                <?php echo Form::label('検索日:', 'search_date'); ?>
                <?php echo Form::input('search_date', Input::get('date', date('Y-m-d')), array('type' => 'date', 'id' => 'search_date', 'required' => 'required')); ?>
            </div>
            <button type="submit">検索</button>
        <?php echo Form::close(); ?>

        <!-- 料理名検索フォーム (通常のPOST) -->
        <?php echo Form::open(array('action' => 'meal-records/search', 'method' => 'post', 'class' => 'search-form')); ?>
            <h3>料理名で検索</h3>
            <?php echo Form::csrf(); // CSRF ?>
            <?php echo Form::hidden('search_type', 'name'); ?>
            <div class="form-group">
                <?php echo Form::label('料理名:', 'search_name'); ?>
                <?php echo Form::input('search_name', $search_name, array('type' => 'text', 'id' => 'search_name', 'required' => 'required')); ?>
            </div>
            <button type="submit">検索</button>
        <?php echo Form::close(); ?>
    </div>

    <!-- 日付検索結果表示エリア -->
    <div id="dateResults"></div>

    <!-- 料理名検索結果表示 -->
    <?php if (Input::method() === 'POST' && Input::post('search_type') === 'name' && empty($error_message)): ?>
        <h2>料理名「<?php echo Security::htmlentities($search_name); ?>」の検索結果</h2>
        <?php if ($records && count($records) > 0): ?>
            <ul class="results-list">
                <?php foreach ($records as $record): ?>
                    <li>
                        <strong><?php echo Security::htmlentities($record['date']); ?></strong> - 
                        <span class="meal-type"><?php echo Security::htmlentities($record['meal_type']); ?>:</span>
                        <?php echo Security::htmlentities($record['food_name']); ?>
                        <?php if ($record['calories'] !== null): ?>
                            <span class="calories">(<?php echo Security::htmlentities($record['calories']); ?> kcal)</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-results">該当する記録は見つかりませんでした。</p>
        <?php endif; ?>
    <?php endif; ?>

</body>
</html> 