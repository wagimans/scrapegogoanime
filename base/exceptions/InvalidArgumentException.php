<?php

namespace base\exceptions;

class InvalidArgumentException extends InvalidParamException
{
    public function getName()
    {
        return 'Invalid Argument';
    }
}
