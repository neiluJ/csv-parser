<?php
/**
 * This file is part of the CsvParser package
 *
 * @author Team Symfony @ Kaliop <team-symfony@kaliop.com>
 */

namespace Kaliop\CsvParser\Parser;


use Kaliop\CsvParser\Result\ParserResultInterface;
use Kaliop\CsvParser\Result\ParserResult;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AbstractParser
 *
 * This is the class one should extend when building a CSV Parser.
 *
 * @package Kaliop\CsvParser\Parser
 */
abstract class AbstractParser
{
    /**
     * Path to CSV file
     *
     * @var string
     */
    protected $filePath;

    /**
     * CSV fields separator
     *
     * @var string
     */
    protected $separator;

    /**
     * CSV fields enclosure
     *
     * @var string|null
     */
    protected $enclosure = null;

    /**
     * Results from the parsing
     *
     * @var array
     */
    protected $results = array();

    /**
     * Skip the first line of CSV data
     *
     * @var bool
     */
    protected $skipFirstLine = true;

    /**
     * Skip the last column of CSV data
     *
     * @var bool
     */
    protected $skipLastColumn = false;

    /**
     * Check the minimum column count of the CSV data
     * @var int
     */
    protected $minColumnCount = 0;

    /**
     * The Validator
     *
     * @var null|ValidatorInterface
     */
    protected $validator = null;

    /**
     * ParserResult class name
     *
     * @var string
     */
    protected $resultClassName = null;

    /**
     * RAW CSV Contents
     *
     * @var string
     */
    protected $csvContents;

    /**
     * @var int
     */
    private $totalLines = 0;
    /**
     * @var int
     */
    private $totalResults = 0;
    /**
     * @var int
     */
    private $totalValidResults = 0;
    /**
     * @var int
     */
    private $totalErroneousResults = 0;
    /**
     * @var int
     */
    private $totalErrors = 0;

    /**
     * Tells if the parser has been executed or not
     *
     * @var bool
     */
    private $executed = false;

    /**
     * SAPImportManager constructor.
     *
     * @param string $filePath Path to CSV file
     * @param string $separator String separator for CSV fields
     * @param string|null $enclosure
     * @param boolean $skipFirstLine Should we skip first line of data (frequently used as columns headers)
     */
    public function __construct($filePath = null, $separator = ParserInterface::CSV_SEPARATOR, $enclosure = null, $skipFirstLine = true)
    {
        $this->filePath         = $filePath;
        $this->separator        = $separator;
        $this->enclosure        = $enclosure;
        $this->skipFirstLine    = (bool)$skipFirstLine;
    }

    /**
     * Defines CSV separator (fields)
     *
     * @param string $separator
     * @return $this
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * Tells if we should skip first line in CSV (commonly used as column titles)
     *
     * @param boolean $skipFirstLine
     * @return $this
     */
    public function setSkipFirstLine($skipFirstLine)
    {
        $this->skipFirstLine = $skipFirstLine;

        return $this;
    }

    /**
     * Tells if we should skip last column in CSV
     *
     * @param boolean $skipLastColumn
     * @return $this
     */
    public function setSkipLastColumn($skipLastColumn)
    {
        $this->skipLastColumn = $skipLastColumn;

        return $this;
    }

    /**
     * if > 0, check the minimum column count of the CSV data
     *
     * @param int $minColumnCount
     * @return $this
     */
    public function setMinColumnCount($minColumnCount)
    {
        $this->minColumnCount = $minColumnCount;

        return $this;
    }

    /**
     * Defines a Validator
     *
     * @param ValidatorInterface|null $validator
     * @return $this
     */
    public function setValidator(ValidatorInterface $validator = null)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * @return string
     */
    public function getResultClassName()
    {
        if (empty($this->resultClassName)) {
            $this->resultClassName = get_class(new ParserResult());
        }

        return $this->resultClassName;
    }

    /**
     * @param string $resultClassName
     *
     * @return $this
     */
    public function setResultClassName($resultClassName)
    {
        $this->resultClassName = $resultClassName;

        return $this;
    }

    /**
     * Execute the parsing
     *
     * @param null $filePath
     * @return array
     *
     * @throws \Exception
     */
    public function execute($filePath = null)
    {
        if ($this->executed) {
            return $this->results;
        }

        if (!empty($filePath)) {
            $this->filePath = $filePath;
        }

        $handle = fopen($this->filePath, 'r+');
        $this->totalLines = 0;

        if (false !== $handle) {
            // rewind handle in case of previous actions
            rewind($handle);

            // prevent contextError
            $func = function($handle, $separator, $enclosure) {
                if (empty($enclosure)) {
                    return fgetcsv($handle, null, $separator);
                }
                return fgetcsv($handle, null, $separator, $enclosure);
            };

            while(($data = $func($handle, $this->separator, $this->enclosure)) !== false) {

                if ($this->skipLastColumn) {
                    unset($data[count($data) - 1]);
                }

                if ($this->minColumnCount > 0 && count($data) < $this->minColumnCount) {
                    throw new \RuntimeException(sprintf('Invalid minimum column count in "%s", %d expected, %d given', $this->filePath, $this->minColumnCount, count($data)));
                }
                if ($this->skipFirstLine === false || $this->totalLines > 0) {
                    $this->handleLineData($data, $this->totalLines + ($this->skipFirstLine === true ? 1 : 0));
                }
                $this->totalLines++;
            }

            rewind($handle);
            $this->csvContents = stream_get_contents($handle);
            fclose($handle);
        } else {
            throw new \RuntimeException(sprintf('Failed to open/read file: %s', $this->filePath));
        }

        $this->executed = true;

        return $this->results;
    }

    /**
     * Converts raw data into an Entity
     *
     * @param array $data Raw CSV-line data
     * @param int $lineNo
     *
     * @throws \Exception
     */
    protected function handleLineData(array $data, $lineNo)
    {
        $className  = $this->getEntityClassName();
        $accessor   = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        $map        = $this->getMappingDefinition();

        $resultClassName    = $this->getResultClassName();
        $final              = new $resultClassName();

        if (!$final instanceof ParserResultInterface) {
            throw new \RuntimeException(sprintf('ParserResult class "%s" MUST implement ParserResultInterface', get_class($final)));
        }

        $final->setEntity(new $className());
        $final->setCsvLineNo($lineNo);

        foreach ($map as $column => $infos) {
            $property = (isset($infos['property']) ? $infos['property'] : null);
            $filter = (isset($infos['filter']) ? $infos['filter'] : null);
            $default = (isset($infos['default']) ? $infos['default'] : null);

            // null property == skipped
            if (null === $property) {
                continue;
            }

            try {
                $columnData  = (isset($data[$column]) ? $data[$column] : $default);
                if ($filter !== null) {
                    if (!is_callable($filter)) {
                        throw new \LogicException(sprintf('The filter defined for property "%s" is not callable', $property));
                    }
                    $columnData = call_user_func_array($filter, array($columnData));
                }
                $accessor->setValue($final, $property, $columnData);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        $final->finalize();

        $validationGroups = $this->getValidationGroups();
        if (is_array($validationGroups) && count($validationGroups)) {
            if (null === $this->validator) {
                throw new \LogicException('Validation is enabled but no Validator configured. Please use setValidator().');
            }

            $validationContext = $this->validator->startContext();
            $validationContext->validate($final->getEntity(), null, $validationGroups);
            $final->setViolations($validationContext->getViolations());
            if (count($final->getViolations())) {
                $this->totalErrors += count($final->getViolations());
                $this->totalErroneousResults++;
            } else {
                $this->totalValidResults++;
            }
        }

        $this->results[] = $final;
    }

    /**
     * Returns a mapping definition array telling where to put data into entities.
     * Array should be formatted like this:
     *
     *  array(
     *      <columnNumberInCSV> => array(
     *          'property'  => 'path.to.property',
     *          'filter'    => function($value) { return filter_var($value); },
     *          'default'   => 'some default value if needed'
     *      )
     *  )
     *
     * If the property path is null, the column will be ignored.
     * If the filter function is null, the value will not be filtered.
     *
     * @return array
     */
    abstract public function getMappingDefinition();

    /**
     * Returns the Entity class name that should be filled from CSV data
     *
     * @return string
     */
    abstract public function getEntityClassName();

    /**
     * Returns validation groups if required. If no validation groups are defined, the validation is disabled.
     *
     * @return array
     */
    public function getValidationGroups()
    {
        return array();
    }

    /**
     * @return int
     */
    public function getTotalLines()
    {
        return $this->totalLines;
    }

    /**
     * @return int
     */
    public function getTotalValidResults()
    {
        return $this->totalValidResults;
    }

    /**
     * @return int
     */
    public function getTotalErroneousResults()
    {
        return $this->totalErroneousResults;
    }

    /**
     * @return int
     */
    public function getTotalErrors()
    {
        return $this->totalErrors;
    }

    /**
     * @return int
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * Returns parsing results
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getResults()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->results;
    }

    /**
     * @return string
     */
    public function getCsvContents()
    {
        return $this->csvContents;
    }
}