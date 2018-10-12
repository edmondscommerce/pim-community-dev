<?php

namespace spec\Akeneo\Pim\Enrichment\Component\Product\Validator\ConstraintGuesser;

use Akeneo\Pim\Enrichment\Component\Product\Validator\ConstraintGuesserInterface;
use Akeneo\Pim\Enrichment\Component\Product\Validator\Constraints\IsString;
use PhpSpec\ObjectBehavior;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;

class StringGuesserSpec extends ObjectBehavior
{
    function it_is_an_attribute_constraint_guesser()
    {
        $this->shouldImplement(ConstraintGuesserInterface::class);
    }

    function it_enforces_attribute_type(AttributeInterface $attribute)
    {
        $attribute->getType()
            ->willReturn('pim_catalog_text');
        $this->supportAttribute($attribute)
            ->shouldReturn(true);

        $attribute->getType()
            ->willReturn('pim_catalog_textarea');
        $this->supportAttribute($attribute)
            ->shouldReturn(true);

        $attribute->getType()
            ->willReturn('pim_catalog_identifier');
        $this->supportAttribute($attribute)
            ->shouldReturn(true);

        $attribute->getType()
            ->willReturn('pim_catalog_number');
        $this->supportAttribute($attribute)
            ->shouldReturn(false);

        $attribute->getType()
            ->willReturn('foo');
        $this->supportAttribute($attribute)
            ->shouldReturn(false);
    }

    function it_always_guess(AttributeInterface $attribute)
    {
        $constraints = $this->guessConstraints($attribute);

        $constraints->shouldHaveCount(1);

        $constraint = $constraints[0];
        $constraint->shouldBeAnInstanceOf(IsString::class);
    }
}
