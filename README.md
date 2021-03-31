## EvoTwig 2

The second version of EvoTwig - a plugin to replace default Evo parser with Twig.

The advantages are:
* fast and powerful template engine;
* simple cache engine with a lot of drivers, supported by DocLister and MODxAPI;
* no need to sanitize MODX tags in user input;
* storing templates in files - use your favourite editor with syntax highlighting and GIT;
* redesigning of the whole site becomes very simple.

Differences from EvoTwig 1.x:
* Twig 3;
* can be installed without composer;
* ability to add custom Twig extensions;
* evo parser post procession can be switched off completely;
* plugin settings can be overriden with config file;
* support of so called controllers to add custom data to templates (similar to Evo3 controllers);
* template name should be set without extension (like DocLister @FILE chunk);
* "cache" tag syntax differs from earlier versions of Twig.

### Installation
#### Without composer
Update DocLister. Then install EvoTwig2 via Extras module or manually. All needed libraries are bundled, but you cannot have their latest versions. So, it's better to use composer. 

#### With composer
Update DocLister. Then run "composer require pathologic/evo-twig-lib" in the "assets" folder, then install EvoTwig2 plugin with Extras module or manually.
Create autoload entries in assets/composer.json if needed, then run "composer update" in the "assets" folder.

**Do not run composer commands or edit composer.json in site root folder as all changes of composer.json will be lost after Evo update.** 

### Settings
* templatesPath - path to the templates folder, default is "assets/templates/tpl/";
* templatesExtension - extension of the template file, default is "tpl";
* templatesCachePath - path to the folder containing templates cache;
* dataCachePath - path to the folder containing data cache if Filesystem Cache driver is used;
* baseController - base controller class;
* allowedFunctions - allowed php functions to use as functions in Twig templates, comma separated;
* allowedFilters - allowed php functions to use as filters in Twig templates, comma separated;
* debug - enables debug mode;
* disableTwig - disables Twig for templates;
* disablePostProcess - disables Evo parser post procession, set "false" to use evo style links ([~2~]); 
* cacheDocumentObject - set "true" to cache document object. 

All these settings can be overriden by the settings from "config.php" file. There's an example in "assets/plugins/evotwig/config/" folder. As it's php file, you can change settings under different conditions.

Default cache driver is Filesystem Cache driver, you can change it with "cache.php" file. There's an example in "assets/plugins/evotwig/config/" folder. 

### Usage
Create template files in your templates folder. Then link Evo templates to them using template code field:
```
@FILE:mypage
```

Available template variables are:
* _GET, _POST, $_REQUEST, _SESSION, _COOKIE;
* modx - DocumentParser object;
* documentObject - $modx->documentObject;
* config - $modx->config;
* resource - document fields and tvs, a simple version of the document object;
* plh - $modx->placeholders;
* debug - true if debug mode is enabled;
* ajax - true if page is requested with ajax.

If you need to get the whole "plh" or "_SESSION" array, then use toArray() method:
```
{{ dump(_SESSION.toArray()) }}
```

Some Evo specific functions are added:
```
{{ runSnippet('SnippetName',{
     'param1':'value',
     'param2':'value'
   })
}}

{{ getChunk('chunkName') }}

{{ parseChunk('chunkName', {'foo':'bar','bar':'baz'}) }}

{{ parseChunk('@CODE:[+foo+] is bar, [+bar+] is baz', {'foo':'bar','bar':'baz'}) }}

{{ makeUrl(2) }}
{{ makeUrl(2, {foo: 'bar'}) }}
{{ makeUrl(2, {}, true) }}
{{ makeUrl(2, {bar: 'foo'}, false) }}
```

You can use php functions as Twig functions or filters. Just add their names in plugin parameters.

To use Twig with chunks, add "@T_FILE:" or "@T_CODE:" prefix:
```
&tpl=`@T_FILE:chunks/product`
&tpl=`@T_CODE: {{ data.pagetitle }}`
```

Template variables in chunks depend on snippets code. For example, DocLister chunks contain "data", "modx", "DocLister" variables, FormLister - "data", "modx", "FormLister", "errors", "plh". See docs to get variables list.

### Adding Twig extensions
All extensions are placed in "assets/plugins/evotwig/addons/" folder according to their type. See Twig documentation and existing extensions code.

### Controllers
You may use controller classes to add custom template variables. All controller classes should implement \Pathologic\EvoTwig\ControllerInterface.

### Using cache
Cache provide is available via $modx->cache property:
```
$modx->cache->save('cache_key', $data); //cache data with key
$modx->cache->save('cache_key', $data, 600); //cache for 600 seconds
$modx->cache->contains('cache_key'); //check for cached data with key
$modx->cache->fetch('cache_key'); // get cached data by key
```

You can cache snippets with getCache snippet for a number of seconds or infinitely. Snippet parameters:
* snippetName — the name of snippet to run, required;
* key — cache key, required;
* lifetime — cache lifetime if you cache for time, not required;
* keyGenerator — snippet or function to generate cache key, not required.

```
[!getCache?
&snippetName=`mySnippet`
&key=`foobar`
&lifetime=`3000`
&mySnippetParameter=`...`
&mySnippetParameter=`...`
&mySnippetParameter=`...`
!]
```