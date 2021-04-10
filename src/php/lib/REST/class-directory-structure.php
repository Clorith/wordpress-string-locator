<?php

namespace JITS\StringLocator\REST;

use JITS\StringLocator\Directory_Iterator;

class Directory_Structure extends Base {

	protected $rest_base = 'get-directory-structure';

	public function __construct() {
		parent::__construct();
	}

	public function register_rest_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_structure' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	public function get_structure( \WP_REST_Request $request ) {
		$iterator = new Directory_Iterator(
			$request->get_param( 'directory' ),
			$request->get_param( 'search' ),
			$request->get_param( 'regex' )
		);

		return array(
			'success' => true,
			'data'    => $iterator->get_structure(),
		);
	}

}

new Directory_Structure();
