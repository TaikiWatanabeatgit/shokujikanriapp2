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

    <?php if (isset($summary_announcement)): ?>
        <p>
            <?php echo $summary_announcement; ?>
        </p>
        
    <?php endif; ?>
    
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
        <!-- Knockout: 過去記録リスト -->
        <ul class="record-list" data-bind="foreach: pastRecords">
            <li>
                <strong data-bind="text: date"></strong> -
                <span class="meal-type" data-bind="text: meal_type + ':'"></span>
                <span data-bind="text: food_name"></span>
                <!-- Knockout: カロリー表示 (nullでない場合) -->
                <!-- ko if: calories !== null -->
                <span class="calories" data-bind="text: '(' + calories + ' kcal)'"></span>
                <!-- /ko -->
            </li>
        </ul>
        <!-- Knockout: もっと見るボタン -->
        <button data-bind="click: loadMore, visible: hasMore() && !isLoading(), enable: !isLoading()">もっと見る</button>
        <!-- Knockout: 読み込み中インジケーター -->
        <p data-bind="visible: isLoading">読み込み中...</p>
        <!-- Knockout: 記録がない場合のメッセージ -->
        <p data-bind="visible: !isLoading() && pastRecords().length === 0 && !hasMore()">過去の記録はありません。</p>
    </div>

<!-- Knockout.js ライブラリ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.1/knockout-latest.js"></script>

<script type="text/javascript">
    // サーバーサイドからの初期データを取得
    const initialPastRecords = <?php echo json_encode($pastRecords); ?>;
    const initialOffset = <?php echo (int)$initialPastRecordCount; ?>;
    const recordsPerPage = <?php echo (int)$recordsPerPage; ?>;
    const loadMoreUrl = '<?php echo Uri::create("mealrecord/load_more_past_records"); ?>';
    const hasInitialMore = initialPastRecords.length >= recordsPerPage; // 最初の読み込みで限界まで読み込んだか

    function PastRecordsViewModel() {
        var self = this;

        // Observable プロパティ
        self.pastRecords = ko.observableArray(initialPastRecords); // 過去の記録リスト
        self.isLoading = ko.observable(false); // 読み込み中フラグ
        self.currentOffset = ko.observable(initialOffset); // 現在のオフセット
        self.hasMore = ko.observable(hasInitialMore); // さらに読み込めるかフラグ

        // 「もっと見る」アクション
        self.loadMore = async function() {
            if (self.isLoading() || !self.hasMore()) {
                return; // 読み込み中、またはこれ以上ない場合は何もしない
            }

            self.isLoading(true);

            try {
                const url = `${loadMoreUrl}?offset=${self.currentOffset()}&limit=${recordsPerPage}`;
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const newRecords = await response.json();

                if (newRecords && newRecords.length > 0) {
                    // 新しい記録を既存のリストに追加
                    newRecords.forEach(record => self.pastRecords.push(record));
                    // オフセットを更新
                    self.currentOffset(self.currentOffset() + newRecords.length);
                    // 取得した件数がリミット未満なら、もうないと判断
                    if (newRecords.length < recordsPerPage) {
                        self.hasMore(false);
                    }
                } else {
                    // 0件返ってきたら、もうないと判断
                    self.hasMore(false);
                }

            } catch (error) {
                console.error('Error loading more records:', error);
                alert('記録の読み込みに失敗しました。');
                // エラーが起きても hasMore は true のままにしておく (リトライできるように)
            } finally {
                self.isLoading(false);
            }
        };
    }

    // ViewModelを適用
    ko.applyBindings(new PastRecordsViewModel());
</script>

</body>
</html> 