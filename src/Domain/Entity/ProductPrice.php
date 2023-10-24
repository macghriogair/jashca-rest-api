<?php

declare(strict_types=1);

namespace Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Domain\ValueObject\ProductCurrency;
use Domain\ValueObject\ProductVat;

#[ORM\Embeddable]
class ProductPrice
{
    #[ORM\Column(type: Types::STRING, nullable: false, enumType: ProductCurrency::class)]
    private ?ProductCurrency $currency;

    #[ORM\Column(type: Types::INTEGER, nullable: false, enumType: ProductVat::class)]
    private ?ProductVat $vat;

    public function __construct(
        #[ORM\Column(type: Types::INTEGER, nullable: false)]
        private ?int $value = null,
        ?string $currency = null,
        ?int $vat = null,
    ) {
        if (null === $currency) {
            $this->currency = ProductCurrency::EUR;
        } else {
            $this->currency = ProductCurrency::from($currency);
        }

        if (null === $vat) {
            $this->vat = ProductVat::PERCENT_19;
        } else {
            $this->vat = ProductVat::from($vat);
        }
    }

    public function getCurrency(): ?ProductCurrency
    {
        return $this->currency;
    }

    public function setCurrency(?ProductCurrency $currency): void
    {
        $this->currency = $currency;
    }

    public function getVat(): ?ProductVat
    {
        return $this->vat;
    }

    public function setVat(?ProductVat $vat): void
    {
        $this->vat = $vat;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(?int $value): void
    {
        $this->value = $value;
    }
}
