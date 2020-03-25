<?php

namespace base\exceptions;

class InvalidParamException extends \BadMethodCallException
{
    public function getName()
    {
        return 'Invalid Parameter';
    }
}
