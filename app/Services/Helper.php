<?php

namespace App\Services;

class Helper
{
    public static function snakeToCamel($input) {
        if (is_string($input)) {
            return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
        }
        foreach ($input as $item_key => $item) {
            foreach ($item as $key => $value) {
                $item = json_decode(json_encode($item), true);
                $new_key = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
                $item[$new_key] = $value;
                if ($key != $new_key) {
                    unset($item[$key]);
                }
                $item = json_decode(json_encode($item));
            }
            $input[$item_key] = $item;

        }

        return $input;
    }

    public static function getStringBetween($string, $start, $end){
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
}
