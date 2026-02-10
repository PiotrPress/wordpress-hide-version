<?php declare( strict_types = 1 );

/**
 * Plugin Name: Hide Version
 * Plugin URI: https://github.com/PiotrPress/wordpress-hide-version
 * Description: This plugin hides the WordPress version number by removing the generator meta tag and replacing ver query parameter from enqueued scripts and styles with the file modification time.
 * Version: 0.1.0
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Author: Piotr Niewiadomski
 * Author URI: https://piotr.press
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: piotrpress-hide-version
 * Domain Path: /languages
 * Update URI: false
 */

defined( 'ABSPATH' ) or exit;

is_admin() or add_action( 'init', function () {
    add_filter( 'the_generator', '__return_empty_string' );
    foreach( [ 'style', 'script' ] as $type )
        add_filter( $type . '_loader_src', function( string $src ) : string {
            if( ! $src ) return $src;
            if( ! preg_match( '#^(https?:)?//#', $src ) ) $url = rtrim( home_url( '/' ), '/' ) . '/' . ltrim( $src, '/' );
            else $url = $src;

            if( ! $host = wp_parse_url( $url, PHP_URL_HOST ) ) return $src;
            if( $host !== wp_parse_url( home_url(), PHP_URL_HOST ) ) return $src;

            if( ! $query = wp_parse_url( $url, PHP_URL_QUERY ) ) return $src;
            parse_str( $query, $args );
            if( ! array_key_exists('ver', $args ) ) return $src;

            if( ! $path = wp_parse_url( $url, PHP_URL_PATH ) ) return $src;
            $path = wp_normalize_path( $path );
            if( str_starts_with( $path, '/wp-content/' ) ) $file = wp_normalize_path( WP_CONTENT_DIR . substr( $path, strlen( '/wp-content' ) ) );
            else $file = wp_normalize_path( ABSPATH . ltrim( $path, '/' ) );

            return file_exists( $file ) ? add_query_arg( 'ver', @filemtime( $path ) ?: time(), remove_query_arg( 'ver', $src ) ) : $src;
        }, 999 );
} );