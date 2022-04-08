<?php
return new \Twig\TwigFilter('price',
    function ($price, $convert = 1) use ($modx) {
        return $modx->runSnippet('PriceFormat', ['price' => $price, 'convert' => $convert]);
    }
);
