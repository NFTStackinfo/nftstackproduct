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
                unset($item[$key]);
                $item = json_decode(json_encode($item));
            }
            $input[$item_key] = $item;

        }

        return $input;
    }
}
