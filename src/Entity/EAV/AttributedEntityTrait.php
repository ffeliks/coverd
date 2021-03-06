<?php

namespace App\Entity\EAV;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

trait AttributedEntityTrait
{
    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(
     *     targetEntity="App\Entity\EAV\Attribute",
     *     fetch="EAGER",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    private $attributes;

    public function __construct()
    {
        $this->attributes = new ArrayCollection();
    }

    /**
     * @param Attribute $attribute
     *
     * @return $this
     */
    public function addAttribute(Attribute $attribute, bool $overwrite = true)
    {
        foreach ($this->attributes as $item) {
            if ($item->getDefinition()->getId() === $attribute->getDefinition()->getId()) {
                if ($overwrite) {
                    $this->attributes->removeElement($item);
                } else {
                    return $this;
                }
            }
        }

        $this->attributes->add($attribute);

        return $this;
    }

    public function addAttributes(iterable $attributes, bool $overwrite = true)
    {
        foreach ($attributes as $attribute) {
            $this->addAttribute($attribute, $overwrite);
        }
    }

    public function setAttribute($attributeId, $value)
    {
        /** @var Attribute|null $attribute */
        $attribute = $this->attributes->filter(function (Attribute $item) use ($attributeId) {
            return $item->getDefinition()->getId() == $attributeId;
        })->first();

        if ($attribute) {
            $attribute->setValue($value);
        } else {
            // TODO
        }
    }

    /**
     * @param Attribute $attribute
     */
    public function removeAttribute(Attribute $attribute)
    {
        $this->attributes->removeElement($attribute);
    }

    /**
     * @return ArrayCollection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function processAttributeChanges($changes)
    {
        if (isset($changes['attributes'])) {
            foreach ($changes['attributes'] as $attributeChange) {
                if ($attributeChange['id'] > 0) {
                    $attribute = $this->getAttributeById($attributeChange['id']);
                    $attribute->setValue($attributeChange['value']);
                } else {
                    $definition = $this->getDefinitionById($attributeChange['definition_id']);
                    $attribute = $definition->createAttribute();
                    $attribute->setValue($attributeChange['value']);
                    $this->addAttribute($attribute);
                }
                if ($attribute->isEmpty()) {
                    $this->removeAttribute($attribute);
                }
            }
        }

        unset($changes['attributes']);
    }

    private function getAttributeById(int $id): ?Attribute
    {
        foreach ($this->getAttributes() as $attribute) {
            if ($attribute->getId() === $id) {
                return $attribute;
            }
        }
    }

    private function getDefinitionById(int $id): ?Definition
    {
        /** @var Definition[] $definitions */
        $definitions = $this->getAttributes()->map(function (Attribute $attribute) {
            return $attribute->getDefinition();
        });

        foreach ($definitions as $definition) {
            if ($definition->getId() === $id) {
                return $definition;
            }
        }
    }
}
