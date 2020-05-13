<?php

namespace GuardedCookie {
    use GuardedCookie\Tests\SetCookie;

    function setcookie($name, $value, $options)
    {
        SetCookie::getInstance()->set($name, $value, $options);
    }
}

namespace GuardedCookie\Tests {
    class SetCookie
    {
        private $cookies;
        private $cookie_options;

        public static function getInstance()
        {
            static $instance;
            if (isset($instance)) {
                return $instance;
            }
            $instance = new self;
            return $instance;
        }

        public function set($name, $value, $options)
        {
            $this->cookies[$name] = $value;
            $this->cookie_options[$name] = $options;
        }

        public function get($name)
        {
            return $this->cookies[$name] ?? null;
        }

        public function getOptions($name)
        {
            return $this->cookies[$name];
        }
    }
}
