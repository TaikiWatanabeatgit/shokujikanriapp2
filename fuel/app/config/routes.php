<?php
return array(
	'_root_'  => 'welcome/index',  // The default route
	'_404_'   => 'welcome/404',    // The main 404 route
	
	'hello(/:name)?' => array('welcome/hello', 'name' => 'hello'),

	// Meal Record Routes
	'meal-records' => 'mealrecord/index', // 一覧 (GET)
	'meal-records/create' => 'mealrecord/create', // 作成フォーム (GET)
	'meal-records/store' => 'mealrecord/store', // 保存 (POST)
	'meal-records/edit/(:num)' => 'mealrecord/edit/$1', // 編集フォーム (GET)
	'meal-records/update/(:num)' => 'mealrecord/update/$1', // 更新 (POST)
	'meal-records/delete/(:num)' => 'mealrecord/destroy/$1', // 削除 (POST想定だが、GETでも動くように)
	'meal-records/summary' => 'mealrecord/summary', // サマリー (GET)
	'meal-records/search' => 'mealrecord/search', // 検索ページ (GET, POST)
	'api/search' => 'mealrecord/search_date', // 日付検索API (POST)
);
