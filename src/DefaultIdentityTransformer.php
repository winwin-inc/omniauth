<?php

declare(strict_types=1);

namespace winwin\omniauth;

class DefaultIdentityTransformer implements IdentityTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($identity, string $strategy)
    {
        return $identity;
    }
}
