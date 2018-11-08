<?php

use App\Parser;

class Bold extends Parser {

    public $regex = "/\*\*([^\s\n](?:.*[^\s\n])?)\*\*/U";

    public function parse($string)
    {
        return preg_replace_callback(
            $this->regex,
            function($match) {
                return "<b>".$match[1]."</b>";
            },
            $string
        );
    }

}