<?php
namespace DbMigrate\Renderer;

use DbMigrate\Config\Template\TemplateConfig;
use DbMigrate\Model\DbTable;

/**
 * Class PhpTemplateRenderer
 * @package DbMigrate\Renderer
 * @author Christian Labonté <clabonte@baselinetelematics.com>
 * @copyright Baseline Telematics
 */
class PhpTemplateRenderer extends TemplateRenderer
{
    /**
     * PhpTemplateRenderer constructor.
     * @param \Twig_Loader_Filesystem $loader
     * @param string $folder
     */
    public function __construct(\Twig_Loader_Filesystem $loader, $folder)
    {
        parent::__construct($loader, $folder);
    }

    public function renderModel(TemplateConfig $config, DbTable $table)
    {
        $template = $this->twig->load('Model.php.twig');

        $context = array(
            'table' => $table,
            'namespace' => $config->getNamespace());

        $content = $template->render($context);

        $filename = $this->folder.'/Model/'.$this->twigExtension->classFilter($table->name).'.php';

        $this->saveToFile($content, $filename);
    }

    public function renderModelConstants(TemplateConfig $config, DbTable $table)
    {
        $template = $this->twig->load('ModelConstants.php.twig');

        $primaryKey = $table->getPrimaryField();

        $context = array(
            'table' => $table,
            'primaryKey' => $primaryKey,
            'namespace' => $config->getNamespace());

        $content = $template->render($context);

        $filename = $this->folder.'/Model/'.$this->twigExtension->classFilter($table->name).'.php';

        $this->saveToFile($content, $filename);
    }

    public function renderRepositoryInterface(TemplateConfig $config, DbTable $table)
    {
        $template = $this->twig->load('RepositoryInterface.php.twig');

        $context = array(
            'table' => $table,
            'namespace' => $config->getNamespace());

        $content = $template->render($context);

        $filename = $this->folder.'/Repository/'.$this->twigExtension->classFilter($table->name).'RepositoryInterface.php';

        $this->saveToFile($content, $filename);
    }

    public function renderDoctrineRepository(TemplateConfig $config, DbTable $table)
    {
        $template = $this->twig->load('DoctrineRepository.php.twig');

        $context = array(
            'table' => $table,
            'namespace' => $config->getNamespace());

        $content = $template->render($context);

        $filename = $this->folder.'/Repository/Doctrine/Doctrine'.$this->twigExtension->classFilter($table->name).'Repository.php';

        $this->saveToFile($content, $filename);
    }
}