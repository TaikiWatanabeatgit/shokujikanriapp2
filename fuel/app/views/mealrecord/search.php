<!-- fuel/app/views/mealrecord/search.php -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Assuming you have a base template or load assets globally -->
    <!-- If not, add Asset::css(...) links here -->
    <title><?php echo Security::htmlentities($title); ?></title>
    <style>
        /* Basic styling for layout and feedback */
        .search-container { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9; }
        .search-container h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .search-forms { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; }
        .search-form { flex: 1; min-width: 250px; padding: 15px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="date"],
        .form-group input[type="text"] { width: calc(100% - 22px); padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .form-group button { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .form-group button:hover { background-color: #0056b3; }
        .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .no-results { color: #6c757d; background-color: #e2e3e5; border: 1px solid #d6d8db; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .loading, #dateResultArea .loading { text-align: center; padding: 20px; font-style: italic; color: #666; display: none; /* Initially hidden */ }
        .meal-list { margin-top: 20px; }
        .meal-item { border: 1px solid #eee; padding: 15px; margin-bottom: 15px; border-radius: 4px; background-color: #fff; }
        .meal-item h3 { margin-top: 0; font-size: 1.1em; color: #333; }
        .meal-item p { margin: 5px 0; color: #555; }
        .meal-item .calories { color: #888; font-size: 0.9em; }
        .meal-item ul { padding-left: 20px; margin-top: 10px;}
        .meal-item ul li { margin-bottom: 5px; }
        /* Add more styles as needed */
    </style>
</head>
<body>

    <h1><?php echo Security::htmlentities($title); ?></h1>

    <!-- Navigation Links (Example - adapt as needed) -->
    <div class="nav-links" style="margin-bottom: 20px;">
        <a href="<?php echo Uri::create('mealrecord'); ?>">記録一覧</a>
        <a href="<?php echo Uri::create('mealrecord/create'); ?>">記録作成</a>
        <a href="<?php echo Uri::create('mealrecord/summary'); ?>">記録サマリー</a>
        <!-- Add other links like user info if applicable -->
    </div>

    <!-- Display general error messages from controller (e.g., CSRF error, validation failure) -->
    <?php if (Session::get_flash('error')): ?>
        <div class="error-message"><?php echo implode('<br>', (array) Session::get_flash('error')); ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): // Specific error from search logic ?>
        <div class="error-message"><?php echo Security::htmlentities($error_message); ?></div>
    <?php endif; ?>

    <div class="search-forms">

        <!-- Date Search Form (AJAX) -->
        <div class="search-form" id="dateSearchContainer">
            <h3>日付で検索</h3>
            <?php echo Form::open(['id' => 'dateSearchForm', 'action' => Uri::create('mealrecord/search_date'), 'method' => 'post']); ?>
            <?php echo Form::csrf(); // Add CSRF token ?>
            <div class="form-group">
                <?php echo Form::label('検索日', 'search_date'); ?>
                <?php echo Form::input('search_date', '', ['type' => 'date', 'id' => 'search_date', 'required' => true]); ?>
            </div>
            <div class="form-group">
                <?php echo Form::button('submit', '日付で検索', ['type' => 'submit', 'class' => 'btn btn-primary']); ?>
            </div>
            <?php echo Form::close(); ?>
            <div id="dateResultArea">
                <div class="loading">検索中...</div>
                <!-- AJAX results will be injected here -->
            </div>
         </div>

        <!-- Name Search Form -->
        <div class="search-form" id="nameSearchContainer">
            <h3>料理名で検索</h3>
            <?php echo Form::open(['action' => Uri::create('mealrecord/search'), 'method' => 'post']); ?>
            <?php echo Form::csrf(); ?>
            <?php echo Form::hidden('search_type', 'name'); // Hidden field to identify the search type ?>
            <div class="form-group">
                <?php echo Form::label('料理名', 'search_name'); ?>
                <?php echo Form::input('search_name', Security::htmlentities(isset($search_name) ? $search_name : ''), ['id' => 'search_name', 'required' => true, 'placeholder' => '例: カレー']); ?>
            </div>
            <div class="form-group">
                <?php echo Form::button('submit', '料理名で検索', ['type' => 'submit', 'class' => 'btn btn-primary']); ?>
            </div>
            <?php echo Form::close(); ?>
        </div>

        <!-- Date Range Search Form -->
        <div class="search-form" id="rangeSearchContainer">
             <h3>期間で検索</h3>
             <?php echo Form::open(['action' => Uri::create('mealrecord/search'), 'method' => 'post']); ?>
             <?php echo Form::csrf(); ?>
             <?php echo Form::hidden('search_type', 'range'); // Hidden field for type ?>
             <div class="form-group">
                 <?php echo Form::label('開始日', 'start_date'); ?>
                 <?php echo Form::input('start_date', Security::htmlentities(isset($start_date) ? $start_date : ''), ['type' => 'date', 'id' => 'start_date', 'required' => true]); ?>
             </div>
             <div class="form-group">
                 <?php echo Form::label('終了日', 'end_date'); ?>
                 <?php echo Form::input('end_date', Security::htmlentities(isset($end_date) ? $end_date : ''), ['type' => 'date', 'id' => 'end_date', 'required' => true]); ?>
             </div>
             <div class="form-group">
                 <?php echo Form::button('submit', '期間で検索', ['type' => 'submit', 'class' => 'btn btn-primary']); ?>
             </div>
             <?php echo Form::close(); ?>
        </div>

    </div>

    <!-- Results Area for Name and Range Search -->
    <?php if (isset($search_type) && ($search_type === 'name' || $search_type === 'range') && isset($records)): ?>
        <div class="search-results">
            <?php if ($search_type === 'name'): ?>
                <h2>「<?php echo Security::htmlentities($search_name); ?>」の検索結果</h2>
            <?php elseif ($search_type === 'range'): ?>
                 <h2><?php echo Security::htmlentities($start_date); ?> から <?php echo Security::htmlentities($end_date); ?> の検索結果</h2>
            <?php endif; ?>

            <?php if (empty($records)): ?>
                <div class="no-results">
                     該当する記録は見つかりませんでした。
                </div>
            <?php else: ?>
                <div class="meal-list">
                    <?php
                        // Group records by date for display
                        $grouped_records = [];
                        foreach ($records as $record) {
                            $date = $record['date']; // Assuming 'date' field exists
                            if (!isset($grouped_records[$date])) {
                                $grouped_records[$date] = [];
                            }
                            $grouped_records[$date][] = $record;
                        }
                        ksort($grouped_records); // Sort by date
                    ?>
                    <?php foreach ($grouped_records as $date => $daily_records): ?>
                        <div class="meal-item">
                            <h3><?php echo Security::htmlentities($date); ?></h3>
                            <ul>
                                <?php foreach ($daily_records as $record): ?>
                                    <li>
                                        <?php echo Security::htmlentities(ucfirst($record['meal_type'])); // Breakfast, Lunch, etc. ?>:
                                        <?php echo Security::htmlentities($record['food_name']); ?>
                                        <?php if (isset($record['calories']) && $record['calories'] !== null): ?>
                                            <span class="calories">(<?php echo Security::htmlentities($record['calories']); ?>kcal)</span>
                                        <?php endif; ?>
                                        <?php if (!empty($record['notes'])): ?>
                                            <br><small>メモ: <?php echo Security::htmlentities($record['notes']); ?></small>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>


    <!-- Basic AJAX for Date Search -->
    <?php // Consider moving this to a separate JS file using Asset::js() ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateSearchForm = document.getElementById('dateSearchForm');
            const dateResultArea = document.getElementById('dateResultArea');
            const loadingIndicator = dateResultArea.querySelector('.loading');

            if (dateSearchForm) {
                dateSearchForm.addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent traditional form submission
                    loadingIndicator.style.display = 'block'; // Show loading
                    dateResultArea.innerHTML = ''; // Clear previous results before showing loading
                    dateResultArea.appendChild(loadingIndicator);


                    const formData = new FormData(dateSearchForm);
                    // Add CSRF token manually if not included automatically by FormData in your setup
                    // formData.append('fuel_csrf_token', document.querySelector('input[name="fuel_csrf_token"]').value);

                    fetch(dateSearchForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest' // Important for Input::is_ajax()
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        loadingIndicator.style.display = 'none'; // Hide loading
                        dateResultArea.innerHTML = ''; // Clear loading indicator

                        if (data.error) {
                            dateResultArea.innerHTML = `<div class="error-message">${escapeHtml(data.error)}</div>`;
                        } else if (Array.isArray(data) && data.length > 0) {
                            let html = '<h4>検索結果</h4><div class="meal-list">';
                            // Group by date (though likely only one date from this search)
                            let grouped = {};
                            data.forEach(record => {
                                if (!grouped[record.date]) grouped[record.date] = [];
                                grouped[record.date].push(record);
                            });

                            for(const date in grouped) {
                                html += `<div class="meal-item"><h3>${escapeHtml(date)}</h3><ul>`;
                                grouped[date].forEach(record => {
                                    html += `<li>
                                                ${escapeHtml(ucfirst(record.meal_type))}:
                                                ${escapeHtml(record.food_name)}
                                                ${record.calories !== null ? `<span class="calories">(${escapeHtml(record.calories)}kcal)</span>` : ''}
                                                ${record.notes ? `<br><small>メモ: ${escapeHtml(record.notes)}</small>` : ''}
                                             </li>`;
                                });
                                html += `</ul></div>`;
                            }
                            html += '</div>';
                            dateResultArea.innerHTML = html;

                        } else {
                            dateResultArea.innerHTML = '<div class="no-results">該当する記録は見つかりませんでした。</div>';
                        }
                    })
                    .catch(error => {
                        loadingIndicator.style.display = 'none'; // Hide loading
                        console.error('Fetch error:', error);
                        dateResultArea.innerHTML = `<div class="error-message">検索中にエラーが発生しました。(${escapeHtml(error.message)})</div>`;
                    });
                });
            }
        });

        // Simple HTML escaping function
        function escapeHtml(unsafe) {
            if (unsafe === null || typeof unsafe === 'undefined') return '';
            return unsafe
                 .toString()
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        // Simple ucfirst function
        function ucfirst(string) {
            if (!string) return '';
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

    </script>

</body>
</html> 