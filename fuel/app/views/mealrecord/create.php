<!-- fuel/app/views/mealrecord/create.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo Security::htmlentities($title); ?></title>
    <?php echo Asset::css('style.css'); // アセットのパスは適宜調整してください ?>
    <style>
        /* 簡単なフォームスタイル */
        body { font-family: sans-serif; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"],
        input[type="date"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* paddingを含めてwidth 100% に */
        }
        textarea { min-height: 80px; }
        .error-message { color: #dc3545; font-size: 0.9em; margin-top: 5px; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .btn { display: inline-block; padding: 10px 15px; margin-top: 10px; font-size: 16px; font-weight: bold; text-align: center; cursor: pointer; border: 1px solid transparent; border-radius: 4px; text-decoration: none; }
        .btn-primary { color: #fff; background-color: #007bff; border-color: #007bff; }
        .btn-secondary { color: #fff; background-color: #6c757d; border-color: #6c757d; }
    </style>
</head>
<body>
    <h1><?php echo Security::htmlentities($title); ?></h1>

    <?php // バリデーションエラー表示 (Session flash を使う場合)
        if (Session::get_flash('error')): ?>
        <div class="alert alert-danger">
            <strong>エラー:</strong><br>
            <?php echo nl2br(Security::htmlentities(Session::get_flash('error'))); ?>
        </div>
    <?php endif; ?>

    <?php echo Form::open(array('action' => 'mealrecord/store', 'method' => 'post')); ?>

    <?php echo Form::csrf(); // CSRF対策トークン ?>

    <div class="form-group">
        <?php echo Form::label('日付', 'date'); ?>
        <?php echo Form::input('date', $val->validated('date') ?? Input::post('date', date('Y-m-d')), array('id' => 'form_date', 'required' => 'required')); ?>
        <?php if ($val->error('date')): ?>
            <p class="error-message"><?php echo $val->error('date')->get_message(); ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?php echo Form::label('種類', 'meal_type'); ?>
        <?php echo Form::select('meal_type', $val->validated('meal_type') ?? Input::post('meal_type'), array(
            '' => '選択してください',
            'breakfast' => '朝食',
            'lunch' => '昼食',
            'dinner' => '夕食',
            'snack' => '間食',
        ), array('id' => 'form_meal_type', 'required' => 'required')); ?>
        <?php if ($val->error('meal_type')): ?>
            <p class="error-message"><?php echo $val->error('meal_type')->get_message(); ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?php echo Form::label('料理名', 'food_name'); ?>
        <?php echo Form::input('food_name', $val->validated('food_name') ?? Input::post('food_name'), array('id' => 'form_food_name', 'required' => 'required', 'maxlength' => '255')); ?>
        <?php if ($val->error('food_name')): ?>
            <p class="error-message"><?php echo $val->error('food_name')->get_message(); ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?php echo Form::label('カロリー (kcal)', 'calories'); ?>
        <?php echo Form::input('calories', $val->validated('calories') ?? Input::post('calories'), array('id' => 'form_calories', 'type' => 'number', 'min' => '0')); ?>
        <?php if ($val->error('calories')): ?>
            <p class="error-message"><?php echo $val->error('calories')->get_message(); ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?php echo Form::label('メモ', 'notes'); ?>
        <?php echo Form::textarea('notes', $val->validated('notes') ?? Input::post('notes'), array('id' => 'form_notes', 'rows' => 4)); ?>
        <?php if ($val->error('notes')): ?>
            <p class="error-message"><?php echo $val->error('notes')->get_message(); ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?php echo Form::submit('submit', '保存', array('class' => 'btn btn-primary')); ?>
        <?php echo Html::anchor('mealrecord', 'キャンセル', array('class' => 'btn btn-secondary')); ?>
    </div>

    <?php echo Form::close(); ?>

</body>
</html> 