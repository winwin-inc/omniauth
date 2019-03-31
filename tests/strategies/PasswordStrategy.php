<?php

use winwin\omniauth\AbstractStrategy;

class PasswordStrategy extends AbstractStrategy
{
    /**
     * @var array
     */
    private $users;

    public function request()
    {
        $content = <<<"EOF"
<!doctype html>
<html lang="zh-cn">
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
</form>
</body>
</html>
EOF;
        return $this->responseFactory->createResponse()
            ->withBody($this->streamFactory->createStream($content));
    }

    public function verify()
    {
        $this->buildUsers();
        $params = $this->request->getParsedBody();
        if (!$this->hasUser($params['username'])) {
            return $this->redirect($this->action());
        }
        if (!password_verify($params['password'], $this->getPasswordHash($params['username']))) {
            return $this->redirect($this->action());
        }
        $this->omniauth->setIdentity($this->getIdentity($params['username']));
        return $this->redirect($this->omniauth->getCallbackUrl());
    }

    private function buildUsers()
    {
        if (isset($this->users)) {
            return;
        }
        if (isset($this->options['users'])) {
            foreach ($this->options['users'] as $user) {
                $this->users[$user['user_id']] = $user;
            }
        }
    }

    private function hasUser($username)
    {
        return isset($this->users[$username]);
    }

    private function getPasswordHash($username)
    {
        if (!isset($this->users[$username]['password'])) {
            throw new \InvalidArgumentException("User $username password not set");
        }
        return $this->users[$username]['password'];
    }

    private function getIdentity($username)
    {
        if (!isset($this->users[$username])) {
            return [];
        }
        $user = $this->users[$username];
        unset($user['password']);
        return $user;
    }
}
