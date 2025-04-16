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
        <?php echo Html::anchor('mealrecord', '一覧に戻る'); ?>
        <?php echo Html::anchor('mealrecord/create', '新規作成'); ?>
        <?php echo Html::anchor('mealrecord/search', '検索'); ?>
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
            <ul class="record-list" id="past-records-list">
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
            <?php if ($initialPastRecordCount >= $recordsPerPage): ?>
                <button id="load-more-btn" data-offset="<?php echo $initialPastRecordCount; ?>" data-limit="<?php echo $recordsPerPage; ?>">もっと見る</button>
                <p id="loading-indicator" style="display: none;">読み込み中...</p>
            <?php endif; ?>
        <?php else: ?>
            <p>過去の記録はありません。</p>
        <?php endif; ?>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const loadMoreButton = document.getElementById('load-more-btn');
    const pastRecordsList = document.getElementById('past-records-list');
    const loadingIndicator = document.getElementById('loading-indicator');

    if (loadMoreButton) {
        loadMoreButton.addEventListener('click', async () => {
            const offset = parseInt(loadMoreButton.dataset.offset, 10);
            const limit = parseInt(loadMoreButton.dataset.limit, 10);

            loadMoreButton.disabled = true;
            if (loadingIndicator) loadingIndicator.style.display = 'block';

            try {
                const response = await fetch(`<?php echo Uri::create('mealrecord/load_more_past_records'); ?>?offset=${offset}&limit=${limit}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const newRecords = await response.json();

                if (newRecords && newRecords.length > 0) {
                    newRecords.forEach(record => {
                        const li = document.createElement('li');

                        const strong = document.createElement('strong');
                        strong.textContent = record.date;

                        const mealTypeSpan = document.createElement('span');
                        mealTypeSpan.className = 'meal-type';
                        mealTypeSpan.textContent = record.meal_type + ':';

                        li.appendChild(strong);
                        li.appendChild(document.createTextNode(' - '));
                        li.appendChild(mealTypeSpan);
                        li.appendChild(document.createTextNode(' '));
                        li.appendChild(document.createTextNode(record.food_name));

                        if (record.calories !== null) {
                            const caloriesSpan = document.createElement('span');
                            caloriesSpan.className = 'calories';
                            caloriesSpan.textContent = `(${record.calories} kcal)`;
                            li.appendChild(document.createTextNode(' '));
                            li.appendChild(caloriesSpan);
                        }

                        pastRecordsList.appendChild(li);
                    });

                    loadMoreButton.dataset.offset = offset + newRecords.length;

                    if (newRecords.length < limit) {
                        loadMoreButton.style.display = 'none';
                    } else {
                         loadMoreButton.disabled = false;
                    }

                } else {
                    loadMoreButton.style.display = 'none';
                }

            } catch (error) {
                console.error('Error loading more records:', error);
                alert('記録の読み込みに失敗しました。');
                 loadMoreButton.disabled = false;
            } finally {
                if (loadingIndicator) loadingIndicator.style.display = 'none';
            }
        });
    }
});
</script>

</body>
</html> 