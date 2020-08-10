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
     * @var string[]
     */
    private $allowPatterns;

    /**
     * AuthorizeRequestMatcher constructor.
     *
     * @param string[] $allowList
     * @param string[] $allowPatterns
     */
    public function __construct(array $allowList = [], array $allowPatterns = [])
    {
        $this->allowList = $allowList;
        $this->allowPatterns = $allowPatterns;
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

        if (count($this->allowPatterns) > 0) {
            foreach ($this->allowPatterns as $pattern) {
                if (preg_match($pattern, $path)) {
                    return false;
                }
            }
        }

        return true;
    }
}
