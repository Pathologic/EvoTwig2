<?php


namespace Pathologic\EvoTwig;


class BaseController implements ControllerInterface
{
    protected $modx;
    protected $data = [];
    protected $params = [];
    protected $template = '';

    /**
     * BaseController constructor.
     * @param  \DocumentParser  $modx
     * @param  array  $params
     */
    public function __construct(\DocumentParser $modx, array $params = [])
    {
        $this->modx = $modx;
        $this->params = $params;
    }

    /**
     * @param  array  $data
     */
    public function setTemplateData(array $data = [], $replace = false)
    {
        $this->data = $replace ? $data : array_merge($this->data, $data);
    }

    /**
     * @return array
     */
    public function getTemplateData(): array
    {
        return $this->data;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        $template = $this->template;
        if (empty($template)) {
            $dir = MODX_BASE_PATH . $this->params['templatesPath'];
            $tplExt = $this->params['templatesExtension'];
            $documentObject = $this->modx->documentObject;
            switch (true) {
                case file_exists($dir . 'tpl-' . $documentObject['template'] . '_doc-' . $documentObject['id'] . '.' . $tplExt):
                {
                    $template = 'tpl-' . $documentObject['template'] . '_doc-' . $documentObject['id'];
                    break;
                }
                case file_exists($dir . 'doc-' . $documentObject['id'] . '.' . $tplExt):
                {
                    $template = 'doc-' . $documentObject['id'] . '.' . $tplExt;
                    break;
                }
                case file_exists($dir . 'tpl-' . $documentObject['template'] . '.' . $tplExt):
                {
                    $template = 'tpl-' . $documentObject['template'];
                    break;
                }
            }
        }

        return $template;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $template = $this->getTemplate();
        $out = '';
        if (!empty($template)) {
            $tpl = $this->modx->twig->load($this->getTemplate() . '.' . $this->params['templatesExtension']);
            $out = $this->modx->twig->render($tpl, $this->getTemplateData());
        }

        return $out;
    }
}
