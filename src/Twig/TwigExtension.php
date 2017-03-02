<?php
namespace DbMigrate\Twig;

use DbMigrate\Model\DbField;
use Doctrine\Common\Inflector\Inflector;

/**
 * Class TwigExtension
 * @package DbMigrate\Twig
 * @author Christian Labonté <clabonte@baselinetelematics.com>
 * @copyright Baseline Telematics
 */
class TwigExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('plural', [$this, 'pluralFilter']),
            new \Twig_SimpleFilter('singular', [$this, 'singularFilter']),
            new \Twig_SimpleFilter('words', [$this, 'wordsFilter']),
            new \Twig_SimpleFilter('camel', [$this, 'camelFilter']),
            new \Twig_SimpleFilter('path', [$this, 'pathFilter']),
            new \Twig_SimpleFilter('class', [$this, 'classFilter']),
            new \Twig_SimpleFilter('field', [$this, 'variableFilter']),
            new \Twig_SimpleFilter('variable', [$this, 'variableFilter']),
            new \Twig_SimpleFilter('getter', [$this, 'getterFilter']),
            new \Twig_SimpleFilter('setter', [$this, 'setterFilter']),
            new \Twig_SimpleFilter('type', [$this, 'typeFilter'])
        );
    }

    /**
     * Filters a string to tranform it to its plural equivalent. Converts 'Table' to 'Tables'.
     *
     * @param  string $str
     *
     * @return string
     */
    public function pluralFilter($str)
    {
        return Inflector::pluralize($str);
    }

    /**
     * Filters a string to tranform it to its singular equivalent. Converts 'Tables' to 'Table'.
     *
     * @param  string $str
     *
     * @return string
     */
    public function singularFilter($str)
    {
        return Inflector::singularize($str);
    }

    /**
     * Filters a string to tranform it to its camel case equivalent. Converts 'table_name' to 'table name'.
     *
     * @param  string $str
     *
     * @return string
     */
    public function wordsFilter($str)
    {
        return str_replace('_', ' ', $str);
    }

    /**
     * Filters a string to tranform it to its camel case equivalent. Converts 'table_name' to 'tableName'.
     *
     * @param  string $str
     *
     * @return string
     */
    public function camelFilter($str)
    {
        return Inflector::camelize($str);
    }

    /**
     * Filters a string to tranform it to its class name. Converts 'table_names' to 'TableName'.
     *
     * @param  string $str
     *
     * @return string
     */
    public function classFilter($str)
    {
        return Inflector::singularize(Inflector::classify($str));
    }

    /**
     * Filters a string to tranform it to its variable name. Converts 'table_names' to '$tableName'.
     *
     * @param  string $str
     *
     * @return string
     */
    public function variableFilter($field)
    {
        $name = $field;
        if ($field instanceof DbField) {
            $name = $field->getName();
        }

        return '$'.Inflector::singularize(Inflector::camelize($name));
    }

    /**
     * Filters a string to tranform it to its field name. Converts 'table_names' to '$this->tableName'.
     *
     * @param  string $str
     *
     * @return string
     */
    public function fieldFilter($field)
    {
        $name = $field;
        if ($field instanceof DbField) {
            $name = $field->getName();
        }

        return '$this->'.Inflector::singularize(Inflector::camelize($name));
    }

    /**
     * Filters a string to tranform it to a namespace path. Converts 'Com.Folder.A' to 'Com\Folder\A'
     *
     * @param  string $str
     *
     * @return string
     */
    public function pathFilter($str)
    {
        return str_replace('.', '\\', $str);
    }

    /**
     * @param string|DbField $field
     */
    public function getterFilter($field)
    {
        $prefix = 'get';
        $name = $field;
        if ($field instanceof DbField) {
            if ($field->isBoolean()) {
                $prefix = 'is';
            }
            $name = $field->getName();
        }

        return $prefix.$this->classFilter($this->singularFilter($name)).'()';
    }

    /**
     * @param string|DbField $field
     */
    public function setterFilter($field)
    {
        $prefix = 'set';
        $name = $field;
        if ($field instanceof DbField) {
            $name = $field->getName();
        }

        return $prefix.$this->classFilter($this->singularFilter($name));
    }

    /**
     * @param string|DbField $field
     */
    public function typeFilter($field)
    {
        $type = 'string';
        if ($field instanceof DbField) {
            if ($field->isBoolean()) {
                $type = 'boolean';
            } else if ($field->isDateOrTime()) {
                $type = '\DateTime';
            } else if ($field->isInteger()) {
                $type = 'int';
            } else if ($field->isFloat()) {
                $type = 'float';
            }
        }

        return $type;
    }
}