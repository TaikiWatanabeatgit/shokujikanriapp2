<!-- fuel/app/views/mealrecord/summary.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo Security::htmlentities($title); ?></title>
    <?php echo Asset::css('style.css'); // アセットのパス ?>
    <style>
        /* 簡単なスタイル */
        body { font-family: sans-serif; }
        .summary-section { margin-bottom: 30px; padding: 15px; border: 1px solid #eee; border-radius: 5px; }
        .summary-section h2 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .summary-stats p { margin: 10px 0; font-size: 1.1em; }
        .summary-stats strong { display: inline-block; min-width: 150px; }
        .record-list { list-style: none; padding-left: 0; }
        .record-list li { margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dotted #eee; }
        .record-list .meal-type { font-weight: bold; text-transform: capitalize; }
        .record-list .calories { color: #555; font-size: 0.9em; }
        nav > a { margin-right: 15px; text-decoration: none; color: #007bff; }
        nav { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1><?php echo Security::htmlentities($title); ?></h1>

    <nav>
        <?php echo Html::anchor('meal-records', '一覧に戻る'); ?>
        <?php echo Html::anchor('meal-records/create', '新規作成'); ?>
        <?php echo Html::anchor('meal-records/search', '検索'); ?>
    </nav>

    <div class="summary-section summary-stats">
        <h2>統計情報</h2>
        <p><strong>今月の合計カロリー:</strong> <?php echo Security::htmlentities(number_format($monthlyTotalCalories)); ?> kcal</p>
        <p><strong>過去の平均カロリー:</strong> <?php echo Security::htmlentities(number_format($pastAverageCalories)); ?> kcal/日</p>
    </div>

    <div class="summary-section">
        <h2>今日の記録 (<?php echo date('Y年m月d日'); ?>)</h2>
        <?php if ($todayRecords && count($todayRecords) > 0): ?>
            <ul class="record-list">
                <?php foreach ($todayRecords as $record): ?>
                    <li>
                        <span class="meal-type"><?php echo Security::htmlentities($record['meal_type']); ?>:</span>
                        <?php echo Security::htmlentities($record['food_name']); ?>
                        <?php if ($record['calories'] !== null): ?>
                            <span class="calories">(<?php echo Security::htmlentities($record['calories']); ?> kcal)</span>
                        <?php endif; ?>
                        <?php if (!empty($record['notes'])): ?>
                            <br><small>メモ: <?php echo nl2br(Security::htmlentities($record['notes'])); ?></small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>今日の記録はありません。</p>
        <?php endif; ?>
    </div>

    <div class="summary-section">
        <h2>過去の記録 (今日以外)</h2>
        <?php if ($pastRecords && count($pastRecords) > 0): ?>
            <ul class="record-list">
                <?php foreach ($pastRecords as $record): ?>
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
            <?php // 過去の記録が多い場合、ページネーションや表示件数制限を検討 ?>
        <?php else: ?>
            <p>過去の記録はありません。</p>
        <?php endif; ?>
    </div>

</body>
</html> 