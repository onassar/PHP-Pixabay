<?php

    /**
     * Pixabay
     * 
     * PHP wrapper for Pixabay
     * 
     * @author Oliver Nassar <onassar@gmail.com>
     * @see    https://github.com/onassar/PHP-Pixabay
     * @see    https://pixabay.com/api/docs/
     */
    class Pixabay
    {
        /**
         * _associative
         * 
         * @var    boolean
         * @access protected
         */
        protected $_associative;

        /**
         * _base
         * 
         * @var    string
         * @access protected
         */
        protected $_base = 'https://pixabay.com/api/';

        /**
         * _hd
         * 
         * @var    boolean (default: false)
         * @access protected
         */
        protected $_hd = false;

        /**
         * _key
         * 
         * @var    string
         * @access protected
         */
        protected $_key;

        /**
         * _minHeight
         * 
         * @var    string (default: '0')
         * @access protected
         */
        protected $_minHeight = '0';

        /**
         * _minWidth
         * 
         * @var    string (default: '0')
         * @access protected
         */
        protected $_minWidth = '0';

        /**
         * _order
         * 
         * @var    string (default: 'popular')
         * @access protected
         */
        protected $_order = 'popular';

        /**
         * _page
         * 
         * @var    string (default: '1')
         * @access protected
         */
        protected $_page = '1';

        /**
         * _photosPerPage
         * 
         * @var    string (default: '20')
         * @access protected
         */
        protected $_photosPerPage = '20';

        /**
         * _type
         * 
         * @var    string (default: 'photo')
         * @access protected
         */
        protected $_type = 'photo';

        /**
         * _username
         * 
         * @var    string
         * @access protected
         */
        protected $_username;

        /**
         * __construct
         * 
         * @access public
         * @param  string $username
         * @param  string $key
         * @param  boolean $associative (default: true)
         * @return void
         */
        public function __construct(
            $username,
            $key,
            $associative = true
        ) {
            $this->_username = $username;
            $this->_key = $key;
            $this->_associative = $associative;
        }

        /**
         * _get
         * 
         * @access protected
         * @param  array $args
         * @return false|array|stdClass
         */
        public function _get(array $args)
        {
            // Path to request
            $responseGroup = 'image_details';
            if ($this->_hd === true) {
                $responseGroup = 'high_resolution';
            }
            $args = array_merge(
                array(
                    'username' => $this->_username,
                    'key' => $this->_key,
                    'response_group' => $responseGroup,
                ),
                $args
            );
            $path = http_build_query($args);
            $url = ($this->_base) . '?' . ($path);

            // Stream (to ignore 400 errors)
            $opts = array(
                'http' => array(
                    'method' => 'GET',
                    'ignore_errors' => true
                )
            );

            // Make the request
            $context = stream_context_create($opts);
            $response = file_get_contents($url, false, $context);

            // Attempt request; fail with false if it bails
            json_decode($response);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_decode(
                    $response,
                    $this->_associative
                );
            }

            // Fail
            error_log($response);
            return false;
        }

        /**
         * id
         * 
         * @access public
         * @param  string $id
         * @return false|array|stdClass
         */
        public function id($id)
        {
            $args = array(
                'id' => $id
            );
            $response = $this->_get($args);
            if ($response === false) {
                return false;
            }
            return $this->_associative
                ? $response['hits'][0]
                : $response->hits[0];
        }

        /**
         * query
         * 
         * @access public
         * @param  string $query
         * @param  array $args (default: array())
         * @return false|array|stdClass
         */
        public function query($query, array $args = array())
        {
            $args = array_merge(
                array(
                    'q' => $query,
                    'order' => $this->_order,
                    'page' => $this->_page,
                    'per_page' => $this->_photosPerPage,
                    'image_type' => $this->_type,
                    'min_width' => $this->_minWidth,
                    'min_height' => $this->_minHeight
                ),
                $args
            );
            $response = $this->_get($args);
            if ($response === false) {
                return false;
            }
            return $response;
        }

        /**
         * setHD
         * 
         * @access public
         * @param  boolean $hd
         * @return void
         */
        public function setHD($hd)
        {
            $this->_hd = $hd;
        }

        /**
         * setMinHeight
         * 
         * @access public
         * @param  string $minHeight
         * @return void
         */
        public function setMinHeight($minHeight)
        {
            $this->_minHeight = $minHeight;
        }

        /**
         * setMinWidth
         * 
         * @access public
         * @param  string $minWidth
         * @return void
         */
        public function setMinWidth($minWidth)
        {
            $this->_minWidth = $minWidth;
        }

        /**
         * setOrder
         * 
         * @access public
         * @param  string $order
         * @return void
         */
        public function setOrder($order)
        {
            $this->_order = $order;
        }

        /**
         * setPage
         * 
         * @access public
         * @param  string $page
         * @return void
         */
        public function setPage($page)
        {
            $this->_page = $page;
        }

        /**
         * setPhotosPerPage
         * 
         * @access public
         * @param  string $photosPerPage
         * @return void
         */
        public function setPhotosPerPage($photosPerPage)
        {
            $this->_photosPerPage = $photosPerPage;
        }

        /**
         * setType
         * 
         * @access public
         * @param  string $type
         * @return void
         */
        public function setType($type)
        {
            $this->_type = $type;
        }
    }
