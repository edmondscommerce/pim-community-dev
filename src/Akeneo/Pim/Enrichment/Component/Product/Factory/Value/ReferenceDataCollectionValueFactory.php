<?php

namespace Akeneo\Pim\Enrichment\Component\Product\Factory\Value;

use Akeneo\Pim\Enrichment\Component\Product\Model\ReferenceDataInterface;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ReferenceDataRepositoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ReferenceDataRepositoryResolverInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Tool\Component\StorageUtils\Exception\InvalidPropertyException;
use Akeneo\Tool\Component\StorageUtils\Exception\InvalidPropertyTypeException;

/**
 * Factory that creates simple-select and multi-select product values.
 *
 * @internal  Please, do not use this class directly. You must use \Akeneo\Pim\Enrichment\Component\Product\Factory\ProductValueFactory.
 *
 * @author    Damien Carcel (damien.carcel@akeneo.com)
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class ReferenceDataCollectionValueFactory implements ValueFactoryInterface
{
    /** @var ReferenceDataRepositoryResolverInterface */
    protected $repositoryResolver;

    /** @var string */
    protected $productValueClass;

    /** @var string */
    protected $supportedAttributeType;

    /**
     * @param ReferenceDataRepositoryResolverInterface $repositoryResolver
     * @param string                                   $productValueClass
     * @param string                                   $supportedAttributeType
     */
    public function __construct(
        ReferenceDataRepositoryResolverInterface $repositoryResolver,
        $productValueClass,
        $supportedAttributeType
    ) {
        $this->repositoryResolver = $repositoryResolver;
        $this->productValueClass = $productValueClass;
        $this->supportedAttributeType = $supportedAttributeType;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeInterface $attribute, $channelCode, $localeCode, $data, $ignoreUnknownData = false)
    {
        $this->checkData($attribute, $data);

        if (null === $data) {
            $data = [];
        }

        sort($data);

        $value = new $this->productValueClass(
            $attribute,
            $channelCode,
            $localeCode,
            $this->getReferenceDataCollection($attribute, $data, $ignoreUnknownData)
        );

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($attributeType)
    {
        return $attributeType === $this->supportedAttributeType;
    }

    /**
     * Checks if data is valid.
     *
     * @param AttributeInterface $attribute
     * @param mixed              $data
     *
     * @throws InvalidPropertyTypeException
     */
    protected function checkData(AttributeInterface $attribute, $data)
    {
        if (null === $data || [] === $data) {
            return;
        }

        if (!is_array($data)) {
            throw InvalidPropertyTypeException::arrayExpected(
                $attribute->getCode(),
                static::class,
                $data
            );
        }

        foreach ($data as $key => $value) {
            if (!is_string($value)) {
                throw InvalidPropertyTypeException::validArrayStructureExpected(
                    $attribute->getCode(),
                    sprintf('array key "%s" expects a string as value, "%s" given', $key, gettype($value)),
                    static::class,
                    $data
                );
            }
        }
    }

    /**
     * Gets a collection of reference data from an array of codes.
     *
     * @param AttributeInterface $attribute
     * @param array              $referenceDataCodes
     *
     * @return array
     */
    protected function getReferenceDataCollection(AttributeInterface $attribute, array $referenceDataCodes, bool $ignoreUnknownData)
    {
        $collection = [];

        $repository = $this->repositoryResolver->resolve($attribute->getReferenceDataName());

        foreach ($referenceDataCodes as $referenceDataCode) {
            $referenceData = $this->getReferenceData($attribute, $repository, $referenceDataCode, $ignoreUnknownData);
            if (null !== $referenceData && !in_array($referenceData, $collection, true)) {
                $collection[] = $referenceData;
            }
        }

        return $collection;
    }

    /**
     * Finds a reference data by code.
     *
     * @todo TIP-684: When deleting one element of the collection, we will end up throwing the exception.
     *       Problem is, when loading a product value from single storage, it will be skipped because of
     *       one reference data, when the others in the collection could be valid. So the value will not
     *       be loaded at all, when what we want is the value to be loaded minus the wrong reference data.
     *
     * @param AttributeInterface               $attribute
     * @param ReferenceDataRepositoryInterface $repository
     * @param string                           $referenceDataCode
     * @param bool                             $ignoreUnknownData
     *
     * @throws InvalidPropertyException
     * @return ReferenceDataInterface
     */
    protected function getReferenceData(
        AttributeInterface $attribute,
        ReferenceDataRepositoryInterface $repository,
        $referenceDataCode,
        bool $ignoreUnknownData
    ) {
        $referenceData = $repository->findOneBy(['code' => $referenceDataCode]);

        if (null === $referenceData && false === $ignoreUnknownData) {
            throw InvalidPropertyException::validEntityCodeExpected(
                $attribute->getCode(),
                'reference data code',
                sprintf('The code of the reference data "%s" does not exist', $attribute->getReferenceDataName()),
                static::class,
                $referenceDataCode
            );
        }

        return $referenceData;
    }
}
