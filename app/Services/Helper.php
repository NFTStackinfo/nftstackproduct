<?php

namespace App\Services;

class Helper
{
    public static function snakeToCamel($input) {
        if (is_string($input)) {
            return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
        }
        foreach ($input as $key => $value) {
            $new_key = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
            $input[$new_key] = $value;
            unset($input[$key]);
        }
        return $input;
    }
}
