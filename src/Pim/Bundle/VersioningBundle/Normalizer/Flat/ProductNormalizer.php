<?php

namespace Pim\Bundle\VersioningBundle\Normalizer\Flat;

use Pim\Component\Catalog\Model\ProductInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * A normalizer to transform a product entity into a flat array
 *
 * @author    Gildas Quemener <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductNormalizer implements NormalizerInterface
{
    /** @staticvar string */
    const ITEM_SEPARATOR = ',';

    /** @var string[] */
    protected $supportedFormats = ['flat'];

    /** @var NormalizerInterface */
    protected $standardNormalizer;

    /** @var NormalizerInterface */
    protected $productValueNormalizer;

    /**
     * @param NormalizerInterface $standardNormalizer
     * @param NormalizerInterface $productValueNormalizer
     */
    public function __construct(
        NormalizerInterface $standardNormalizer,
        NormalizerInterface $productValueNormalizer
    ) {
        $this->standardNormalizer = $standardNormalizer;
        $this->productValueNormalizer = $productValueNormalizer;
    }

    /**
     * {@inheritdoc}
     *
     * @param ProductInterface $product
     *
     * @return array
     */
    public function normalize($product, $format = null, array $context = [])
    {
        $standardProduct = $this->standardNormalizer->normalize($product, 'standard', $context);
        $flatProduct = $standardProduct;

        unset($flatProduct['identifier']);

        $flatProduct['groups'] = implode(self::ITEM_SEPARATOR, $standardProduct['groups']);
        $flatProduct['categories'] = implode(self::ITEM_SEPARATOR, $standardProduct['categories']);

        unset($flatProduct['associations']);
        $flatProduct += $this->normalizeAssociations($standardProduct['associations']);

        unset($flatProduct['values']);
        $flatProduct += $this->normalizeValues($standardProduct['values'], 'flat', $context);

        unset($flatProduct['created']);
        unset($flatProduct['updated']);

        return $flatProduct;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof ProductInterface && in_array($format, $this->supportedFormats);
    }

    /**
     * Normalize associations
     *
     * @param array $associations
     *
     * @return array
     */
    protected function normalizeAssociations($associations = [])
    {
        $flatAssociations = [];

        foreach ($associations as $associationType => $association) {
            $flatAssociations[$associationType . '-groups'] = implode(',', $association['groups']);
            $flatAssociations[$associationType . '-products'] = implode(',', $association['products']);
        }

        return $flatAssociations;
    }

    /**
     * Normalize values from the prodct to serialize into flat format
     *
     * @param array  $productValues
     * @param string $format
     * @param array  $context
     *
     * @return array
     */
    protected function normalizeValues($productValues, $format = null, array $context = [])
    {
        $normalizedValues = [];
        foreach ($productValues as $attribute => $productValue) {
            $normalizedValues += (array) $this->productValueNormalizer->normalize(
                [$attribute => $productValue],
                $format,
                $context
            );
        }
        ksort($normalizedValues);

        return $normalizedValues;
    }
}
