<?php
namespace DbMigrate\Renderer;

use DbMigrate\Config\Template\TemplateConfig;
use DbMigrate\Model\DbTable;

/**
 * Class PhpUnitTemplateRenderer
 * @package DbMigrate\Renderer
 * @author Christian Labonté <clabonte@baselinetelematics.com>
 * @copyright Baseline Telematics
 */
class PhpUnitTemplateRenderer extends TemplateRenderer
{
    /**
     * PhpUnitTemplateRenderer constructor.
     * @param \Twig_Loader_Filesystem $loader
     * @param string $folder
     */
    public function __construct(\Twig_Loader_Filesystem $loader, $folder)
    {
        parent::__construct($loader, $folder);
    }

    public function renderDoctrineRepositoryTest(TemplateConfig $config, DbTable $table)
    {
        $template = $this->twig->load('DoctrineRepositoryTest.php.twig');

        $context = array(
            'table' => $table,
            'namespace' => $config->getNamespace());

        $content = $template->render($context);

        $filename = $this->folder.'/Repository/Doctrine/Doctrine'.$this->twigExtension->classFilter($table->name).'RepositoryTest.php';

        $this->saveToFile($content, $filename);
    }

}