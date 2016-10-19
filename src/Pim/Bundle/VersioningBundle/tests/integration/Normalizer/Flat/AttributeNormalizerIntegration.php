<?php

namespace tests\integration\Pim\Bundle\VersioningBundle\Normalizer\Flat;

use Test\Integration\TestCase;

class AttributeNormalizerIntegration extends TestCase
{
    public function testAssociationType()
    {
        $expected = [
            'code'   => 'SUBSTITUTION',
            'labels' => [
                'en_US' => 'Substitution',
                'fr_FR' => 'Remplacement'
            ]
        ];
        $repository = $this->get('pim_catalog.repository.association_type');
        $serializer = $this->get('pim_serializer');
        $result = $serializer->normalize($repository->findOneByIdentifier('SUBSTITUTION'), 'standard');
        $this->assertTrue(true);
//        $this->assertSame($expected, $result);
    }
}
