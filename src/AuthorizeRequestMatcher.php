<?php

declare(strict_types=1);

namespace winwin\omniauth;

use Psr\Http\Message\ServerRequestInterface;

class AuthorizeRequestMatcher implements AuthorizeRequestMatcherInterface
{
    /**
     * @var string[]
     */
    private $allowList;

    /**
     * @var string|null
     */
    private $allowPattern;

    /**
     * AuthorizeRequestMatcher constructor.
     *
     * @param string[] $allowList
     * @param string   $allowPattern
     */
    public function __construct(array $allowList = [], ?string $allowPattern = null)
    {
        $this->allowList = $allowList;
        $this->allowPattern = $allowPattern;
    }

    /**
     * {@inheritdoc}
     */
    public function match(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        if (count($this->allowList) > 0 && in_array($path, $this->allowList, true)) {
            return false;
        }

        if (null !== $this->allowPattern && preg_match($this->allowPattern, $path)) {
            return false;
        }

        return true;
    }
}
