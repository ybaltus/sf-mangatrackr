<?php

namespace App\Tests\Unit\Traits;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait MyUnitTestTrait
{
    /**
     * Check the violations with the Validator Component.
     *
     * @return array<ConstraintViolationListInterface|string> [0: violations, 1: message]
     */
    public function assertViolationsWithValidator(ValidatorInterface $validator, object $entity): array
    {
        $violations = $validator->validate($entity);

        $errorMessages = [];
        /*
         * @var ConstraintViolation $error
         */
        foreach ($violations as $violation) {
            $errorMessages[] = $violation->getPropertyPath().' - '.$violation->getMessage();
        }

        return [$violations, implode(', ', $errorMessages)];
    }
}
