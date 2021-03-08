<?php


namespace Pathologic\EvoTwig;


interface ControllerInterface
{
    public function setTemplateData(array $data = [], $replace = false);

    public function getTemplateData(): array;

    public function setTemplate($template);

    public function getTemplate(): string;

    public function render(): string;
}
