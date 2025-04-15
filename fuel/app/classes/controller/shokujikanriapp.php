<?php

class Controller_Shokujikanriapp extends Controller
{
    public function action_index()
    {
        // Viewに渡すデータを定義 (今回はタイトルのみ)
        $data = array();
        $data['title'] = '食事管理アプリ';

        // Viewを生成して返す
        return Response::forge(View::forge('shokujikanriapp/index', $data));
    }
} 