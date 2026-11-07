<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Blocks' ) ) {
	class MWTSA_Blocks {

		public static function init() {
			// The blocks need WordPress 5.6 or later. The shortcodes work on every version.
			// The other two are older, but Plugin Check only accepts a check for the function itself.
			if ( ! function_exists( 'get_block_wrapper_attributes' ) || ! function_exists( 'register_block_type_from_metadata' ) || ! function_exists( 'wp_set_script_translations' ) ) {
				return;
			}

			wp_register_script(
				'mwtsa-blocks-editor',
				MWTSAI()->plugin_url . 'blocks/editor.js',
				array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render', 'wp-shortcode' ),
				MWTSAI()->version,
				true
			);
			wp_set_script_translations( 'mwtsa-blocks-editor', 'search-analytics' );

			register_block_type_from_metadata( __DIR__ . '/popular-searches', array(
				'render_callback' => array( __CLASS__, 'render_popular_searches' ),
			) );

			register_block_type_from_metadata( __DIR__ . '/latest-searches', array(
				'render_callback' => array( __CLASS__, 'render_latest_searches' ),
			) );
		}

		public static function render_popular_searches( $attributes ) {
			return self::wrap( MWTSA_Display_Search_Stats_Shortcode::render( array(
				'unit'                            => $attributes['unit'],
				'amount'                          => $attributes['amount'],
				'most_searched'                   => $attributes['mostSearched'],
				'most_searched_count'             => $attributes['mostSearchedCount'],
				'most_searched_only_with_results' => $attributes['mostSearchedOnlyWithResults'],
				'most_searched_label'             => $attributes['mostSearchedLabel'],
				'user_searches'                   => $attributes['userSearches'],
				'user_searches_count'             => $attributes['userSearchesCount'],
				'user_searches_only_with_results' => $attributes['userSearchesOnlyWithResults'],
				'user_searches_label'             => $attributes['userSearchesLabel'],
				'link_terms'                      => $attributes['linkTerms'],
				'count_link_clicks'               => $attributes['countLinkClicks'],
				'show_label'                      => $attributes['showLabel'],
			) ) );
		}

		public static function render_latest_searches( $attributes ) {
			return self::wrap( MWTSA_Display_Latest_Searches_Shortcode::render( array(
				'unit'              => $attributes['unit'],
				'amount'            => $attributes['amount'],
				'count'             => $attributes['count'],
				'only_with_results' => $attributes['onlyWithResults'],
				'label'             => $attributes['label'],
				'link_terms'        => $attributes['linkTerms'],
				'count_link_clicks' => $attributes['countLinkClicks'],
				'show_label'        => $attributes['showLabel'],
			) ) );
		}

		// The shortcodes print an empty wrapper when there is nothing to list. The blocks print nothing.
		private static function wrap( $output ) {
			if ( '' === trim( wp_strip_all_tags( $output ) ) ) {
				return '';
			}

			return '<div ' . get_block_wrapper_attributes() . '>' . $output . '</div>';
		}
	}
}
