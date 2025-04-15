<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo Html::chars($title); ?></title>
    <?php echo Asset::css('style.css'); // アセットのパスは適宜調整してください ?>
    <style>
        /* 簡単なテーブルスタイル */
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .actions a { margin-right: 5px; text-decoration: none; color: #007bff; }
        .actions a:hover { text-decoration: underline; }
        .pagination { margin-top: 20px; list-style: none; padding: 0; }
        .pagination li { display: inline; margin-right: 5px; }
        .pagination li a, .pagination li span { padding: 5px 10px; border: 1px solid #ddd; text-decoration: none; }
        .pagination li.active span { background-color: #007bff; color: white; border-color: #007bff; }
        .pagination li.disabled span { color: #ccc; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .btn { display: inline-block; padding: 6px 12px; margin-bottom: 0; font-size: 14px; font-weight: 400; line-height: 1.42857143; text-align: center; white-space: nowrap; vertical-align: middle; cursor: pointer; border: 1px solid transparent; border-radius: 4px; text-decoration: none; }
        .btn-primary { color: #fff; background-color: #007bff; border-color: #007bff; }
        nav > a { margin-right: 15px; text-decoration: none; color: #007bff; }
        nav { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1><?php echo Html::chars($title); ?></h1>

    <?php // フラッシュメッセージ表示
        if (Session::get_flash('success')): ?>
        <div class="alert alert-success">
            <?php echo nl2br(Html::chars(Session::get_flash('success'))); // XSS対策と改行反映 ?>
        </div>
    <?php endif; ?>
    <?php if (Session::get_flash('error')): ?>
        <div class="alert alert-danger">
            <?php echo nl2br(Html::chars(Session::get_flash('error'))); // XSS対策と改行反映 ?>
        </div>
    <?php endif; ?>

    <nav>
        <?php echo Html::anchor('meal-records/create', '新規作成', array('class' => 'btn btn-primary')); ?>
        <?php echo Html::anchor('meal-records/summary', 'サマリー表示'); ?>
        <?php echo Html::anchor('meal-records/search', '検索'); ?>
    </nav>

    <?php if ($mealRecords): ?>
        <table>
            <thead>
                <tr>
                    <th>日付</th>
                    <th>種類</th>
                    <th>料理名</th>
                    <th>カロリー</th>
                    <th>メモ</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mealRecords as $record): ?>
                    <tr>
                        <td><?php echo Html::chars($record->date); ?></td>
                        <td><?php echo Html::chars($record->meal_type); ?></td>
                        <td><?php echo Html::chars($record->food_name); ?></td>
                        <td><?php echo $record->calories !== null ? Html::chars($record->calories) . ' kcal' : '-'; ?></td>
                        <td><?php echo nl2br(Html::chars($record->notes ?? '')); // nullの場合は空文字 ?></td>
                        <td class="actions">
                            <?php // echo Html::anchor('meal-records/show/'. $record->id, '詳細'); // 必要なら ?>
                            <?php echo Html::anchor('meal-records/edit/'. $record->id, '編集'); ?>
                            <?php // 削除はPOST推奨。簡易的にJSで確認ダイアログを出す
                                echo Form::open(array('action' => 'meal-records/delete/'.$record->id, 'method' => 'post', 'style' => 'display:inline;', 'onsubmit' => "return confirm('本当に削除しますか？');"));
                                echo Form::button('delete', '削除', array('type' => 'submit', 'class' => 'btn btn-danger btn-xs')); // CSSで調整
                                echo Form::close();
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php echo $pagination; // Pagination::render() を呼び出す ?>
        </div>

    <?php else: ?>
        <p>食事記録はありません。</p>
    <?php endif; ?>

</body>
</html> 