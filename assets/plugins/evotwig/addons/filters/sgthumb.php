<?php
return new \Twig\TwigFilter('sgthumb',
    function ($image, $options) use ($modx) {
        return $modx->runSnippet('sgThumb', ['input' => $image, 'options' => $options]);
    }
);
