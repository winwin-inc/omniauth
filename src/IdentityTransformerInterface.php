<?php

declare(strict_types=1);

namespace winwin\omniauth;

interface IdentityTransformerInterface
{
    /**
     * Transform identity.
     *
     * @param mixed $identity
     *
     * @return mixed
     */
    public function transform($identity, string $strategy);
}
