<?php
namespace CSVParser\Result;

use Symfony\Component\Validator\ConstraintViolationListInterface;

interface ParserResultInterface
{
    /**
     * Returns the defined Entity for this result
     *
     * @return object
     */
    public function getEntity();

    /**
     * Defines the Entity for this result
     *
     * @param object $entity The entity instance
     *
     * @return void
     */
    public function setEntity($entity);

    /**
     * Shortcut to tell if this result has validation violations or not
     *
     * @return boolean
     */
    public function isValid();

    /**
     * Tells which line from the CSV this result is from
     *
     * @param integer $lineNo Csv line number
     * @return void
     */
    public function setCsvLineNo($lineNo);

    /**
     * Returns the CSV line number corresponding to this result
     *
     * @return integer
     */
    public function getCsvLineNo();

    /**
     * Returns constraints violations (if any)
     *
     * @return ConstraintViolationListInterface
     */
    public function getViolations();

    /**
     * Sets validation results/violations
     *
     * @param ConstraintViolationListInterface $violations
     *
     * @return void
     */
    public function setViolations(ConstraintViolationListInterface $violations);

    /**
     * Utility method called when parsing/validation is done.
     *
     * @return void
     */
    public function finalize();
}