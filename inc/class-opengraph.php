<?php

namespace FeelingRESTful;

class OpenGraph {

	public function __construct() {

		add_action( 'opengraph', array( $this, 'opengraph' ) );

		add_filter( 'opengraph_tags', array( $this, 'pagebuilder_tags' ), 10, 2 );

	}

	public function opengraph() {

		$tags = [ ];

		// Get page ID for page currently being displayed
		global $wpdb;
		$postid = '';
		$posttype = '';
		$path = explode('/', $_SERVER['REQUEST_URI'] );
		if( count($path) >= 3 ) {
			$postname = $path[2];
			$posttype = $path[1];
			if ( is_numeric( $postname ) ) { // Speaker slugs are the post ID
				$postid = $postname;
			} else {
				$post   = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name='%s' AND post_status='publish';", $postname ) );
				if ($post) {
		        	$postid = $post[0];
		    	}
			}
	    }

		if ( $postid ) {

		 	if ( get_the_title( $postid ) ) {
		 		$tags['og:title'] = get_the_title( $postid );
		 	}

		 	if ( 'speakers' === $posttype ) { // No excerpt for speakers
			 	$content = get_post_field( 'post_content', $postid );
				if ( $content ) {
					$tags['og:description'] = strip_tags( $content );
				}
		 	} else {
			 	$excerpt = get_post_field( 'post_excerpt', $postid );
				if ( $excerpt ) {
					$tags['og:description'] = apply_filters( 'get_the_excerpt', $excerpt );
				}
			}

			if ( get_the_permalink( $postid ) ) {
				$tags['og:url'] = get_the_permalink( $postid );
			}

			if ( get_the_post_thumbnail_url( $postid, 'full' ) ) {
				$tags['og:image'] = get_the_post_thumbnail_url( $postid, 'full' );
			}

		}

		$tags = apply_filters( 'opengraph_tags', $tags, $postid );

		$tags = wp_parse_args( $tags, [
			'og:type'        => 'website',
			'og:title'       => get_bloginfo( 'name' ),
			'og:description' => get_bloginfo( 'description' ),
			'og:url'         => home_url( '/' ),
			'og:image'       => get_site_icon_url(),
		] );

		$tags = array_filter( $tags );

		foreach ( $tags as $property => $content ) {
			printf( '
			<meta property="%s" content="%s">',
				esc_attr( $property ),
				esc_attr( $content )
			);
		}

	}

	public function pagebuilder_tags( $tags, $postid ) {

		// Page Builder stuff
		if ( class_exists( 'ModularPageBuilder\\Plugin' ) && $postid ) {

			$mpb     = \ModularPageBuilder\Plugin::get_instance();
			$builder = $mpb->get_builder( 'modular-page-builder' );
			$html    = $builder->get_rendered_data( $postid, $builder->id . '-data' );

			if ( empty( $tags['og:description'] ) && $html ) {
				$tags['og:description'] = trim( strip_tags( $html ) );
			}

			foreach ( $builder->get_raw_data( $postid ) as $module_args ) {
				if ( $module = $mpb->init_module( $module_args['name'], $module_args ) ) {
					if ( 'image' === $module_args['name'] && ! has_post_thumbnail() ) {
						$tags['og:image'] = $module->get_json()['image'][0][0];
						break;
					}
				}
			}

		}

		return $tags;
	}

}