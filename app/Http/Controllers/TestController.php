<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function show()
    {
        $arr = array(
           'title' => 'Test',
           'permissions' => array(
               'guest' => array(
                   'create_page' => false,
                   'edit_page' => false,
               ),
               'registered' => array(
                   'create_page' => true,
                   'edit_page' => true,
               ),
           ),
            'blocks' => null,
        );
        $json = json_encode($arr);
        $arr2 = json_decode($json, true);
        dump($arr2["permissions"]["guest"]["create_page"]);
    }
}
