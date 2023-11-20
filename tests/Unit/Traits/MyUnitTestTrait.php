<?php

namespace App\Tests\Unit\Traits;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait MyUnitTestTrait
{
    /**
     * Check the violations with the Validator Component
     * @param ValidatorInterface $validator
     * @param object $entity
     * @return array [0: violations, 1: message]
     */
    public function assertViolationsWithValidator(ValidatorInterface $validator, object $entity): array
    {
        $violations=$validator->validate($entity);

        $errorMessages = array();
        /**
         * @var ConstraintViolation $error
         */
        foreach ($violations as $violation)
        {
            $errorMessages[] = $violation->getPropertyPath() . " - " . $violation->getMessage();
        }

        return [$violations, implode(', ', $errorMessages)];

    }
}