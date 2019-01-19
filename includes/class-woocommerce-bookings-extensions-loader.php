<?php

/**
 * Register all actions and filters for the plugin
 *
 * @since      1.0.0
 *
 * @package    Woocommerce_Bookings_Extensions
 * @subpackage Woocommerce_Bookings_Extensions/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Woocommerce_Bookings_Extensions
 * @subpackage Woocommerce_Bookings_Extensions/includes
 * @author     Marius Bezuidenhout <marius.bezuidenhout@gmail.com>
 */
class Woocommerce_Bookings_Extensions_Loader {

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
	 */
	protected $actions;

	/**
	 * The array of actions to remove from WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $remove_actions   The actions to remove from WordPress to fire when the plugin loads.
	 */
	protected $remove_actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
	 */
	protected $filters;

	/**
	 * The array of filters to remove from WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $remove_filters   The filters to remove from WordPress to fire when the plugin loads.
	 */
	protected $remove_filters;

	/**
	 * The array of filters to remove from WordPress.
	 *
	 * @since    1.2.0
	 * @access   protected
	 * @var      array    $shortcodes   Shortcodes provided by this plugin
	 */
	protected $shortcodes;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->actions        = array();
		$this->filters        = array();
		$this->remove_actions = array();
		$this->remove_filters = array();
		$this->shortcodes     = array();
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since    1.0.0
	 * @param    string               $hook             The name of the WordPress action that is being registered.
	 * @param    object               $component        A reference to the instance of the object on which the action is defined.
	 * @param    string               $callback         The name of the function definition on the $component.
	 * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
	 * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	public function add_shortcode( $hook, $component, $callback ) {
		$this->shortcodes = $this->add( $this->shortcodes, $hook, $component, $callback );
	}

	public function remove_action( $hook, $component, $callback, $priority = 10 ) {
		$this->remove_actions = $this->add( $this->remove_actions, $hook, $component, $callback, $priority );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @since    1.0.0
	 * @param    string               $hook             The name of the WordPress filter that is being registered.
	 * @param    object               $component        A reference to the instance of the object on which the filter is defined.
	 * @param    string               $callback         The name of the function definition on the $component.
	 * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
	 * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	public function remove_filter( $hook, $component, $callback, $priority = 10 ) {
		$this->remove_filters = $this->add( $this->remove_filters, $hook, $component, $callback, $priority );
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single
	 * collection.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array                $hooks            The collection of hooks that is being registered (that is, actions or filters).
	 * @param    string               $hook             The name of the WordPress filter that is being registered.
	 * @param    object               $component        A reference to the instance of the object on which the filter is defined.
	 * @param    string               $callback         The name of the function definition on the $component.
	 * @param    int|null             $priority         The priority at which the function should be fired.
	 * @param    int|null             $accepted_args    The number of arguments that should be passed to the $callback.
	 * @return   array                                  The collection of actions and filters registered with WordPress.
	 */
	private function add( $hooks, $hook, $component, $callback, $priority = null, $accepted_args = null ) {
		$entity = array(
			'hook'      => $hook,
			'component' => $component,
			'callback'  => $callback,
		);

		if ( null !== $priority ) {
			$entity['priority'] = $priority;
		}
		if ( null !== $accepted_args ) {
			$entity['accepted_args'] = $accepted_args;
		}

		$hooks[] = $entity;

		return $hooks;

	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {

		foreach ( $this->remove_actions as $hook ) {
			remove_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'] );
		}

		foreach ( $this->remove_filters as $hook ) {
			remove_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'] );
		}

		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->shortcodes as $hook ) {
			add_shortcode( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
		}

	}

}
