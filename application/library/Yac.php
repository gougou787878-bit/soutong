<?php

if (!class_exists('\Yac')){
    final class Yac
    {

        protected $_prefix = "";


        public function __construct(string $prefix = "")
        {
        }

        /**
         * @param string|array $key
         * @param mixed $value
         * @param int $ttl
         * @return bool|null
         */
        public function add($key,  $value, int $ttl = 0): ?bool
        {
            return true;
        }

        /**
         * @param string|array $key
         * @param mixed|NULL $cas
         * @return bool|null
         */
        public function get($key,  &$cas = NULL): ?string
        {
            return null;
        }

        /**
         * @param string|array $key
         * @param mixed $value
         * @param int $ttl
         * @return bool|null
         */
        public function set($key,  $value, int $ttl = 0): ?bool
        {
            return true;
        }

        /**
         * @param string|array $key
         * @param int $delay
         * @return bool|null
         */
        public function delete($key, int $delay = 0): ?bool
        {
            return true;
        }

        public function flush(): bool
        {
            return true;
        }

        public function info(): array
        {
            return [];
        }

        public function dump(int $limit = 0): ?array
        {
            return [];
        }
    }
}
