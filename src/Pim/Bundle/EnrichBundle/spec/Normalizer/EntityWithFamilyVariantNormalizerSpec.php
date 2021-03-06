<?php

namespace spec\Pim\Bundle\EnrichBundle\Normalizer;

use Doctrine\Common\Collections\Collection;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\EnrichBundle\Normalizer\ImageNormalizer;
use Akeneo\Pim\Enrichment\Component\Product\Completeness\CompletenessCalculatorInterface;
use Akeneo\Pim\Enrichment\Component\Product\EntityWithFamilyVariant\EntityWithFamilyVariantAttributesProvider;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeOptionInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeOptionValueInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\CompletenessInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\ProductModel\ImageAsLabel;
use Akeneo\Pim\Enrichment\Component\Product\ProductModel\Query\CompleteVariantProducts;
use Akeneo\Pim\Enrichment\Component\Product\ProductModel\Query\VariantProductRatioInterface;
use Akeneo\Channel\Component\Repository\LocaleRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityWithFamilyVariantNormalizerSpec extends ObjectBehavior
{
    function let(
        ImageNormalizer $imageNormalizer,
        LocaleRepositoryInterface $localeRepository,
        EntityWithFamilyVariantAttributesProvider $attributesProvider,
        NormalizerInterface $completenessCollectionNormalizer,
        CompletenessCalculatorInterface $completenessCalculator,
        VariantProductRatioInterface $variantProductRatioQuery,
        ImageAsLabel $imageAsLabel
    ) {
        $this->beConstructedWith(
            $imageNormalizer,
            $localeRepository,
            $attributesProvider,
            $completenessCollectionNormalizer,
            $completenessCalculator,
            $variantProductRatioQuery,
            $imageAsLabel
        );
    }

    function it_throws_an_exception_if_the_entity_is_not_a_variant_product_nor_a_product_model(
        \stdClass $entity
    ) {
        $this->shouldThrow(\InvalidArgumentException::class)->during(
            'normalize', [$entity, 'internal_api']
        );
    }

    function it_normalizes_a_variant_product(
        $localeRepository,
        $attributesProvider,
        $completenessCollectionNormalizer,
        $completenessCalculator,
        ProductInterface $variantProduct,
        AttributeInterface $colorAttribute,
        AttributeInterface $sizeAttribute,
        ValueInterface $colorValue,
        ValueInterface $sizeValue,
        AttributeOptionInterface $colorAttributeOption,
        AttributeOptionValueInterface $colorAttributeOptionValue,
        CompletenessInterface $completeness1,
        CompletenessInterface $completeness2,
        Collection $productCompletenesses
    ) {
        $context = [
            'locale' => 'en_US'
        ];
        $localeRepository->getActivatedLocaleCodes()->willReturn(['fr_FR', 'en_US']);

        $variantProduct->isVariant()->willReturn(true);
        $variantProduct->getLabel('fr_FR')->willReturn('Tshirt Blanc S');
        $variantProduct->getLabel('en_US')->willReturn('Tshirt White S');
        $variantProduct->getId()->willReturn(42);

        $variantProduct->getIdentifier()->willReturn('tshirt_white_s');

        $attributesProvider->getAxes($variantProduct)->willReturn([
            $colorAttribute,
            $sizeAttribute
        ]);

        $colorAttribute->getCode()->willReturn('color');
        $colorAttribute->getType()->willReturn('pim_catalog_simpleselect');
        $sizeAttribute->getCode()->willReturn('size');
        $sizeAttribute->getType()->willReturn('pim_catalog_text');
        $variantProduct->getValue('color')->willReturn($colorValue);
        $variantProduct->getValue('size')->willReturn($sizeValue);

        $colorValue->getData()->willReturn($colorAttributeOption);
        $colorAttributeOption->setLocale('fr_FR')->shouldBeCalled();
        $colorAttributeOption->setLocale('en_US')->shouldBeCalled();
        $colorAttributeOption->getSortOrder()->willReturn(2);
        $colorAttributeOption->getCode()->willReturn('white');
        $colorAttributeOption->getTranslation()->willReturn($colorAttributeOptionValue);
        $colorAttributeOptionValue->getLabel()->willReturn('Blanc', 'White');
        $sizeValue->__toString()->willReturn('S');

        $variantProduct->getImage()->willReturn(null);

        $variantProduct->getCompletenesses()->willReturn($productCompletenesses);

        $productCompletenesses->isEmpty()->willReturn(true);
        $completenessCalculator->calculate($variantProduct)->willReturn($completeness1, $completeness2);

        $completenessCollectionNormalizer->normalize($productCompletenesses, 'internal_api')
            ->willReturn(['NORMALIZED_COMPLETENESS']);

        $this->normalize($variantProduct, 'internal_api', $context)->shouldReturn([
            'id'                 => 42,
            'identifier'         => 'tshirt_white_s',
            'axes_values_labels' => [
                'fr_FR' => 'Blanc, S',
                'en_US' => 'White, S',
            ],
            'labels'             => [
                'fr_FR' => 'Tshirt Blanc S',
                'en_US' => 'Tshirt White S',
            ],
            'order'              => [2, 'white', 'S'],
            'image'              => null,
            'model_type'         => 'product',
            'completeness'       => ['NORMALIZED_COMPLETENESS']
        ]);
    }

    function it_normalizes_a_product_model(
        $localeRepository,
        $attributesProvider,
        $variantProductRatioQuery,
        ProductModelInterface $productModel,
        AttributeInterface $colorAttribute,
        ValueInterface $colorValue,
        AttributeOptionInterface $colorAttributeOption,
        AttributeOptionValueInterface $colorAttributeOptionValue,
        CompleteVariantProducts $completeVariantProducts
    ) {
        $context = [
            'locale' => 'en_US'
        ];
        $localeRepository->getActivatedLocaleCodes()->willReturn(['fr_FR', 'en_US']);

        $productModel->getLabel('fr_FR')->willReturn('Tshirt Blanc');
        $productModel->getLabel('en_US')->willReturn('Tshirt White');
        $productModel->getId()->willReturn(5);

        $productModel->getCode()->willReturn('tshirt_white');

        $attributesProvider->getAxes($productModel)->willReturn([$colorAttribute]);

        $colorAttribute->getCode()->willReturn('color');
        $colorAttribute->getType()->willReturn('pim_catalog_simpleselect');
        $productModel->getValue('color')->willReturn($colorValue);

        $colorValue->getData()->willReturn($colorAttributeOption);
        $colorAttributeOption->setLocale('fr_FR')->shouldBeCalled();
        $colorAttributeOption->setLocale('en_US')->shouldBeCalled();
        $colorAttributeOption->getSortOrder()->willReturn(2);
        $colorAttributeOption->getCode()->willReturn('white');
        $colorAttributeOption->getTranslation()->willReturn($colorAttributeOptionValue);
        $colorAttributeOptionValue->getLabel()->willReturn('Blanc', 'White');

        $variantProductRatioQuery->findComplete($productModel)->willReturn($completeVariantProducts);
        $completeVariantProducts->values()->willReturn(['NORMALIZED COMPLETENESS']);

        $this->normalize($productModel, 'internal_api', $context)->shouldReturn([
            'id'                 => 5,
            'identifier'         => 'tshirt_white',
            'axes_values_labels' => [
                'fr_FR' => 'Blanc',
                'en_US' => 'White',
            ],
            'labels'             => [
                'fr_FR' => 'Tshirt Blanc',
                'en_US' => 'Tshirt White',
            ],
            'order'              => [2, 'white'],
            'image'              => null,
            'model_type'         => 'product_model',
            'completeness'       => ['NORMALIZED COMPLETENESS']
        ]);
    }
}
