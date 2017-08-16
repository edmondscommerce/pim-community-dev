<?php

namespace Pim\Bundle\CatalogBundle\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Pim\Component\Catalog\Factory\ValueCollectionFactory;
use Pim\Component\Catalog\Model\EntityWithFamilyVariantInterface;
use Pim\Component\Catalog\Model\EntityWithValuesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load real product values object from the $rawValues field (ie: values in JSON)
 * when a product is loaded by Doctrine.
 *
 * @author    Julien Janvier <j.janvier@gmail.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * TODO: we could use an Entity Listener instead (need to upgrade bundle to 1.3)
 * TODO: cf. http://symfony.com/doc/current/bundles/DoctrineBundle/entity-listeners.html
 * TODO: cf. http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#entity-listeners
 */
class LoadEntityWithValuesSubscriber implements EventSubscriber
{
    /** @var ContainerInterface */
    protected $container;

    /** @var ValueCollectionFactory */
    protected $valueCollectionFactory;

    /**
     * TODO: The container is injected here to avoid a circular reference
     * TODO: I didn't find any other way to do it :(
     * TODO: Open to every proposal :)
     *
     * TODO: Basically we have this each time we try to inject something related to the Doctrine entity manager
     * TODO: in a Symfony subscriber.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postLoad
        ];
    }

    /**
     * Here we load the real object values from the raw values field.
     * We also add the identifier as a regular value so that it can be used in the product
     * edit form transparently.
     *
     * @param LifecycleEventArgs $event
     */
    public function postLoad(LifecycleEventArgs $event)
    {
        $entity = $event->getObject();
        if (!$entity instanceof EntityWithValuesInterface) {
            return;
        }

        $rawValues = $entity->getRawValues();
        if ($entity instanceof EntityWithFamilyVariantInterface) {
            $ancestryRawValues = $this->getAncestryRawValues($entity);
            foreach ($ancestryRawValues as $attributeCode => $value) {
                $rawValues[$attributeCode] = $value;
            }
        }

        $values = $this->getProductValueCollectionFactory()->createFromStorageFormat($rawValues);
        $entity->setValues($values);
    }

    /**
     * @return ValueCollectionFactory
     */
    private function getProductValueCollectionFactory(): ValueCollectionFactory
    {
        if (null === $this->valueCollectionFactory) {
            $this->valueCollectionFactory = $this->container->get('pim_catalog.factory.value_collection');
        }

        return $this->valueCollectionFactory;
    }

    /**
     * Recursively get the raw values of all the parents of an entity.
     *
     * @param EntityWithFamilyVariantInterface $entity
     * @param array                            $ancestryRawValues
     *
     * @return array
     */
    private function getAncestryRawValues(EntityWithFamilyVariantInterface $entity, array $ancestryRawValues = []): array
    {
        $parent = $entity->getParent();

        if (null === $parent) {
            return $ancestryRawValues;
        }

        return $this->getAncestryRawValues($parent, array_merge($ancestryRawValues, $parent->getRawValues()));
    }
}