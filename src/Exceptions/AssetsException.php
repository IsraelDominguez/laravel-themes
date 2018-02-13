<?php namespace Genetsis\Themes\Exceptions;

class AssetsException extends \Exception
{
    public function __construct($exception)
    {
        parent::__construct(" $exception ");
    }
}