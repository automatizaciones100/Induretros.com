<?php

/**
 * REST API controller class for the product quickview.
 */
class Kadence_Shop_Kit_Quickview_Rest_Controller extends WP_REST_Controller {
	/**
	 * query loop post id property name.
	 */
	const PROP_ID = 'product_id';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'kwqv-content/v1';
		$this->quickview_base = 'quickview';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		// parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->quickview_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'quickview' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_query_params(),
				),
			)
		);
	}

    /**
	 * Gets the html content for a product quickview.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response
	 */
	public function quickview( $request ) {
        $data = array( 'success' => false );

		$product_id = $request->get_param( self::PROP_ID );

        if( $product_id ) {
            $qvrc = Kadence_Shop_Kit_Quickview::get_instance();
            $data['success'] = true;
            $data['html'] = $qvrc->get_the_quickview($product_id);
        }

		return rest_ensure_response( $data );
	}

	/**
	 * Retrieves the query params for the search results collection.
	 *
	 * @return array Collection parameters.
	 */
	public function get_query_params() {
		$query_params  = parent::get_collection_params();
		$query_params[ self::PROP_ID ] = array(
			'description' => __( 'The product post id.', 'kadence-woo-extras' ),
			'type'        => 'integer',
			'default'     => 0,
		);
		return $query_params;
	}
}