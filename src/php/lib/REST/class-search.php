<?php

namespace JITS\StringLocator\REST;

class Search extends Base {

	protected $rest_base = 'search';

	public function __construct() {
		parent::__construct();
	}

	public function register_rest_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'perform_search' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	public function perform_search( \WP_REST_Request $request ) {
		$handler = new \JITS\StringLocator\Search();

		return array(
			'success' => true,
			'data'    => $handler->run( $request->get_param( 'filenum' ) )
		);
	}

}

new Search();
