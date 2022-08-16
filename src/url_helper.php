<?php

function urljoin($base, $rel) {
    if (!$base) {
        return $rel;
    }

    if (!$rel) {
        return $base;
    }

    $uses_relative = array('', 'ftp', 'http', 'gopher', 'nntp', 'imap',
        'wais', 'file', 'https', 'shttp', 'mms',
        'prospero', 'rtsp', 'rtspu', 'sftp',
        'svn', 'svn+ssh', 'ws', 'wss');

    $pbase = parse_url($base);
    $prel = parse_url($rel);

    if ($prel === false || preg_match('/^[a-z0-9\-.]*[^a-z0-9\-.:][a-z0-9\-.]*:/i', $rel)) {
        /*
            Either parse_url couldn't parse this, or the original URL
            fragment had an invalid scheme character before the first :,
            which can confuse parse_url
        */
        $prel = array('path' => $rel);
    }

    if (array_key_exists('path', $pbase) && $pbase['path'] === '/') {
        unset($pbase['path']);
    }

    if (isset($prel['scheme'])) {
        if ($prel['scheme'] !== $pbase['scheme'] || in_array($prel['scheme'], $uses_relative, true) === false) {
            return $rel;
        }
    }

    $merged = array_merge($pbase, $prel);

    // Handle relative paths:
    //   'path/to/file.ext'
    // './path/to/file.ext'
    if (array_key_exists('path', $prel) && !($prel['path'][0] === '/')) {

        // Normalize: './path/to/file.ext' => 'path/to/file.ext'
        if (strpos($prel['path'], './') === 0) {
            $prel['path'] = substr($prel['path'], 2);
        }

        if (array_key_exists('path', $pbase)) {
            $dir = preg_replace('@/[^/]*$@', '', $pbase['path']);
            $merged['path'] = $dir . '/' . $prel['path'];
        } else {
            $merged['path'] = '/' . $prel['path'];
        }

    }

    if(array_key_exists('path', $merged)) {
        // Get the path components, and remove the initial empty one
        $pathParts = explode('/', $merged['path']);
        array_shift($pathParts);

        $path = [];
        $prevPart = '';
        foreach ($pathParts as $part) {
            if ($part === '..' && count($path) > 0) {
                // Cancel out the parent directory (if there's a parent to cancel)
                $parent = array_pop($path);
                // But if it was also a parent directory, leave it in
                if ($parent === '..') {
                    $path[] = $parent;
                    $path[] = $part;
                }
            } else if ($prevPart !== '' || ($part !== '.' && $part !== '')) {
                // Don't include empty or current-directory components
                if ($part === '.') {
                    $part = '';
                }
                $path[] = $part;
            }
            $prevPart = $part;
        }
        $merged['path'] = '/' . implode('/', $path);
    }

    $ret = '';
    if (isset($merged['scheme'])) {
        $ret .= $merged['scheme'] . ':';
    }

    if (isset($merged['scheme']) || isset($merged['host'])) {
        $ret .= '//';
    }

    if (isset($prel['host'])) {
        $hostSource = $prel;
    } else {
        $hostSource = $pbase;
    }

    // username, password, and port are associated with the hostname, not merged
    if (isset($hostSource['host'])) {
        if (isset($hostSource['user'])) {
            $ret .= $hostSource['user'];
            if (isset($hostSource['pass'])) {
                $ret .= ':' . $hostSource['pass'];
            }
            $ret .= '@';
        }
        $ret .= $hostSource['host'];
        if (isset($hostSource['port'])) {
            $ret .= ':' . $hostSource['port'];
        }
    }

    if (isset($merged['path'])) {
        $ret .= $merged['path'];
    }

    if (isset($prel['query'])) {
        $ret .= '?' . $prel['query'];
    }

    if (isset($prel['fragment'])) {
        $ret .= '#' . $prel['fragment'];
    }

    return $ret;
}
if (!function_exists('http_build_url'))
{
    define('HTTP_URL_REPLACE', 1);              // Replace every part of the first URL when there's one of the second URL
    define('HTTP_URL_JOIN_PATH', 2);            // Join relative paths
    define('HTTP_URL_JOIN_QUERY', 4);           // Join query strings
    define('HTTP_URL_STRIP_USER', 8);           // Strip any user authentication information
    define('HTTP_URL_STRIP_PASS', 16);          // Strip any password authentication information
    define('HTTP_URL_STRIP_AUTH', 32);          // Strip any authentication information
    define('HTTP_URL_STRIP_PORT', 64);          // Strip explicit port numbers
    define('HTTP_URL_STRIP_PATH', 128);         // Strip complete path
    define('HTTP_URL_STRIP_QUERY', 256);        // Strip query string
    define('HTTP_URL_STRIP_FRAGMENT', 512);     // Strip any fragments (#identifier)
    define('HTTP_URL_STRIP_ALL', 1024);         // Strip anything but scheme and host

    // Build an URL
    // The parts of the second URL will be merged into the first according to the flags argument.
    //
    // @param   mixed           (Part(s) of) an URL in form of a string or associative array like parse_url() returns
    // @param   mixed           Same as the first argument
    // @param   int             A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
    // @param   array           If set, it will be filled with the parts of the composed url like parse_url() would return
    function http_build_url($url = '', $parts = array(), $flags = HTTP_URL_REPLACE, &$new_url = false): string
    {
        $keys = array('user','pass','port','path','query','fragment');

        // HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
        if ($flags & HTTP_URL_STRIP_ALL)
        {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
            $flags |= HTTP_URL_STRIP_PORT;
            $flags |= HTTP_URL_STRIP_PATH;
            $flags |= HTTP_URL_STRIP_QUERY;
            $flags |= HTTP_URL_STRIP_FRAGMENT;
        }
        // HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
        else if ($flags & HTTP_URL_STRIP_AUTH)
        {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
        }

        // Parse the original URL
        // - Suggestion by Sayed Ahad Abbas
        //   In case you send a parse_url array as input
        $parse_url = !is_array($url) ? parse_url($url) : $url;

        // Scheme and Host are always replaced
        if (isset($parts['scheme'])) {
            $parse_url['scheme'] = $parts['scheme'];
        }
        if (isset($parts['host'])) {
            $parse_url['host'] = $parts['host'];
        }

        // (If applicable) Replace the original URL with it's new parts
        if ($flags & HTTP_URL_REPLACE)
        {
            foreach ($keys as $key)
            {
                if (isset($parts[$key])) {
                    $parse_url[$key] = $parts[$key];
                }
            }
        } else
        {
            // Join the original URL path with the new path
            if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH))
            {
                if (isset($parse_url['path'])) {
                    $parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
                } else {
                    $parse_url['path'] = $parts['path'];
                }
            }

            // Join the original query string with the new query string
            if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY))
            {
                if (isset($parse_url['query'])) {
                    $parse_url['query'] .= '&' . $parts['query'];
                } else {
                    $parse_url['query'] = $parts['query'];
                }
            }
        }

        // Strips all the applicable sections of the URL
        // Note: Scheme and Host are never stripped
        foreach ($keys as $key)
        {
            if ($flags & (int)constant('HTTP_URL_STRIP_' . strtoupper($key))) {
                unset($parse_url[$key]);
            }
        }


        $new_url = $parse_url;

        return
            ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
            .((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') .'@' : '')
            .($parse_url['host'] ?: '')
            .((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
            .($parse_url['path'] ?: '')
            .((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
            .((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '')
            ;
    }
}