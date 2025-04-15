<!-- fuel/app/views/mealrecord/edit.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo Security::htmlentities($title); ?></title>
    <?php echo Asset::css('style.css'); // アセットのパスは適宜調整してください ?>
    <style>
        /* create.php と同じスタイルを流用 or 共通化 */
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
            box-sizing: border-box;
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

    <?php // バリデーションエラー表示
        if (Session::get_flash('error')): ?>
        <div class="alert alert-danger">
            <strong>エラー:</strong><br>
            <?php echo nl2br(Security::htmlentities(Session::get_flash('error'))); ?>
        </div>
    <?php endif; ?>

    <?php echo Form::open(array('action' => 'meal-records/update/' . $mealRecord['id'], 'method' => 'post')); ?>

    <?php echo Form::csrf(); // CSRF対策トークン ?>

    <div class="form-group">
        <?php echo Form::label('日付', 'date'); ?>
        <?php // エラー時はPOST値優先、なければDBの値
            $date_value = $val->validated('date') ?? Input::post('date', $mealRecord['date']);
            echo Form::input('date', $date_value, array('id' => 'date', 'required' => 'required')); ?>
        <?php if ($val->error('date')): ?>
            <p class="error-message"><?php echo $val->error('date')->get_message(); ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?php echo Form::label('種類', 'meal_type'); ?>
        <?php
            $meal_type_value = $val->validated('meal_type') ?? Input::post('meal_type', $mealRecord['meal_type']);
            echo Form::select('meal_type', $meal_type_value, array(
            '' => '選択してください',
            'breakfast' => '朝食',
            'lunch' => '昼食',
            'dinner' => '夕食',
            'snack' => '間食',
        ), array('id' => 'meal_type', 'required' => 'required')); ?>
        <?php if ($val->error('meal_type')): ?>
            <p class="error-message"><?php echo $val->error('meal_type')->get_message(); ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?php echo Form::label('料理名', 'food_name'); ?>
        <?php
            $food_name_value = $val->validated('food_name') ?? Input::post('food_name', $mealRecord['food_name']);
            echo Form::input('food_name', $food_name_value, array('id' => 'food_name', 'required' => 'required', 'maxlength' => '255')); ?>
        <?php if ($val->error('food_name')): ?>
            <p class="error-message"><?php echo $val->error('food_name')->get_message(); ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?php echo Form::label('カロリー (kcal)', 'calories'); ?>
        <?php
            $calories_value = $val->validated('calories') ?? Input::post('calories', $mealRecord['calories']);
            echo Form::input('calories', $calories_value, array('id' => 'calories', 'type' => 'number', 'min' => '0')); ?>
        <?php if ($val->error('calories')): ?>
            <p class="error-message"><?php echo $val->error('calories')->get_message(); ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?php echo Form::label('メモ', 'notes'); ?>
        <?php
            $notes_value = $val->validated('notes') ?? Input::post('notes', $mealRecord['notes']);
            echo Form::textarea('notes', $notes_value, array('id' => 'notes', 'rows' => 4)); ?>
        <?php if ($val->error('notes')): ?>
            <p class="error-message"><?php echo $val->error('notes')->get_message(); ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?php echo Form::submit('submit', '更新', array('class' => 'btn btn-primary')); ?>
        <?php echo Html::anchor('meal-records', 'キャンセル', array('class' => 'btn btn-secondary')); ?>
    </div>

    <?php echo Form::close(); ?>

</body>
</html> 