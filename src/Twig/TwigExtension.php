<?php
namespace DbMigrate\Twig;

use DbMigrate\Model\DbField;
use DbMigrate\Model\DbTable;
use Doctrine\Common\Inflector\Inflector;
use Twig_Function;

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
            new \Twig_SimpleFilter('constant', [$this, 'constantFilter']),
            new \Twig_SimpleFilter('field', [$this, 'variableFilter']),
            new \Twig_SimpleFilter('variable', [$this, 'variableFilter']),
            new \Twig_SimpleFilter('getter', [$this, 'getterFilter']),
            new \Twig_SimpleFilter('setter', [$this, 'setterFilter']),
            new \Twig_SimpleFilter('type', [$this, 'typeFilter'])
        );
    }

    public function getFunctions()
    {
        return array(
            new Twig_Function('editableFields', [$this, 'generateEditableFields'], array('is_safe' => array('html'))),
            new Twig_Function('pdoVariables', [$this, 'generatePdoVariables'], array('is_safe' => array('html'))),
            new Twig_Function('pdoUpdate', [$this, 'generatePdoUpdate'], array('is_safe' => array('html')))
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
        return Inflector::pluralize($this->getName($str));
    }

    /**
     * Filters a string to tranform it to its singular equivalent. Converts 'Tables' to 'Table'.
     *
     * @param  mixed $str
     *
     * @return string
     */
    public function singularFilter($str)
    {
        return Inflector::singularize($this->getName($str));
    }

    /**
     * Filters a string to tranform it to its camel case equivalent. Converts 'table_name' to 'table name'.
     *
     * @param  mixed $str
     *
     * @return string
     */
    public function wordsFilter($str)
    {
        return str_replace('_', ' ', $this->getName($str));
    }

    /**
     * Filters a string to tranform it to its camel case equivalent. Converts 'table_name' to 'tableName'.
     *
     * @param  mixed $str
     *
     * @return string
     */
    public function camelFilter($str)
    {
        return Inflector::camelize($this->getName($str));
    }

    /**
     * Filters a string to tranform it to its class name. Converts 'table_names' to 'TableName'.
     *
     * @param  mixed $str
     *
     * @return string
     */
    public function classFilter($str)
    {
        return Inflector::singularize(Inflector::classify($this->getName($str)));
    }

    /**
     * Filters a string to tranform it to its constant name. Converts 'constant value' to 'CONSTANT_VALUE'.
     *
     * @param  mixed $str
     *
     * @return string
     */
    public function constantFilter($str)
    {
        return str_replace(' ', '_', strtoupper($this->getName($str)));
    }

    /**
     * Filters a string to tranform it to its variable name. Converts 'table_names' to '$tableName'.
     *
     * @param  mixed $str
     *
     * @return string
     */
    public function variableFilter($str)
    {
        return '$'.Inflector::singularize(Inflector::camelize($this->getName($str)));
    }

    /**
     * Filters a string to tranform it to its field name. Converts 'table_names' to '$this->tableName'.
     *
     * @param  mixed $str
     *
     * @return string
     */
    public function fieldFilter($str)
    {
        return '$this->'.Inflector::singularize(Inflector::camelize($this->getName($str)));
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
     * @param mixed $str
     */
    public function getterFilter($str)
    {
        $prefix = 'get';

        return $prefix.$this->classFilter($this->singularFilter($this->getName($str))).'()';
    }

    /**
     * @param mixed $str
     */
    public function setterFilter($str)
    {
        $prefix = 'set';

        return $prefix.$this->classFilter($this->singularFilter($this->getName($str)));
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

    public function generateEditableFields(DbTable $table, $includePrimaryKey = true)
    {
        $fields = array();

        foreach ($table->getFields() as $field) {
            if ($includePrimaryKey && $field->isPrimaryKey() && $field->isUuid()) {
                $fields[] = '`'.$field->getName().'`';
            } else if (!$field->isGenerated()) {
                $fields[] = '`'.$field->getName().'`';
            }
        }

        return implode(', ', $fields);
    }

    public function generatePdoVariables(DbTable $table, $includePrimaryKey = true)
    {
        $values = array();

        foreach ($table->getFields() as $field) {
            if ($includePrimaryKey && $field->isPrimaryKey() && $field->isUuid()) {
                $values[] = ':'.$field->getName();
            } else if (!$field->isGenerated()) {
                $values[] = ':'.$field->getName();
            }
        }

        return implode(', ', $values);
    }

    public function generatePdoUpdate(DbTable $table, $includePrimaryKey = false)
    {
        $values = array();

        foreach ($table->getFields() as $field) {
            if ($includePrimaryKey && $field->isPrimaryKey()) {
                $values[] = '`'.$field->getName().'` = :'.$field->getName();
            } else if (!$field->isGenerated() && !$field->isPrimaryKey()) {
                $values[] = '`'.$field->getName().'` = :'.$field->getName();
            }
        }

        return implode(', ', $values);
    }

    private function getName($value) {
        $name = $value;
        if ($value instanceof DbField) {
            $name = $value->getName();
        } elseif ($value instanceof DbTable) {
            $name = $value->getName();
        }

        return $name;
    }
}