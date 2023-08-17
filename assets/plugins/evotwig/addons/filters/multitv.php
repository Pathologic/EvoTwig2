<?php
return new \Twig\TwigFilter('multiTV',
    function ($value) {
        $value = json_decode($value, true) ?? [];
        if(isset($value['fieldValue'])) $value = $value['fieldValue'];
        
        return $value;
    }
);
