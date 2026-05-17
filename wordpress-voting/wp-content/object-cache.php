<?php
/**
 * Redis-backed WordPress object cache drop-in (Post Votes API assessment).
 *
 * Copy this file to wp-content/object-cache.php and configure WP_REDIS_* in wp-config.php.
 *
 * @package PostVotesApi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Redis object cache implementation.
 */
class WP_Object_Cache
{
    /** @var Redis|null */
    private $redis;

    /** @var bool */
    private $redis_active = false;

    /** @var array<string, array<string, mixed>> */
    private $cache = array();

    /** @var array<string, int> */
    private $expire = array();

    /** @var int */
    private $cache_hits = 0;

    /** @var int */
    private $cache_misses = 0;

    /** @var string */
    private $prefix;

    /** @var array<int, string> */
    private $global_groups = array();

    /** @var array<int, string> */
    private $non_persistent_groups = array('counts', 'plugins', 'themes');

  public function __construct()
    {
        $this->prefix = defined('WP_REDIS_PREFIX') ? WP_REDIS_PREFIX : 'wp:';

        if (class_exists('Redis')) {
            try {
                $redis = new Redis();
                $host = defined('WP_REDIS_HOST') ? WP_REDIS_HOST : '127.0.0.1';
                $port = defined('WP_REDIS_PORT') ? (int) WP_REDIS_PORT : 6379;
                $timeout = defined('WP_REDIS_TIMEOUT') ? (float) WP_REDIS_TIMEOUT : 1.0;

                if ($redis->connect($host, $port, $timeout)) {
                    if (defined('WP_REDIS_PASSWORD') && WP_REDIS_PASSWORD !== '') {
                        $redis->auth(WP_REDIS_PASSWORD);
                    }

                    if (defined('WP_REDIS_DATABASE')) {
                        $redis->select((int) WP_REDIS_DATABASE);
                    }

                    $this->redis = $redis;
                    $this->redis_active = true;
                }
            } catch (Exception $e) {
                $this->redis_active = false;
            }
        }
    }

    private function is_persistent_group($group)
    {
        return !in_array($group, $this->non_persistent_groups, true);
    }

    private function build_key($key, $group)
    {
        if (empty($group)) {
            $group = 'default';
        }

        return $this->prefix . $group . ':' . $key;
    }

    private function serialize($value)
    {
        return is_numeric($value) && !is_string($value) ? (string) $value : serialize($value);
    }

    private function unserialize($value)
    {
        if ($value === false || $value === null) {
            return false;
        }

        if (is_numeric($value)) {
            return (int) $value == $value && strpos((string) $value, '.') === false
                ? (int) $value
                : (float) $value;
        }

        $data = @unserialize($value);

        return $data === false && $value !== serialize(false) ? $value : $data;
    }

    public function add($key, $data, $group = 'default', $expire = 0)
    {
        if (wp_suspend_cache_addition()) {
            return false;
        }

        if ($this->get($key, $group, false, $found) !== false) {
            return $found;
        }

        return $this->set($key, $data, $group, $expire);
    }

    public function add_multiple(array $data, $group = 'default', $expire = 0)
    {
        $values = array();

        foreach ($data as $key => $value) {
            $values[$key] = $this->add($key, $value, $group, $expire);
        }

        return $values;
    }

    public function replace($key, $data, $group = 'default', $expire = 0)
    {
        if ($this->get($key, $group, false, $found) === false) {
            return false;
        }

        return $this->set($key, $data, $group, $expire);
    }

    public function set($key, $data, $group = 'default', $expire = 0)
    {
        if (empty($group)) {
            $group = 'default';
        }

        if (!isset($this->cache[$group])) {
            $this->cache[$group] = array();
        }

        $this->cache[$group][$key] = $data;

        if ($expire > 0) {
            $this->expire[$group . ':' . $key] = time() + (int) $expire;
        } else {
            unset($this->expire[$group . ':' . $key]);
        }

        if ($this->redis_active && $this->is_persistent_group($group)) {
            $redis_key = $this->build_key($key, $group);
            $payload = $this->serialize($data);

            if ($expire > 0) {
                return $this->redis->setex($redis_key, (int) $expire, $payload);
            }

            return $this->redis->set($redis_key, $payload);
        }

        return true;
    }

    public function set_multiple(array $data, $group = 'default', $expire = 0)
    {
        $values = array();

        foreach ($data as $key => $value) {
            $values[$key] = $this->set($key, $value, $group, $expire);
        }

        return $values;
    }

    public function get($key, $group = 'default', $force = false, &$found = null)
    {
        if (empty($group)) {
            $group = 'default';
        }

        $expire_key = $group . ':' . $key;

        if (isset($this->expire[$expire_key]) && $this->expire[$expire_key] < time()) {
            $this->delete($key, $group);
            $found = false;
            $this->cache_misses++;

            return false;
        }

        if (!$force && isset($this->cache[$group][$key])) {
            $found = true;
            $this->cache_hits++;

            return $this->cache[$group][$key];
        }

        if ($this->redis_active && $this->is_persistent_group($group)) {
            $redis_key = $this->build_key($key, $group);
            $value = $this->redis->get($redis_key);

            if ($value === false) {
                $found = false;
                $this->cache_misses++;

                return false;
            }

            $data = $this->unserialize($value);

            if (!isset($this->cache[$group])) {
                $this->cache[$group] = array();
            }

            $this->cache[$group][$key] = $data;
            $found = true;
            $this->cache_hits++;

            return $data;
        }

        $found = false;
        $this->cache_misses++;

        return false;
    }

    public function get_multiple($keys, $group = 'default', $force = false)
    {
        $values = array();

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $group, $force);
        }

        return $values;
    }

    public function delete($key, $group = 'default', $deprecated = false)
    {
        if (empty($group)) {
            $group = 'default';
        }

        unset($this->cache[$group][$key], $this->expire[$group . ':' . $key]);

        if ($this->redis_active && $this->is_persistent_group($group)) {
            return (bool) $this->redis->del($this->build_key($key, $group));
        }

        return true;
    }

    public function delete_multiple(array $keys, $group = 'default')
    {
        $values = array();

        foreach ($keys as $key) {
            $values[$key] = $this->delete($key, $group);
        }

        return $values;
    }

    public function incr($key, $offset = 1, $group = 'default')
    {
        $existing = $this->get($key, $group, true, $found);

        if (!$found) {
            $existing = 0;
        }

        if (!is_numeric($existing)) {
            return false;
        }

        $offset = (int) $offset;
        $value = (int) $existing + $offset;

        $this->set($key, $value, $group);

        return $value;
    }

    public function decr($key, $offset = 1, $group = 'default')
    {
        return $this->incr($key, -((int) $offset), $group);
    }

    public function flush()
    {
        $this->cache = array();
        $this->expire = array();

        if ($this->redis_active) {
            $pattern = $this->prefix . '*';
            $iterator = null;

            do {
                $keys = $this->redis->scan($iterator, $pattern, 100);

                if ($keys !== false && $keys !== array()) {
                    $this->redis->del($keys);
                }
            } while ($iterator > 0);

            return true;
        }

        return true;
    }

    public function flush_runtime()
    {
        $this->cache = array();
        $this->expire = array();

        return true;
    }

    public function flush_group($group)
    {
        unset($this->cache[$group]);

        if ($this->redis_active && $this->is_persistent_group($group)) {
            $pattern = $this->prefix . $group . ':*';
            $iterator = null;

            do {
                $keys = $this->redis->scan($iterator, $pattern, 100);

                if ($keys !== false && $keys !== array()) {
                    $this->redis->del($keys);
                }
            } while ($iterator > 0);
        }

        return true;
    }

    public function supports($feature)
    {
        return in_array($feature, array('add_multiple', 'set_multiple', 'get_multiple', 'delete_multiple', 'flush_runtime', 'flush_group'), true);
    }

    public function stats()
    {
        return array(
            'hits' => $this->cache_hits,
            'misses' => $this->cache_misses,
            'redis' => $this->redis_active,
        );
    }

    public function redis_status()
    {
        return $this->redis_active;
    }

    public function add_global_groups($groups)
    {
        $groups = (array) $groups;

        foreach ($groups as $group) {
            $this->global_groups[] = $group;
        }
    }

    public function add_non_persistent_groups($groups)
    {
        $groups = (array) $groups;

        foreach ($groups as $group) {
            $this->non_persistent_groups[] = $group;
        }
    }

    public function switch_to_blog($blog_id)
    {
        $this->prefix = defined('WP_REDIS_PREFIX') ? WP_REDIS_PREFIX : 'wp:';
        $this->prefix .= (int) $blog_id . ':';
    }

    public function reset()
    {
        $this->cache = array();
        $this->expire = array();
    }
}

function wp_cache_init()
{
    $GLOBALS['wp_object_cache'] = new WP_Object_Cache();
}

function wp_cache_add($key, $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->add($key, $data, $group, (int) $expire);
}

function wp_cache_add_multiple(array $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->add_multiple($data, $group, (int) $expire);
}

function wp_cache_replace($key, $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->replace($key, $data, $group, (int) $expire);
}

function wp_cache_set($key, $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->set($key, $data, $group, (int) $expire);
}

function wp_cache_set_multiple(array $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->set_multiple($data, $group, (int) $expire);
}

function wp_cache_get($key, $group = '', $force = false, &$found = null)
{
    global $wp_object_cache;

    return $wp_object_cache->get($key, $group, $force, $found);
}

function wp_cache_get_multiple($keys, $group = '', $force = false)
{
    global $wp_object_cache;

    return $wp_object_cache->get_multiple($keys, $group, $force);
}

function wp_cache_delete($key, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->delete($key, $group);
}

function wp_cache_delete_multiple(array $keys, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->delete_multiple($keys, $group);
}

function wp_cache_incr($key, $offset = 1, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->incr($key, $offset, $group);
}

function wp_cache_decr($key, $offset = 1, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->decr($key, $offset, $group);
}

function wp_cache_flush()
{
    global $wp_object_cache;

    return $wp_object_cache->flush();
}

function wp_cache_flush_runtime()
{
    global $wp_object_cache;

    return $wp_object_cache->flush_runtime();
}

function wp_cache_flush_group($group)
{
    global $wp_object_cache;

    return $wp_object_cache->flush_group($group);
}

function wp_cache_supports($feature)
{
    global $wp_object_cache;

    return $wp_object_cache->supports($feature);
}

function wp_cache_close()
{
    return true;
}

function wp_cache_add_global_groups($groups)
{
    global $wp_object_cache;

    $wp_object_cache->add_global_groups($groups);
}

function wp_cache_add_non_persistent_groups($groups)
{
    global $wp_object_cache;

    $wp_object_cache->add_non_persistent_groups($groups);
}

function wp_cache_switch_to_blog($blog_id)
{
    global $wp_object_cache;

    $wp_object_cache->switch_to_blog($blog_id);
}

function wp_cache_reset()
{
    _deprecated_function(__FUNCTION__, '3.5.0', 'wp_cache_switch_to_blog()');
}
