<?php

    /**
     * Pixabay
     * 
     * PHP wrapper for Pixabay
     * 
     * @link    https://pixabay.com/api/docs/
     * @link    https://github.com/onassar/PHP-Pixabay
     * @author  Oliver Nassar <onassar@gmail.com>
     */
    class Pixabay
    {
        /**
         * _attemptSleepDelay
         * 
         * @access  protected
         * @var     int (default: 2000) in milliseconds
         */
        protected $_attemptSleepDelay = 2000;

        /**
         * _base
         * 
         * @access  protected
         * @var     string (default: 'https://pixabay.com/api/')
         */
        protected $_base = 'https://pixabay.com/api/';

        /**
         * _hd
         * 
         * @access  protected
         * @var     bool (default: false)
         */
        protected $_hd = false;

        /**
         * _imageType
         * 
         * @access  protected
         * @var     string (default: 'photo')
         */
        protected $_imageType = 'photo';

        /**
         * _key
         * 
         * @access  protected
         * @var     null|string (default: null)
         */
        protected $_key = null;

        /**
         * _lastRemoteRequestHeaders
         * 
         * @access  protected
         * @var     array (default: array())
         */
        protected $_lastRemoteRequestHeaders = array();

        /**
         * _limit
         * 
         * @access  protected
         * @var     int (default: 200)
         */
        protected $_limit = 200;

        /**
         * _logClosure
         * 
         * @access  protected
         * @var     null|Closure (default: null)
         */
        protected $_logClosure = null;

        /**
         * _maxAttempts
         * 
         * @access  protected
         * @var     int (default: 2)
         */
        protected $_maxAttempts = 2;

        /**
         * _maxPerPage
         * 
         * @access  protected
         * @var     int (default: 200)
         */
        protected $_maxPerPage = 200;

        /**
         * _minHeight
         * 
         * @access  protected
         * @var     int (default: 0)
         */
        protected $_minHeight = 0;

        /**
         * _minPerPage
         * 
         * @access  protected
         * @var     int (default: 3)
         */
        protected $_minPerPage = 3;

        /**
         * _minWidth
         * 
         * @access  protected
         * @var     int (default: 0)
         */
        protected $_minWidth = 0;

        /**
         * _order
         * 
         * @access  protected
         * @var     string (default: 'popular')
         */
        protected $_order = 'popular';

        /**
         * _paths
         * 
         * @access  protected
         * @var     array
         */
        protected $_paths = array(
            'search' => ''
        );

        /**
         * _rateLimits
         * 
         * @access  protected
         * @var     null|array (default: null)
         */
        protected $_rateLimits = null;

        /**
         * _requestApproach
         * 
         * @access  protected
         * @var     string (default: 'streams')
         */
        protected $_requestApproach = 'streams';

        /**
         * _requestTimeout
         * 
         * @access  protected
         * @var     int (default: 10)
         */
        protected $_requestTimeout = 10;

        /**
         * __construct
         * 
         * @access  public
         * @param   string $key
         * @return  void
         */
        public function __construct(string $key)
        {
            $this->_key = $key;
        }

        /**
         * _addURLParams
         * 
         * @access  protected
         * @param   string $url
         * @param   array $params
         * @return  string
         */
        protected function _addURLParams(string $url, array $params): string
        {
            $query = http_build_query($params);
            $piece = parse_url($url, PHP_URL_QUERY);
            if ($piece === null) {
                $url = ($url) . '?' . ($query);
                return $url;
            }
            $url = ($url) . '&' . ($query);
            return $url;
        }

        /**
         * _attempt
         * 
         * Method which accepts a closure, and repeats calling it until
         * $maxAttempts have been made.
         * 
         * This was added to account for requests failing (for a variety of
         * reasons).
         * 
         * @access  protected
         * @param   Closure $closure
         * @param   int $attempt (default: 1)
         * @return  null|string
         */
        protected function _attempt(Closure $closure, int $attempt = 1): ?string
        {
            try {
                $response = call_user_func($closure);
                if ($attempt !== 1) {
                    $msg = 'Subsequent success on attempt #' . ($attempt);
                    $this->_log($msg);
                }
                return $response;
            } catch (Exception $exception) {
                $msg = 'Failed closure';
                $this->_log($msg);
                $msg = $exception->getMessage();
                $this->_log($msg);
                $maxAttempts = $this->_maxAttempts;
                if ($attempt < $maxAttempts) {
                    $delay = $this->_attemptSleepDelay;
                    $msg = 'Going to sleep for ' . ($delay);
                    LogUtils::log($msg);
                    $this->_sleep($delay);
                    $response = $this->_attempt($closure, $attempt + 1);
                    return $response;
                }
                $msg = 'Failed attempt';
                $this->_log($msg);
            }
            return null;
        }

        /**
         * _get
         * 
         * @access  protected
         * @param   array $requestData
         * @return  null|array
         */
        protected function _get(array $requestData): ?array
        {
            // Make the request
            $url = $this->_getSearchURL($requestData);
            $response = $this->_requestURL($url);
            if ($response === null) {
                return null;
            }
            $this->_rateLimits = $this->_getRateLimits();

            // Invalid json response
            json_decode($response);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            // Response formatting
            $response = json_decode($response, true);
            return $response;
        }

        /**
         * _getFormattedSearchResponse
         * 
         * @access  protected
         * @param   string $query
         * @param   array $response
         * @return  array
         */
        protected function _getFormattedSearchResponse(string $query, array $response): array
        {
            $results = $response['hits'];
            foreach ($results as $index => $hit) {
                $results[$index]['original_query'] = $query;
            }
            return $results;
        }

        /**
         * _getPaginationData
         * 
         * @access  protected
         * @return  array
         */
        protected function _getPaginationData(): array
        {
            $perPage = $this->_getResultsPerPage();
            $offset = $this->_offset;
            $offset = $this->_roundToLower($offset, $perPage);
            $page = ceil($offset / $perPage) + 1;
            $paginationData = array(
                'page' => $page,
                'per_page' => $perPage
            );
            return $paginationData;
        }

        /**
         * _getQueryData
         * 
         * @access  protected
         * @param   string $query
         * @return  array
         */
        protected function _getQueryData(string $query): array
        {
            $responseGroup = 'image_details';
            if ($this->_hd === true) {
                $responseGroup = 'high_resolution';
            }
            $queryData = array(
                'q' => $query,
                'key' => $this->_key,
                'response_group' => $responseGroup,
                'order' => $this->_order,
                'safesearch' => 'true',
                'image_type' => $this->_imageType,
                'min_width' => $this->_minWidth,
                'min_height' => $this->_minHeight
            );
            return $queryData;
        }

        /**
         * _getResultsPerPage
         * 
         * @access  protected
         * @return  int
         */
        protected function _getResultsPerPage(): int
        {
            $resultsPerPage = min($this->_limit, $this->_maxPerPage);
            return $resultsPerPage;
        }

        /**
         * _getRateLimits
         * 
         * @see     http://php.net/manual/en/reserved.variables.httpresponseheader.php
         * @access  protected
         * @return  null|array
         */
        protected function _getRateLimits(): ?array
        {
            $headers = $this->_lastRemoteRequestHeaders;
            if ($headers === null) {
                return null;
            }
            $formatted = array();
            foreach ($headers as $header) {
                $pieces = explode(':', $header);
                if (count($pieces) >= 2) {
                    $formatted[$pieces[0]] = $pieces[1];
                }
            }
            $rateLimits = array(
                'remaining' => false,
                'limit' => false,
                'reset' => false
            );
            if (isset($formatted['X-RateLimit-Remaining']) === true) {
                $rateLimits['remaining'] = (int) trim($formatted['X-RateLimit-Remaining']);
            }
            if (isset($formatted['X-RateLimit-Limit']) === true) {
                $rateLimits['limit'] = (int) trim($formatted['X-RateLimit-Limit']);
            }
            if (isset($formatted['X-RateLimit-Reset']) === true) {
                $rateLimits['reset'] = (int) trim($formatted['X-RateLimit-Reset']);
            }
            return $rateLimits;
        }

        /**
         * _getRequestData
         * 
         * @access  protected
         * @param   string $query
         * @return  array
         */
        protected function _getRequestData(string $query): array
        {
            $paginationData = $this->_getPaginationData();
            $queryData = $this->_getQueryData($query);
            $requestData = array_merge($paginationData, $queryData);
            return $requestData;
        }

        /**
         * _getRequestStreamContext
         * 
         * @access  protected
         * @return  resource
         */
        protected function _getRequestStreamContext()
        {
            $requestTimeout = $this->_requestTimeout;
            $options = array(
                'http' => array(
                    'ignore_errors' => true,
                    'method' => 'GET',
                    'timeout' => $requestTimeout
                )
            );
            $streamContext = stream_context_create($options);
            return $streamContext;
        }

        /**
         * _getSearchURL
         * 
         * @access  protected
         * @param   array $requestData
         * @return  string
         */
        protected function _getSearchURL(array $requestData): string
        {
            $base = $this->_base;
            $path = $this->_paths['search'];
            $data = $requestData;
            $url = ($base) . ($path);
            $url = $this->_addURLParams($url, $data);
            return $url;
        }

        /**
         * _log
         * 
         * @access  protected
         * @param   string $msg
         * @return  bool
         */
        protected function _log(string $msg): bool
        {
            if ($this->_logClosure === null) {
                error_log($msg);
                return false;
            }
            $closure = $this->_logClosure;
            $args = array($msg);
            call_user_func_array($closure, $args);
            return true;
        }

        /**
         * _parseCURLResponse
         * 
         * This method was required because at times the cURL requests would not
         * return the headers, which would cause issues.
         * 
         * @access  protected
         * @param   string $response
         * @return  array
         */
        protected function _parseCURLResponse(string $response): array
        {
            $delimiter = "\r\n\r\n";
            $pieces = explode($delimiter, $response);
            if (count($pieces) === 1) {
                $headers = '';
                $body = $response;
                $response = array($headers, $body);
                return $response;
            }
            list($headers, $body) = explode("\r\n\r\n", $response, 2);
            $response = array($headers, $body);
            return $response;
        }

        /**
         * _requestURL
         * 
         * @throws  Exception
         * @access  protected
         * @param   string $url
         * @return  null|string
         */
        protected function _requestURL(string $url): ?string
        {
            if ($this->_requestApproach === 'cURL') {
                $response = $this->_requestURLUsingCURL($url);
                return $response;
            }
            if ($this->_requestApproach === 'streams') {
                $response = $this->_requestURLUsingStreams($url);
                return $response;
            }
            $msg = 'Invalid request approach';
            throw new Exception($msg);
        }

        /**
         * _requestURLUsingCURL
         * 
         * @see     https://stackoverflow.com/a/9183272/115025
         * @access  protected
         * @param   string $url
         * @return  null|string
         */
        protected function _requestURLUsingCURL(string $url): ?string
        {
            $closure = function() use ($url) {
                $requestTimeout = $this->_requestTimeout;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $requestTimeout);
                curl_setopt($ch, CURLOPT_TIMEOUT, $requestTimeout);
                curl_setopt($ch, CURLOPT_HEADER, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                return $response;
            };
            $response = $this->_attempt($closure);
            if ($response === false) {
                return null;
            }
            if ($response === null) {
                return null;
            }
            list($headers, $body) = $this->_parseCURLResponse($response);
            $this->_setCURLResponseHeaders($headers);
            return $body;
        }

        /**
         * _requestURLUsingStreams
         * 
         * @access  protected
         * @param   string $url
         * @return  null|string
         */
        protected function _requestURLUsingStreams(string $url): ?string
        {
            $closure = function() use ($url) {
                $streamContext = $this->_getRequestStreamContext();
                $response = file_get_contents($url, false, $streamContext);
                $this->_lastRemoteRequestHeaders = $http_response_header ?? $this->_lastRemoteRequestHeaders;
                return $response;
            };
            $response = $this->_attempt($closure);
            if ($response === false) {
                return null;
            }
            if ($response === null) {
                return null;
            }
            return $response;
        }

        /**
         * _roundToLower
         * 
         * @access  protected
         * @param   int $int
         * @param   int $interval
         * @return  int
         */
        protected function _roundToLower(int $int, int $interval): int
        {
            $int = (string) $int;
            $int = preg_replace('/[^0-9]/', '', $int);
            $int = (int) $int;
            $lowered = floor($int / $interval) * $interval;
            return $lowered;
        }

        /**
         * _setCURLResponseHeaders
         * 
         * @access  protected
         * @param   string $headers
         * @return  void
         */
        protected function _setCURLResponseHeaders(string $headers): void
        {
            $headers = explode("\n", $headers);
            $this->_lastRemoteRequestHeaders = $headers;
        }

        /**
         * _sleep
         * 
         * @access  protected
         * @param   int $duration in milliseconds
         * @return  void
         */
        protected function _sleep(int $duration): void
        {
            usleep($duration * 1000);
        }

        /**
         * getRateLimits
         * 
         * @access  public
         * @return  null|array
         */
        public function getRateLimits(): ?array
        {
            $rateLimits = $this->_rateLimits;
            return $rateLimits;
        }

        /**
         * search
         * 
         * @access  public
         * @param   string $query
         * @param   array &persistent (default: array())
         * @return  null|array
         */
        public function search(string $query, array &$persistent = array()): ?array
        {
            // Request results
            $requestData = $this->_getRequestData($query);
            $response = $this->_get($requestData);

            // Failed request
            if ($response === null) {
                return array();
            }
            if (isset($response['hits']) === false) {
                return array();
            }

            // Format + more than enough found
            $results = $this->_getFormattedSearchResponse($query, $response);
            $resultsCount = count($results);
            $mod = $this->_offset % $this->_getResultsPerPage();
            if ($mod !== 0) {
                array_splice($results, 0, $mod);
            }
            $persistent = array_merge($persistent, $results);
            $persistentCount = count($persistent);
            if ($persistentCount >= $this->_limit) {
                return array_slice($persistent, 0, $this->_limit);
            }
            if ($resultsCount < $this->_maxPerPage) {
                return array_slice($persistent, 0, $this->_limit);
            }

            // Recusively get more
            $this->_offset += count($results);
            return $this->search($query, $persistent);
        }

        /**
         * setHD
         * 
         * @access  public
         * @param   bool $hd
         * @return  void
         */
        public function setHD(bool $hd): void
        {
            $this->_hd = $hd;
        }

        /**
         * setImageType
         * 
         * @access  public
         * @param   string $imageType
         * @return  void
         */
        public function setImageType($imageType)
        {
            $this->_imageType = $imageType;
        }

        /**
         * setLimit
         * 
         * @access  public
         * @param   string $limit
         * @return  void
         */
        public function setLimit($limit): void
        {
            $this->_limit = $limit;
            if ($limit < $this->_minPerPage) {
                $this->_limit = $this->_minPerPage;
            }
        }

        /**
         * setLogClosure
         * 
         * @access  public
         * @param   Closure $closure
         * @return  void
         */
        public function setLogClosure(Closure $closure): void
        {
            $this->_logClosure = $closure;
        }

        /**
         * setMaxAttempts
         * 
         * @access  public
         * @param   int $maxAttempts
         * @return  void
         */
        public function setMaxAttempts(int $maxAttempts): void
        {
            $this->_maxAttempts = $maxAttempts;
        }

        /**
         * setMinHeight
         * 
         * @access  public
         * @param   int $minHeight
         * @return  void
         */
        public function setMinHeight(int $minHeight): void
        {
            $this->_minHeight = $minHeight;
        }

        /**
         * setMinWidth
         * 
         * @access  public
         * @param   int $minWidth
         * @return  void
         */
        public function setMinWidth(int $minWidth): void
        {
            $this->_minWidth = $minWidth;
        }

        /**
         * setOrder
         * 
         * @access  public
         * @param   string $order
         * @return  void
         */
        public function setOrder(string $order): void
        {
            $this->_order = $order;
        }

        /**
         * setOffset
         * 
         * @access  public
         * @param   string $offset
         * @return  void
         */
        public function setOffset($offset): void
        {
            $this->_offset = $offset;
        }

        /**
         * setRequestApproach
         * 
         * @access  public
         * @param   string $requestApproach
         * @return  void
         */
        public function setRequestApproach(string $requestApproach): void
        {
            $this->_requestApproach = $requestApproach;
        }

        /**
         * setRequestTimeout
         * 
         * @access  public
         * @param   int $requestTimeout
         * @return  void
         */
        public function setRequestTimeout(int $requestTimeout): void
        {
            $this->_requestTimeout = $requestTimeout;
        }
    }
