<?php

    // Namespace overhead
    namespace onassar\Pixabay;
    use onassar\RemoteRequests;

    /**
     * Pixabay
     * 
     * PHP wrapper for Pixabay
     * 
     * @link    https://pixabay.com/api/docs/
     * @link    https://github.com/onassar/PHP-Pixabay
     * @author  Oliver Nassar <onassar@gmail.com>
     * @extends RemoteRequests\Base
     */
    class Pixabay extends RemoteRequests\Base
    {
        /**
         * RemoteRequets\Pagination
         * 
         */
        use RemoteRequests\Pagination;

        /**
         * RemoteRequets\RateLimits
         * 
         */
        use RemoteRequests\RateLimits;

        /**
         * RemoteRequets\SearchAPI
         * 
         */
        use RemoteRequests\SearchAPI;

        /**
         * _hd
         * 
         * @access  protected
         * @var     bool (default: false)
         */
        protected $_hd = false;

        /**
         * _host
         * 
         * @access  protected
         * @var     string (default: 'pixabay.com')
         */
        protected $_host = 'pixabay.com';

        /**
         * _imageType
         * 
         * @access  protected
         * @var     string (default: 'photo')
         */
        protected $_imageType = 'photo';

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
         * Defines the minimum number of results that need need to be retrieved
         * for requests. This is unique to Pixabay (at the time of
         * documentation). This is relevant due to recursive-search which could
         * have generated requests that desired 1 or 2 objects per request.
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
            'search' => '/api/'
        );

        /**
         * __construct
         * 
         * @see     https://i.imgur.com/GPI3Ttu.png
         * @access  public
         * @return  void
         */
        public function __construct()
        {
            // $this->_maxPerPage = 200;
            $this->_maxPerPage = 16;
            $this->_responseResultsIndex = 'hits';
        }

        /**
         * _getQueryRequestData
         * 
         * @access  protected
         * @param   string $query
         * @return  array
         */
        protected function _getQueryRequestData(string $query): array
        {
            $responseGroup = 'image_details';
            if ($this->_hd === true) {
                $responseGroup = 'high_resolution';
            }
            $queryRequestData = array(
                'q' => $query,
                'key' => $this->_apiKey,
                'response_group' => $responseGroup,
                'order' => $this->_order,
                'safesearch' => 'true',
                'image_type' => $this->_imageType,
                'min_width' => $this->_minWidth,
                'min_height' => $this->_minHeight
            );
            return $queryRequestData;
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
        public function setImageType(string $imageType): void
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
        public function setLimit(int $limit): void
        {
            $this->_limit = max($limit, $this->_minPerPage);
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
    }
