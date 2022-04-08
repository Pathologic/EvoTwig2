<?php
return new \Twig\TwigFilter('phpthumb',
    function ($image, $options) use ($modx) {
        return $modx->runSnippet('phpthumb', ['input' => $image, 'options' => $options]);
    }
);
