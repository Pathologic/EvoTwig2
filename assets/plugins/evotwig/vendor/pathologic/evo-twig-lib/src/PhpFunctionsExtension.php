<?php


namespace Pathologic\EvoTwig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class PhpFunctionsExtension extends AbstractExtension
{
    protected $functions = [];
    protected $filters = [];

    public function __construct(array $functions = [], array $filters = [])
    {
        $this->functions = $functions;
        $this->filters = $filters;
    }

    public function getFunctions()
    {
        $functions = [];
        foreach ($this->functions as $function) {
            $functions[] = new TwigFunction($function, $function);
        }

        return $functions;
    }

    public function getFilters()
    {
        $filters = [];
        foreach ($this->filters as $filter) {
            $filters[] = new TwigFilter($filter, $filter);
        }

        return $filters;
    }


}