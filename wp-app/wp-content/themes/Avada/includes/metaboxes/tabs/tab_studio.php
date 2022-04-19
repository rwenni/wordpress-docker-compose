<?php
/**
 * Studio Metabox options.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 */

/**
 * Studio Page Settings.
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function avada_page_options_tab_studio( $sections ) {

	$sections['studio'] = [
		'label'    => esc_attr__( 'Studio', 'Avada' ),
		'id'       => 'studio',
		'alt_icon' => 'fusiona-footer',
		'fields'   => [
			'studio_replace_params' => [
				'id'          => 'studio_replace_params',
				'label'       => esc_html__( 'Replace Global Params', 'Avada' ),
				'choices'     => [
					'yes' => esc_attr__( 'Yes', 'Avada' ),
					'no'  => esc_attr__( 'No', 'Avada' ),
				],
				'description' => esc_html__( 'Choose to enable or disable element global params replacements.', 'Avada' ),
				'type'        => 'radio-buttonset',
				'map'         => 'yesno',
				'transport'   => 'postMessage',
				'default'     => 'yes',
			],
			'exclude_form_studio'   => [
				'id'          => 'exclude_form_studio',
				'label'       => esc_html__( 'Exclude from Studio', 'Avada' ),
				'choices'     => [
					'yes' => esc_attr__( 'Yes', 'Avada' ),
					'no'  => esc_attr__( 'No', 'Avada' ),
				],
				'description' => esc_html__( 'Choose to include or exclude this template from studio content.', 'Avada' ),
				'type'        => 'radio-buttonset',
				'map'         => 'yesno',
				'transport'   => 'postMessage',
				'default'     => 'no',
			],
		],
	];
	
	return $sections;
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
