<?php

namespace Pim\Bundle\TransformBundle\Filter;

use Doctrine\Common\Collections\Collection;

/**
 * Filter for ProductValue objects during flat export.
 *
 * @author    Julien Janvier <julien.janvier@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FlatProductValueFilter implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(Collection $objects, array $context = [])
    {
        if (!isset($context['identifier'])) {
            throw new \Exception('"identifier" is required in the context.');
        }

        $identifier  = $context['identifier'];
        $scopeCode   = isset($context['scopeCode']) ? $context['scopeCode'] : null;
        $localeCodes = isset($context['localeCodes']) ? $context['localeCodes'] : [];

        return $objects->filter(
            function ($value) use ($identifier, $scopeCode, $localeCodes) {
                return (
                    ($value !== $identifier) &&
                    (
                        ($scopeCode == null) ||
                        (!$value->getAttribute()->isScopable()) ||
                        ($value->getAttribute()->isScopable() && $value->getScope() == $scopeCode)
                    ) &&
                    (
                        (count($localeCodes) == 0) ||
                        (!$value->getAttribute()->isLocalizable()) ||
                        ($value->getAttribute()->isLocalizable() && in_array($value->getLocale(), $localeCodes))

                    )
                );
            }
        );
    }
} 