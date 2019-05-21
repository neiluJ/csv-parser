<?php
namespace Kaliop\CsvParser\Parser;

use Kaliop\CsvParser\Result\ParserResult;
use Symfony\Component\Validator\Validator\ValidatorInterface;

interface ParserInterface
{
    const CSV_SEPARATOR = ";";

    /**
     * Returns parsing results
     *
     * @return ParserResult[]
     */
    public function getResults();

    /**
     * Defines a Validator
     *
     * @param ValidatorInterface $validator
     *
     * @return void
     */
    public function setValidator(ValidatorInterface $validator);

    /**
     * Returns a mapping definition array telling where to put data into entities.
     * Array should be formatted like this:
     *
     *  array(
     *      <columnNumberInCSV> => array(
     *          'property'  => 'path.to.property',
     *          'filter'    => function($value) { return filter_var($value); },
     *          'default'   => 'default value'
     *      )
     *  )
     *
     * If the property path is null, the column will be ignored.
     * If the filter function is null, the value will not be filtered.
     * The default value is null.
     *
     * @return array
     */
    public function getMappingDefinition();

    /**
     * Returns the Entity class name that should be filled from CSV data
     *
     * @return string
     */
    public function getEntityClassName();

    /**
     * Returns validation groups. If no groups are defined, validation is disabled.
     *
     * @return array
     */
    public function getValidationGroups();

    /**
     * @return int
     */
    public function getTotalValidResults();

    /**
     * @return int
     */
    public function getTotalLines();

    /**
     * @return int
     */
    public function getTotalErroneousResults();

    /**
     * @return int
     */
    public function getTotalErrors();

    /**
     * @return int
     */
    public function getTotalResults();

    /**
     * Defines CSV separator (fields)
     *
     * @param string $separator
     *
     * @return void
     */
    public function setSeparator($separator);

    /**
     * Tells if we should skip first line in CSV (commonly used as column titles)
     *
     * @param boolean $skipFirstLine
     *
     * @return void
     */
    public function setSkipFirstLine($skipFirstLine);
}