/**
 * EvoTwig
 * 
 * Twig template engine for Evolution CMS 1.x
 *
 * @category    plugin
 * @version     2.0.0
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Pathologic
 * @internal    @properties &debug=Debug;list;true,false;true &disableTwig=Disable Twig for templates;list;true,false;false &templatesPath=Path to template files;text;assets/templates/tpl/ &templatesExtension=Templates extension;text;tpl &templatesCachePath=Path to store compiled templates;text;assets/cache/templates/ &dataCachePath=Path to store cached data for filesystem cache;text;assets/cache/data/ &allowedFunctions=Allowed functions to use as Twig functions;textarea;count,filesize,get_key,intval &allowedFilters=Allowed functions to use as Twig filters ;textarea; &controllersNamespace=Controllers namespace;text; &disablePostProcess=Disable evo parser post procession;list;true,false;true &cacheDocumentObject=Cache document object;list;true,false;false 
 * @internal    @events OnWebPageInit,OnManagerPageInit,OnPageNotFound,OnLoadWebDocument,OnCacheUpdate,OnSiteRefresh,OnBeforeLoadDocumentObject
 * @internal    @installset base
 * @internal    @disabled 1
 */

return require MODX_BASE_PATH . 'assets/plugins/evotwig/plugin.evotwig.php';
