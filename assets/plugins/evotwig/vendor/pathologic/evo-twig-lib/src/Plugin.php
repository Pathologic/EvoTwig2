<?php

namespace Pathologic\EvoTwig;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;
use Helpers\FS;
use Symfony\Component\Cache\Adapter\DoctrineAdapter;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\DebugExtension;
use Twig\Extra\Cache\CacheRuntime;
use Twig\Loader\FilesystemLoader as TwigLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class Plugin
{
    protected $modx;
    protected $params;
    protected $addonsPath = 'assets/plugins/evotwig/addons/';
    protected $configPath = 'assets/plugins/evotwig/config/';

    public function __construct(\DocumentParser $modx, $params = [])
    {
        $this->modx = $modx;
        $this->params = $this->prepareParams($params);
    }

    protected function prepareParams(array $params = [])
    {
        $params['templatesPath'] = $params['templatesPath'] ?? 'assets/templates/tpl/';
        $params['templatesExtension'] = $params['templatesExtension'] ?? 'tpl';
        $params['templatesCachePath'] = $params['templatesCachePath'] ?? 'assets/cache/templates/';
        $params['controllersNamespace'] = $params['controllersNamespace'] ?? __NAMESPACE__;
        $params['dataCachePath'] = $params['dataCachePath'] ?? 'assets/cache/data/';
        $params['allowedFunctions'] = $params['allowedFunctions'] ?? '';
        $params['allowedFilters'] = $params['allowedFilters'] ?? '';
        $params['debug'] = $params['debug'] === 'true';
        $params['disableTwig'] = $params['disableTwig'] === 'true';
        $params['disablePostProcess'] = $params['disablePostProcess'] === 'true';
        $params['cacheDocumentObject'] = $params['cacheDocumentObject'] === 'true';
        $configPath = $this->configPath . 'config.php';
        if (FS::getInstance()->checkFile($configPath)) {
            $modx = $this->modx;
            $config = require(MODX_BASE_PATH . $configPath);
            if (is_array($config)) {
                $params = array_merge($params, $config);
            }
        };

        return $params;
    }

    public function OnManagerPageInit()
    {
        $this->init();
    }

    public function OnWebPageInit()
    {
        $this->init();
    }

    public function OnPageNotFound()
    {
        $this->init();
    }

    protected function init()
    {
        $this->initDLTemplate();
        $this->initCache();
        $this->initTwig();
    }

    protected function initCache()
    {
        $cacheConfigPath = $this->configPath . 'cache.php';
        if (FS::getInstance()->checkFile($cacheConfigPath)) {
            $driver = require(MODX_BASE_PATH . $cacheConfigPath);
        } else {
            $cachePath = MODX_BASE_PATH . $this->params['dataCachePath'];
            if (!is_dir($cachePath)) {
                mkdir($cachePath, 0755, true);
            }
            $driver = new FilesystemCache($cachePath);
        }
        if ($driver instanceof CacheProvider) {
            $driver->setNamespace($this->modx->getConfig('site_name'));
            $this->modx->cache = $driver;
        } else {
            throw new \Exception('Unable to load cache driver');
        }
    }

    protected function initTwig()
    {
        $templatesPath = MODX_BASE_PATH . $this->params['templatesPath'];
        if (!is_dir($templatesPath)) {
            mkdir($templatesPath, 0755, true);
        }
        $loader = new TwigLoader($templatesPath);
        $cachePath = MODX_BASE_PATH . $this->params['templatesCachePath'];
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
        $twig = new TwigEnvironment($loader, [
            'cache' => $cachePath,
            'debug' => $this->params['debug']
        ]);
        $twig->addRuntimeLoader(new class($this->modx) implements RuntimeLoaderInterface {
            protected $modx;
            public function __construct(\DocumentParser $modx)
            {
                $this->modx = $modx;
            }
            public function load($class) {
                if (CacheRuntime::class === $class) {
                    return new CacheRuntime(new DoctrineAdapter($this->modx->cache));
                }
            }
        });
        if ($this->params['debug']) {
            $twig->addExtension(new DebugExtension());
        }
        $this->modx->twig = $twig;
        $this->loadAddons('extensions');
        $this->loadAddons('functions');
        $this->loadAddons('filters');
        $this->modx->tpl->loadTwig();
        $this->modx->tpl->setTemplatePath($this->params['templatesPath']);
        $this->modx->tpl->setTemplateExtension($this->params['templatesExtension']);
    }

    public function initDLTemplate() {
        $this->modx->tpl = \DLTemplate::getInstance($this->modx);
    }

    public function OnLoadWebDocument()
    {
        if ($this->params['disableTwig'] === true) return;
        $template = $this->modx->documentObject['template'] ? $this->modx->documentContent : $this->modx->documentObject['content'];
        $template = $template ?: $this->modx->documentObject['content'];
        if (strpos($template, '@FILE:') === 0) {
            $this->modx->minParserPasses = -1;
            $this->modx->maxParserPasses = -1;
            $template = substr($template, 6);
            $template = explode('@', $template, 2);
            if (!empty($template[1])) {
                $controller = $this->params['controllersNamespace'] . '\\' . $template[1];
            }
            $template = $template[0];
            if (empty($controller) || !class_exists($controller) || !is_a($controller, 'Pathologic\EvoTwig\ControllerInterface', true)) {
                $controller = 'Pathologic\EvoTwig\BaseController';
            }
            $controller = new $controller($this->modx, $this->params);
            $controller->setTemplate($template);
            $out = $controller->render();
            if (!empty($out)) {
                $this->modx->cacheKey = '';
                $this->modx->documentContent = $out;
                if ($this->params['disablePostProcess']) {
                    $this->hackPostProcess();
                }
            }
        }
    }

    protected function loadAddons($type)
    {
        $modx = $this->modx;
	    $twig = $this->modx->twig;
	    $params = $this->params;
	    $path = $this->addonsPath . $type . '/';
	    $method = $class = '';
	    switch ($type) {
            case 'extensions':
                $method = 'addExtension';
                $class = 'Twig\Extension\AbstractExtension';
                break;
            case 'functions':
                $method = 'addFunction';
                $class = 'Twig\TwigFunction';
                break;
            case 'filters':
                $method = 'addFilter';
                $class = 'Twig\TwigFilter';
                break;
        }
        if (empty($method)) return;
        $fs = FS::getInstance();
        if (!$fs->checkDir($path)) $fs->makeDir($path);
	    foreach (scandir(MODX_BASE_PATH . $path) as $item) {
	        if ($item == '.' || $item == '..') continue;
	        if(substr($item,-4) === '.php') {
	            $file = $path . $item;
	            if (!$fs->checkFile($file)) continue;
	            $addon = require(MODX_BASE_PATH . $file);
	            if (is_a($addon, $class, true)) {
	                $twig->$method($addon);
                }
            }
        }
    }

    public function OnCacheUpdate()
    {
        $this->dropCache();
    }

    public function OnSiteRefresh()
    {
        $this->dropCache();
        FS::getInstance()->rmDir($this->params['templatesCachePath']);
    }

    protected function dropCache()
    {
        $this->modx->cache->flushAll();
    }

    protected function hackPostProcess()
    {
        $this->modx->documentGenerated = 1;
        if ($this->modx->config['error_page'] == $this->modx->documentIdentifier && $this->modx->config['error_page'] != $this->modx->config['site_start']) {
            header('HTTP/1.0 404 Not Found');
        }
        $this->modx->documentOutput = $this->modx->documentContent;

        if ($this->modx->documentGenerated == 1 && $this->modx->documentObject['cacheable'] == 1 && $this->modx->documentObject['type'] == 'document' && $this->modx->documentObject['published'] == 1) {
            if (!empty($this->modx->sjscripts)) {
                $this->modx->documentObject['__MODxSJScripts__'] = $this->modx->sjscripts;
            }
            if (!empty($this->modx->jscripts)) {
                $this->modx->documentObject['__MODxJScripts__'] = $this->modx->jscripts;
            }
        }

        // Moved from prepareResponse() by sirlancelot
        // Insert Startup jscripts & CSS scripts into template - template must have a <head> tag
        if ($js = $this->modx->getRegisteredClientStartupScripts()) {
            // change to just before closing </head>
            // $this->documentContent = preg_replace("/(<head[^>]*>)/i", "\\1\n".$js, $this->documentContent);
            $this->modx->documentOutput = preg_replace("/(<\/head>)/i", $js . "\n\\1", $this->modx->documentOutput);
        }

        // Insert jscripts & html block into template - template must have a </body> tag
        if ($js = $this->modx->getRegisteredClientScripts()) {
            $this->modx->documentOutput = preg_replace("/(<\/body>)/i", $js . "\n\\1", $this->modx->documentOutput);
        }
        // End fix by sirlancelot

        // send out content-type and content-disposition headers
        if (IN_PARSER_MODE == "true") {
            $type = !empty ($this->modx->contentTypes[$this->modx->documentIdentifier]) ? $this->modx->contentTypes[$this->modx->documentIdentifier] : "text/html";
            header('Content-Type: ' . $type . '; charset=' . $this->modx->config['modx_charset']);
            //            if (($this->documentIdentifier == $this->config['error_page']) || $redirect_error)
            //                header('HTTP/1.0 404 Not Found');
            if (!$this->modx->checkPreview() && $this->modx->documentObject['content_dispo'] == 1) {
                if ($this->modx->documentObject['alias']) {
                    $name = $this->modx->documentObject['alias'];
                } else {
                    // strip title of special characters
                    $name = $this->modx->documentObject['pagetitle'];
                    $name = strip_tags($name);
                    $name = $this->modx->cleanUpMODXTags($name);
                    $name = strtolower($name);
                    $name = preg_replace('/&.+?;/', '', $name); // kill entities
                    $name = preg_replace('/[^\.%a-z0-9 _-]/', '', $name);
                    $name = preg_replace('/\s+/', '-', $name);
                    $name = preg_replace('|-+|', '-', $name);
                    $name = trim($name, '-');
                }
                $header = 'Content-Disposition: attachment; filename=' . $name;
                header($header);
            }
        }
        $this->modx->setConditional();

        // invoke OnWebPagePrerender event
            $evtOut = $this->modx->invokeEvent('OnWebPagePrerender', array('documentOutput' => $this->modx->documentOutput));
            if (is_array($evtOut) && count($evtOut) > 0) {
                $this->modx->documentOutput = $evtOut['0'];
            }

        $this->modx->documentOutput = $this->modx->removeSanitizeSeed($this->modx->documentOutput);

        echo $this->modx->documentOutput;
        ob_end_flush();
        $this->modx->invokeEvent('OnWebPageComplete');
        die();
    }

    public function OnBeforeLoadDocumentObject()
    {
        if (!$this->params['cacheDocumentObject']) return;
        $identifier = $this->params['identifier'];
        $key = 'documentObject' . $identifier;
        if (!$documentObject = $this->modx->cache->fetch($key)) {
            $documentObject = $this->getDocumentObject($identifier);
            $this->modx->cache->save($key, $documentObject);
        }

        $this->modx->event->setOutput($documentObject);
    }

    protected function getDocumentObject($id)
    {
        $modx = $this->modx;
        if (is_array($modx->documentObject) && $id === $modx->documentObject['id']) {
            $documentObject = $modx->documentObject;
        } else {
            $documentObject = $modx->db->query("SELECT * FROM ".$modx->getFullTableName('site_content')." WHERE id = ".(int)$id);
            $documentObject = $modx->db->getRow($documentObject);
        }
        if($documentObject === null) $documentObject = array();
        else {
            $rs = $modx->db->select("tv.*, IF(tvc.value!='',tvc.value,tv.default_text) as value", $modx->getFullTableName("site_tmplvars") . " tv
                    INNER JOIN " . $modx->getFullTableName("site_tmplvar_templates") . " tvtpl ON tvtpl.tmplvarid = tv.id
                    LEFT JOIN " . $modx->getFullTableName("site_tmplvar_contentvalues") . " tvc ON tvc.tmplvarid=tv.id AND tvc.contentid = '{$documentObject['id']}'", "tvtpl.templateid = '{$documentObject['template']}'");
            $tmplvars = array();
            while ($row = $modx->db->getRow($rs)) {
                $tmplvars[$row['name']] = array(
                    $row['name'],
                    $row['value'],
                    $row['display'],
                    $row['display_params'],
                    $row['type']
                );
            }
            $documentObject = array_merge($documentObject, $tmplvars);
        }

        return $documentObject;
    }
}
