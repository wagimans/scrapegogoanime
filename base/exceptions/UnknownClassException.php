<?php

namespace base\exceptions;

class UnknownClassException extends \Exception
{
    public function getName()
    {
        return 'Unknown Class';
    }
}
