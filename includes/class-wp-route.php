<?php
/**
 * WP_Route
 *
 * A simple class for binding
 * complex routes to functions
 * methods or WP_AJAX actions.
 *
 * @author     Anthony Budd
 */
final class WP_Route {

	private $hooked = false;

	/** @var array $reserved_names */
	protected $reserved_names;

	private $routes = array(
		'ANY'    => array(),
		'GET'    => array(),
		'POST'   => array(),
		'HEAD'   => array(),
		'PUT'    => array(),
		'DELETE' => array(),
	);

	private function __construct() {
		if ( ! function_exists( 'get_subdirectory_reserved_names' ) ) {
			require_once ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'ms-functions.php';
		}
		$this->reserved_names = get_subdirectory_reserved_names();
	}

	public static function instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new WP_Route();
			$instance->hook();
		}
		return $instance;
	}

	// -----------------------------------------------------
	// CREATE ROUTE METHODS
	// -----------------------------------------------------
	public static function any( $route, $callable ) {
		$r = self::instance();
		$r->add_route( 'ANY', $route, $callable );
	}

	public static function get( $route, $callable ) {
		$r = self::instance();
		$r->add_route( 'GET', $route, $callable );
	}

	public static function post( $route, $callable ) {
		$r = self::instance();
		$r->add_route( 'POST', $route, $callable );
	}

	public static function head( $route, $callable ) {
		$r = self::instance();
		$r->add_route( 'HEAD', $route, $callable );
	}

	public static function put( $route, $callable ) {
		$r = self::instance();
		$r->add_route( 'PUT', $route, $callable );
	}

	public static function delete( $route, $callable ) {
		$r = self::instance();
		$r->add_route( 'DELETE', $route, $callable );
	}

	public static function match( $methods, $route, $callable ) {
		if ( ! is_array( $methods ) ) {
			throw new Exception( "\$methods must be an array" );
		}
		$r = self::instance();
		foreach ( $methods as $method ) {
			if ( ! in_array( strtoupper( $method ), array_keys( self::$routes ), true ) ) {
				throw new Exception( "Unknown method {$method}" );
			}

			$r->add_route( strtoupper( $method ), $route, $callable );
		}
	}

	public static function redirect( $route, $redirect, $code = 301 ) {
		$r = self::instance();
		$r->add_route(
			'ANY',
			$route,
			$redirect,
			array(
				'code'     => $code,
				'redirect' => $redirect,
			)
		);
	}

	// -----------------------------------------------------
	// INTERNAL UTILITY METHODS
	// -----------------------------------------------------
	private function add_route( $method, $route, $callable, $options = array() ) {
		$base_path = explode( '?', $this->tokenize( $route )[0] )[0];
		if ( in_array( $base_path, $this->reserved_names, true ) ) {
			throw new Exception( 'Route contains reserved name' );
		}
		$this->routes[ $method ][] = (object) array_merge(
			array(
				'route'    => ltrim( $route, '/' ),
				'callable' => $callable,
			),
			$options
		);

	}

	private function hook() {
		if ( ! $this->hooked ) {
			add_action( 'wp_loaded', array( 'WP_Route', 'on_init' ), 1, 0 );
			$this->hooked = true;
		}
	}

	public static function on_init() {
		$r = self::instance();
		$r->handle();
	}

	private function get_route_params( $route ) {
		$tokenized_route      = $this->tokenize( $route );
		$tokenized_request_uri = $this->tokenize( $this->requestURI() );
		preg_match_all( '/\{\s*.+?\s*\}/', $route, $matches );
		$return = array();
		foreach ( $matches[0] as $match ) {
			$search = array_search( $match, $tokenized_route );
			if ( $search !== false ) {
				$return[]  = $tokenized_request_uri[ $search ];
			}
		}

		return $return;
	}

	// -----------------------------------------------------
	// GENERAL UTILITY METHODS
	// -----------------------------------------------------
	public static function routes() {
		$r = self::instance();
		return $r->routes;
	}

	public function tokenize($url) {
		return array_filter(explode('/', ltrim($url, '/')));
	}

	public function requestURI() {
		return ltrim( $_SERVER['REQUEST_URI'], '/' );
	}

	// -----------------------------------------------------
	// handle()
	// -----------------------------------------------------
	public function handle() {
		$method                = strtoupper( $_SERVER['REQUEST_METHOD'] );
		$routes                = is_array($this->routes[ $method ]) ? array_merge( $this->routes[ $method ], $this->routes['ANY'] ) : null;
		$request_uri           = $this->requestURI();
		$tokenized_request_uri = $this->tokenize( $request_uri );
		$request_uri_path      = explode( '?', $this->tokenize( $request_uri )[0] )[0];

		if ( is_array($routes) ) {
            foreach ( $routes as $key => $route ) {
                // First, filter routes that do not have equal tokenized lengths
                if ( count( $this->tokenize( $route->route ) ) !== count( $tokenized_request_uri ) ) {
                    unset( $routes[ $key ] );
                    continue;
                }
                if ( $this->tokenize( $route->route )[0] !== $request_uri_path ) {
                    unset( $routes[ $key ] );
                    continue;
                }

                // Add more filtering here as routing gets more complex.
            }
            $routes = array_values( $routes );
            if ( isset( $routes[0] ) ) {
                $route = $routes[0];
                if ( is_string( $route->callable ) &&
                   class_exists( $route->callable ) &&
                   is_subclass_of( $route->callable, 'WP_AJAX' ) ) {
                    $callable   = $route->callable;
                    $controller = new $callable();
                    call_user_func_array( array( $controller, 'boot' ), $this->get_route_params( $route->route ) );
                    exit;
                } elseif ( isset( $routes[0]->redirect ) ) {
                    $redirect = $routes[0]->redirect;
                    header( "Location: {$redirect}", true, $routes[0]->code );
                    wp_die();
                } else {
                    call_user_func_array( $route->callable, $this->get_route_params( $route->route ) );
                    exit;
                }
            }
        }
	}
}
