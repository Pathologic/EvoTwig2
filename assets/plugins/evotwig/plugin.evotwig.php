<?php
if (!class_exists('Pathologic\EvoTwig\Plugin')) {
    include_once(MODX_BASE_PATH . 'assets/plugins/evotwig/vendor/autoload.php');
    include_once(MODX_BASE_PATH . 'assets/snippets/FormLister/__autoload.php');
}
if (!class_exists('DLTemplate')) {
    include_once (MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
}
if (!class_exists('Helpers\FS')) {
    include_once (MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');
}
$plugin = new Pathologic\EvoTwig\Plugin($modx, $params);
$event = $modx->event->name;
if (method_exists($plugin, $event)) {
    $plugin->$event();
}
