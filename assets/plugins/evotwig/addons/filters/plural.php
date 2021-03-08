<?php
/**
 * {{ ['Остался %d час', 'Осталось %d часа', 'Осталось %d часов']|plural(11) }}
 * {{ count }} стат{{ ['ья','ьи','ей']|plural(count) }}
 */
return new \Twig\TwigFilter('plural',
    function ($endings, $number) {
        $cases = [2, 0, 1, 1, 1, 2];
        $n = $number;

        return sprintf($endings[($n % 100 > 4 && $n % 100 < 20) ? 2 : $cases[min($n % 10, 5)]], $n);
    }
);
