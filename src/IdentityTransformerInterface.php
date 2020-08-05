<?php

declare(strict_types=1);

namespace winwin\omniauth;

interface IdentityTransformerInterface
{
    /**
     * Transform identity.
     *
     * @param array $identity
     *
     * @return mixed
     */
    public function transform(array $identity, string $strategy);
}
