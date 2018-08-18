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
         * _minHeight
         * 
         * @var     int (default: 0)
         * @access  protected
         */
        protected $_minHeight = 0;

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
         * _page
         * 
         * @var     int (default: 1)
         * @access  protected
         */
        protected $_page = 1;

        /**
         * _photosPerPage
         * 
         * @var     int (default: 20)
         * @access  protected
         */
        protected $_photosPerPage = 20;

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
         * _type
         * 
         * @var     string (default: 'photo')
         * @access  protected
         */
        protected $_type = 'photo';

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
         * _getSearchQueryData
         * 
         * @access  protected
         * @param   array $args
         * @return  array
         */
        protected function _getSearchQueryData(array $args): array
        {
            $responseGroup = 'image_details';
            if ($this->_hd === true) {
                $responseGroup = 'high_resolution';
            }
            $data = array(
                'key' => $this->_key,
                'response_group' => $responseGroup,
            );
            $data = array_merge($data, $args);
            return $data;
        }

        /**
         * _getSearchUrl
         * 
         * @access  protected
         * @param   array $args
         * @return  string
         */
        protected function _getSearchUrl(array $args): string
        {
            $base = $this->_base;
            $path = '/';
            $data = $this->_getSearchQueryData($args);
            $url = ($base) . ($path);
            $url = $this->_addUrlParams($url, $data);
            return $url;
        }

        /**
         * _get
         * 
         * @access  protected
         * @param   array $args
         * @return  null|array
         */
        protected function _get(array $args): ?array
        {
            // Make the request
            $url = $this->_getSearchUrl($args);
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
            foreach ($response['hits'] as $index => $hit) {
                $response['hits'][$index]['original_query'] = $query;
            }
            return $response;
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
         * _getRequestArguments
         * 
         * @access  protected
         * @param   string $query
         * @param   array $args (default: array())
         * @return  array
         */
        protected function _getRequestArguments(string $query, array $args = array()): array
        {
            $defaults = array(
                'q' => $query,
                'order' => $this->_order,
                'safesearch' => 'true',
                'page' => $this->_page,
                'per_page' => $this->_photosPerPage,
                'image_type' => $this->_type,
                'min_width' => $this->_minWidth,
                'min_height' => $this->_minHeight
            );
            $args = array_merge($defaults, $args);
            return $args;
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
            if (isset($http_response_header) === true) {
                $this->_lastRemoteRequestHeaders = $http_response_header;
            }
            if ($response === false) {
                return null;
            }
            return $response;
        }

        /**
         * getRateLimits
         * 
         * @access  public
         * @return  null|array
         */
        public function getRateLimits(): ?array
        {
            return $this->_rateLimits;
        }

        /**
         * search
         * 
         * @access  public
         * @param   string $query
         * @param   array $args (default: array())
         * @return  null|array
         */
        public function search(string $query, array $args = array()): ?array
        {
            $args = $this->_getRequestArguments($query, $args);
            $response = $this->_get($args);
            if ($response === null) {
                return null;
            }
            if (isset($response['hits']) === false) {
                return null;
            }
            $response = $this->_getFormattedSearchResponse($query, $response);
            return $response;
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
         * setPage
         * 
         * @access  public
         * @param   int $page
         * @return  void
         */
        public function setPage(int $page): void
        {
            $this->_page = $page;
        }

        /**
         * setPhotosPerPage
         * 
         * @access  public
         * @param   int $photosPerPage
         * @return  void
         */
        public function setPhotosPerPage(int $photosPerPage): void
        {
            $this->_photosPerPage = $photosPerPage;
        }

        /**
         * setType
         * 
         * @access  public
         * @param   string $type
         * @return  void
         */
        public function setType(string $type): void
        {
            $this->_type = $type;
        }
    }
