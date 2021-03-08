<?php
$functions = array_map('trim', explode(',', $params['allowedFunctions'] ?: ''));
$filters = array_map('trim', explode(',', $params['allowedFilters'] ?: ''));

return new \Pathologic\EvoTwig\PhpFunctionsExtension($functions, $filters);
