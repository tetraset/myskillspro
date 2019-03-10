<?php

namespace MyskillsBundle\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntityNotFoundException extends NotFoundHttpException
{
    public function __construct($class, $value, $field='id')
    {
        parent::__construct(
            sprintf(
                'Entity "%s" with %s: "%s" is not found',
                $class,
                $field,
                $value
            )
        );
    }
}
