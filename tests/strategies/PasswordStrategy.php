<?php

declare(strict_types=1);

namespace winwin\omniauth\strategies;

use Psr\Http\Message\ResponseInterface;
use winwin\omniauth\strategy\AbstractStrategy;

class PasswordStrategy extends AbstractStrategy
{
    /**
     * @var array
     */
    private $users;

    public function authenticate(): ResponseInterface
    {
        $content = <<<"EOF"
<!doctype html>
<html lang="en">
  <body>
    <form method="post" action="{$this->action('verify')}">
      <div class="row">
        <label for="username">Username: </label>
        <input id="username" name="username" />
      </div>
      <div class="row">
        <label for="password">Password: </label>
        <input type="password" name="password" />
      </div>
      <div class="row">
        <input type="submit" value="Sign In" />
      </div>
      <p class="tip">Try username admin and password admin</p>
    </form>
    <a href="/auth/provider">External Login</a>
    <a href="/auth/git-hub">Github Login</a>
  </body>
</html>
EOF;

        return $this->getResponseFactory()->createResponse()
            ->withBody($this->getStreamFactory()->createStream($content));
    }

    public function verify(): ResponseInterface
    {
        $this->buildUsers();
        $params = $this->getRequest()->getParsedBody();
        if (!$this->hasUser($params['username'])) {
            return $this->redirect($this->action());
        }
        if (!password_verify($params['password'], $this->getPasswordHash($params['username']))) {
            return $this->redirect($this->action());
        }

        return $this->login($this->getIdentity($params['username']));
    }

    private function buildUsers(): void
    {
        if (!isset($this->users)) {
            $this->users = [];
            foreach ($this->getOption('users', []) as $user) {
                $this->users[$user['user_id']] = $user;
            }
        }
    }

    private function hasUser($username): bool
    {
        return isset($this->users[$username]);
    }

    private function getPasswordHash($username): string
    {
        if (!isset($this->users[$username]['password'])) {
            throw new \InvalidArgumentException("User $username password not set");
        }

        return $this->users[$username]['password'];
    }

    private function getIdentity($username): array
    {
        if (!isset($this->users[$username])) {
            return [];
        }
        $user = $this->users[$username];
        unset($user['password']);

        return $user;
    }
}
