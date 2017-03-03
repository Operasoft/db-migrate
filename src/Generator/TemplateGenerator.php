<?php
namespace DbMigrate\Generator;

use DbMigrate\Config\Template\TemplateConfig;
use DbMigrate\Model\DbTable;
use DbMigrate\Twig\TwigExtension;
use Twig_Environment;

/**
 * Class TemplateGenerator
 * @package DbMigrate\Generator
 * @author Christian Labonté <clabonte@baselinetelematics.com>
 * @copyright Baseline Telematics
 */
class TemplateGenerator
{
    /** @var \Twig_Loader_Filesystem */
    private $loader;
    /** @var Twig_Environment */
    private $twig;
    /** @var TwigExtension */
    private $twigExtension;
    /** @var string */
    private $folder;

    /**
     * TemplateGenerator constructor.
     * @param \Twig_Loader_Filesystem $loader
     */
    public function __construct(\Twig_Loader_Filesystem $loader, $folder)
    {
        $this->folder = $folder;
        $this->loader = $loader;
        $this->twigExtension = new TwigExtension();
        $this->twig = new Twig_Environment($this->loader, array(
            'cache' => __DIR__.'/../../out/cache',
            'debug' => true
        ));
        $this->twig->addExtension($this->twigExtension);
    }

    public function renderModel(TemplateConfig $config, DbTable $table)
    {
        $template = $this->twig->load('Model.php.twig');

        $context = array(
            'table' => $table,
            'namespace' => $config->getNamespace());

        $content = $template->render($context);

        // Prepare the filename where the content must be dumped
        $filename = $this->folder;
        if (isset($config->getNamespace()['model'])) {
            $filename .= DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $config->getNamespace()['model']);
        }

        $filename .= DIRECTORY_SEPARATOR.$this->twigExtension->classFilter($table->name).'.php';

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

        // Prepare the filename where the content must be dumped
        $filename = $this->folder;
        if (isset($config->getNamespace()['model'])) {
            $filename .= DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $config->getNamespace()['model']);
        }

        $filename .= DIRECTORY_SEPARATOR.$this->twigExtension->classFilter($table->name).'.php';

        $this->saveToFile($content, $filename);
    }

    public function renderRepositoryInterface(TemplateConfig $config, DbTable $table)
    {
        $template = $this->twig->load('RepositoryInterface.php.twig');

        $context = array(
            'table' => $table,
            'namespace' => $config->getNamespace());

        $content = $template->render($context);

        // Prepare the filename where the content must be dumped
        $filename = $this->folder;
        if (isset($config->getNamespace()['repository'])) {
            $filename .= DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $config->getNamespace()['repository']);
        }

        $filename .= DIRECTORY_SEPARATOR.$this->twigExtension->classFilter($table->name).'RepositoryInterface.php';

        $this->saveToFile($content, $filename);
    }

    public function renderDoctrineRepository(TemplateConfig $config, DbTable $table)
    {
        $template = $this->twig->load('DoctrineRepository.php.twig');

        $context = array(
            'table' => $table,
            'namespace' => $config->getNamespace());

        $content = $template->render($context);

        // Prepare the filename where the content must be dumped
        $filename = $this->folder;
        if (isset($config->getNamespace()['repository_doctrine'])) {
            $filename .= DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $config->getNamespace()['repository_doctrine']);
        }

        $filename .= DIRECTORY_SEPARATOR.'Doctrine'.$this->twigExtension->classFilter($table->name).'Repository.php';

        $this->saveToFile($content, $filename);
    }

    /**
     * @param string $content
     * @param string $filename
     */
    protected function saveToFile($content, $filename)
    {
        // Make sure the folder requested exist. If not, create it
        $dir = dirname($filename);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($filename, $content);
    }
}