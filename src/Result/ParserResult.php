<?php
/**
 * This file is part of the CsvParser package
 *
 * @author Team Symfony @ Kaliop <team-symfony@kaliop.com>
 */

namespace Kaliop\CsvParser\Result;


use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class ParserResult
 *
 * This class is the default parser result container. It can be extended to allow the developer to
 * fully customize its parsing results and add special routines within the finalize() method.
 *
 * @package Kaliop\CsvParser\Result
 */
class ParserResult implements ParserResultInterface
{
    /**
     * @var object
     */
    protected $entity;

    /**
     * @var integer
     */
    protected $csvLineNo;

    /**
     * Validation violations
     *
     * @var ConstraintViolationListInterface
     */
    protected $violations;

    /**
     * Shortcut to tell if this result has validation violations or not
     *
     * @return boolean
     */
    public function isValid()
    {
        if (isset($this->violations) && count($this->violations) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Tells which line from the CSV this result is from
     *
     * @param integer $lineNo Csv line number
     * @return void
     */
    public function setCsvLineNo($lineNo)
    {
        $this->csvLineNo = $lineNo;
    }

    /**
     * Returns the CSV line number corresponding to this result
     *
     * @return integer
     */
    public function getCsvLineNo()
    {
        return $this->csvLineNo;
    }

    /**
     * Returns constraints violations (if any)
     *
     * @return ConstraintViolationListInterface|null
     */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * Sets validation results/violations
     *
     * @param ConstraintViolationListInterface $violations
     *
     * @return void
     */
    public function setViolations(ConstraintViolationListInterface $violations)
    {
        $this->violations = $violations;
    }

    /**
     * Utility method called when parsing/validation is done.
     *
     * @return void
     */
    public function finalize()
    {
        // override this method to enhance parsing result
    }

    /**
     * Returns the defined Entity for this result
     *
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Defines the Entity for this result
     *
     * @param object $entity The entity instance
     *
     * @return void
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
}