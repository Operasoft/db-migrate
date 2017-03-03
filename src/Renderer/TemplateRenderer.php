<?php
namespace DbMigrate\Renderer;

use DbMigrate\Config\Template\TemplateConfig;
use DbMigrate\Model\DbTable;
use DbMigrate\Twig\TwigExtension;
use Twig_Environment;

/**
 * Class TemplateRenderer
 * @package DbMigrate\Renderer
 * @author Christian Labonté <clabonte@baselinetelematics.com>
 * @copyright Baseline Telematics
 */
abstract class TemplateRenderer
{
    /** @var \Twig_Loader_Filesystem */
    protected $loader;
    /** @var Twig_Environment */
    protected $twig;
    /** @var TwigExtension */
    protected $twigExtension;
    /** @var string */
    protected $folder;

    /**
     * TemplateGenerator constructor.
     * @param \Twig_Loader_Filesystem $loader
     */
    protected function __construct(\Twig_Loader_Filesystem $loader, $folder)
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

    /**
     * @return string
     */
    protected function getFilename($filename)
    {

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