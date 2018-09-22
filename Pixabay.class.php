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
         * _base
         * 
         * @var     string (default: 'https://pixabay.com/api')
         * @access  protected
         */
        protected $_base = 'https://pixabay.com/api';

        /**
         * _hd
         * 
         * @var     bool (default: false)
         * @access  protected
         */
        protected $_hd = false;

        /**
         * _imageType
         * 
         * @var     string (default: 'photo')
         * @access  protected
         */
        protected $_imageType = 'photo';

        /**
         * _key
         * 
         * @var     null|string
         * @access  protected
         */
        protected $_key = null;

        /**
         * _lastRemoteRequestHeaders
         * 
         * @var     array (default: array())
         * @access  protected
         */
        protected $_lastRemoteRequestHeaders = array();

        /**
         * _limit
         * 
         * @var     int (default: 200)
         * @access  protected
         */
        protected $_limit = 200;

        /**
         * _maxPerPage
         * 
         * @var     int (default: 200)
         * @access  protected
         */
        protected $_maxPerPage = 200;

        /**
         * _minHeight
         * 
         * @var     int (default: 0)
         * @access  protected
         */
        protected $_minHeight = 0;

        /**
         * _minPerPage
         * 
         * @var     int (default: 3)
         * @access  protected
         */
        protected $_minPerPage = 3;

        /**
         * _minWidth
         * 
         * @var     int (default: 0)
         * @access  protected
         */
        protected $_minWidth = 0;

        /**
         * _order
         * 
         * @var     string (default: 'popular')
         * @access  protected
         */
        protected $_order = 'popular';

        /**
         * _paths
         * 
         * @var     array
         * @access  protected
         */
        protected $_paths = array(
            'search' => ''
        );

        /**
         * _rateLimits
         * 
         * @var     null|array
         * @access  protected
         */
        protected $_rateLimits = null;

        /**
         * _requestTimeout
         * 
         * @var     int (default: 10)
         * @access  protected
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
         * _addUrlParams
         * 
         * @access  protected
         * @param   string $url
         * @param   array $params
         * @return  string
         */
        protected function _addUrlParams(string $url, array $params): string
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
         * _get
         * 
         * @access  protected
         * @param   array $requestData
         * @return  null|array
         */
        protected function _get(array $requestData): ?array
        {
            // Make the request
            $url = $this->_getSearchUrl($requestData);
            $response = $this->_requestUrl($url);
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
         * _getSearchUrl
         * 
         * @access  protected
         * @param   array $requestData
         * @return  string
         */
        protected function _getSearchUrl(array $requestData): string
        {
            $base = $this->_base;
            $path = $this->_paths['search'];
            $data = $requestData;
            $url = ($base) . ($path);
            $url = $this->_addUrlParams($url, $data);
            return $url;
        }

        /**
         * _requestUrl
         * 
         * @access  protected
         * @param   string $url
         * @return  null|string
         */
        protected function _requestUrl(string $url): ?string
        {
            $streamContext = $this->_getRequestStreamContext();
            $response = file_get_contents($url, false, $streamContext);
            if ($response === false) {
                return null;
            }
            if (isset($http_response_header) === true) {
                $this->_lastRemoteRequestHeaders = $http_response_header;
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
        public function setLimit($limit)
        {
            $this->_limit = $limit;
            if ($limit < $this->_minPerPage) {
                $this->_limit = $this->_minPerPage;
            }
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
        public function setOffset($offset)
        {
            $this->_offset = $offset;
        }
    }
