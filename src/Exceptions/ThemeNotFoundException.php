<?php namespace Genetsis\Themes\Exceptions;

use Symfony\Component\Translation\Exception\NotFoundResourceException;

class ThemeNotFoundException extends NotFoundResourceException
{
    public function __construct($themeName)
    {
        parent::__construct("Theme [ $themeName ] not found! Maybe you're missing a ".config('theme.config.name').' file.');
    }
}