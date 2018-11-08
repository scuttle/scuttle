<?php

namespace App;

class Parser {

    public $regex = "";

    public function parse($string)
    {
        return "";
    }

    public function getArgs($string)
    {
        $args = array();

        $exploded = explode("=", trim($string));
        foreach ($exploded as $key=>$value) {

            $needle = strrpos($value, '"');
            $keyname = trim(substr($value, $needle-1));
            $args[$keyname] = stripslashes(substr($value,0,$needle));
        }
        return $args;
    }

    public function __toString()
    {
        return $this->regex;
    }

    public function __invoke($string)
    {
        return $this->parse($string);
    }
    
}