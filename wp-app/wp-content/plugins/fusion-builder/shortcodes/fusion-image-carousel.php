<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( fusion_is_element_enabled( 'fusion_images' ) ) {

	if ( ! class_exists( 'FusionSC_ImageCarousel' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 1.0
		 */
		class FusionSC_ImageCarousel extends Fusion_Element {

			/**
			 * Image Carousels counter.
			 *
			 * @access private
			 * @since 1.0
			 * @var int
			 */
			private $image_carousel_counter = 1;

			/**
			 * Total number of images.
			 *
			 * @access private
			 * @since 1.8
			 * @var int
			 */
			private $number_of_images = 1;

			/**
			 * The image data.
			 *
			 * @access private
			 * @since 1.0
			 * @var false|array
			 */
			private $image_data = false;

			/**
			 * Parent SC arguments.
			 *
			 * @access protected
			 * @since 1.0
			 * @var array
			 */
			protected $parent_args;

			/**
			 * Child SC arguments.
			 *
			 * @access protected
			 * @since 1.0
			 * @var array
			 */
			protected $child_args;

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 1.0
			 */
			public function __construct() {
				parent::__construct();
				add_filter( 'fusion_attr_image-carousel-shortcode', [ $this, 'attr' ] );
				add_filter( 'fusion_attr_image-carousel-shortcode-carousel', [ $this, 'carousel_attr' ] );
				add_filter( 'fusion_attr_image-carousel-shortcode-slide-link', [ $this, 'slide_link_attr' ] );
				add_filter( 'fusion_attr_fusion-image-wrapper', [ $this, 'image_wrapper' ] );
				add_filter( 'fusion_attr_image-carousel-shortcode-caption', [ $this, 'caption_attr' ] );

				add_shortcode( 'fusion_images', [ $this, 'render_parent' ] );
				add_shortcode( 'fusion_image', [ $this, 'render_child' ] );

				add_shortcode( 'fusion_clients', [ $this, 'render_parent' ] );
				add_shortcode( 'fusion_client', [ $this, 'render_child' ] );

				// Ajax mechanism for query related part.
				add_action( 'wp_ajax_get_fusion_image_carousel', [ $this, 'ajax_query_single_child' ] );

				add_action( 'wp_ajax_get_fusion_image_carousel_children_data', [ $this, 'query_children' ] );

			}

			/**
			 * Gets the query data.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return void
			 */
			public function ajax_query_single_child() {
				check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );
				$this->query_single_child();
			}

			/**
			 * Gets the query data for single children.
			 *
			 * @access public
			 * @since 2.0.0
			 */
			public function query_single_child() {

				// From Ajax Request.
				if ( isset( $_POST['model'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$defaults = [
						'image_id' => '',
					];
					if ( isset( $_POST['model']['params'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$defaults = $_POST['model']['params']; // phpcs:ignore WordPress.Security
					}

					$return_data['image_data'] = fusion_library()->images->get_attachment_data_by_helper( $defaults['image_id'] );

					$image_sizes = [ 'full', 'portfolio-two', 'blog-medium' ];
					foreach ( $image_sizes as $image_size ) {
						$return_data[ $return_data['image_data']['url'] ][ $image_size ] = wp_get_attachment_image( $return_data['image_data']['id'], $image_size );
						$return_data[ $return_data['image_data']['url'] ]['image_data']  = $return_data['image_data'];
					}
					echo wp_json_encode( $return_data );
				}
				wp_die();
			}

			/**
			 * Gets the query data for all children.
			 *
			 * @access public
			 * @since 2.0.0
			 */
			public function query_children() {

				check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );

				$return_data = [];

				// From Ajax Request.
				if ( isset( $_POST['children'] ) ) {
					$children    = $_POST['children']; // phpcs:ignore WordPress.Security
					$image_sizes = [ 'full', 'portfolio-two', 'blog-medium' ];

					foreach ( $children as $cid => $image_data ) {
						if ( isset( $children[ $cid ]['image_id'] ) && $children[ $cid ]['image_id'] ) {
							$image_id   = explode( '|', $children[ $cid ]['image_id'] );
							$image_id   = $image_id[0];
							$image_data = fusion_library()->get_images_obj()->get_attachment_data_by_helper( $children[ $cid ]['image_id'], $children[ $cid ]['image'] );
						} else {
							$image_data = fusion_library()->images->get_attachment_data_by_helper( '', $children[ $cid ]['image'] );
							$image_id   = $image_data['id'];
						}

						foreach ( $image_sizes as $image_size ) {
							$return_data[ $children[ $cid ]['image'] ][ $image_size ] = wp_get_attachment_image( $image_id, $image_size );
							$return_data[ $children[ $cid ]['image'] ]['image_data']  = $image_data;
						}
					}

					echo wp_json_encode( $return_data );
				}
				wp_die();
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @param string $context Whether we want parent or child.
			 * @return array
			 */
			public static function get_element_defaults( $context ) {
				$fusion_settings = awb_get_fusion_settings();

				$parent = [
					'hide_on_mobile'                       => fusion_builder_default_visibility( 'string' ),
					'class'                                => '',
					'id'                                   => '',
					'autoplay'                             => 'no',
					'border'                               => 'yes',
					'columns'                              => '5',
					'column_spacing'                       => '13',
					'image_id'                             => '',
					'lightbox'                             => 'no',
					'mouse_scroll'                         => 'no',
					'picture_size'                         => 'fixed',
					'scroll_items'                         => '',
					'show_nav'                             => 'yes',
					'hover_type'                           => 'none',

					// Caption params.
					'caption_style'                        => 'off',
					'caption_title_color'                  => '',
					'caption_title_size'                   => '',
					'caption_title_tag'                    => '2',
					'fusion_font_family_caption_title_font' => '',
					'fusion_font_variant_caption_title_font' => '',
					'caption_text_color'                   => '',
					'caption_text_size'                    => '',
					'fusion_font_family_caption_text_font' => '',
					'fusion_font_variant_caption_text_font' => '',
					'caption_border_color'                 => '',
					'caption_overlay_color'                => $fusion_settings->get( 'primary_color' ),
					'caption_background_color'             => '',
					'caption_margin_top'                   => '',
					'caption_margin_right'                 => '',
					'caption_margin_bottom'                => '',
					'caption_margin_left'                  => '',
					'caption_title_transform'              => '',
					'caption_text_transform'               => '',
					'caption_align'                        => 'none',
					'caption_align_medium'                 => 'none',
					'caption_align_small'                  => 'none',
				];

				$child = [
					'alt'           => '',
					'image'         => '',
					'image_id'      => '',
					'image_title'   => '',
					'image_caption' => '',
					'link'          => '',
					'linktarget'    => '_self',
				];

				if ( 'parent' === $context ) {
					return $parent;
				} elseif ( 'child' === $context ) {
					return $child;
				}
			}

			/**
			 * Render the parent shortcode.
			 *
			 * @access public
			 * @since 1.0
			 * @param  array  $args    Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string          HTML output.
			 */
			public function render_parent( $args, $content = '' ) {
				$this->defaults = self::get_element_defaults( 'parent' );
				$defaults       = FusionBuilder::set_shortcode_defaults( $this->defaults, $args, 'fusion_images' );
				$content        = apply_filters( 'fusion_shortcode_content', $content, 'fusion_images', $args );

				$defaults['column_spacing'] = FusionBuilder::validate_shortcode_attr_value( $defaults['column_spacing'], '' );

				extract( $defaults );

				$this->parent_args = $this->args = $defaults;

				preg_match_all( '/\[fusion_image (.*?)\]/s', $content, $matches );

				preg_match_all( '/\[fusion_image (.*?)\]/s', $content, $matches );

				if ( isset( $matches[0] ) ) {
					$this->number_of_images = count( $matches[0] );
				}

				$html  = '<div ' . FusionBuilder::attributes( 'image-carousel-shortcode' ) . '>';
				$html .= '<div ' . FusionBuilder::attributes( 'image-carousel-shortcode-carousel' ) . '>';
				$html .= '<div ' . FusionBuilder::attributes( 'fusion-carousel-positioner' ) . '>';

				// The main carousel.
				$html .= '<ul ' . FusionBuilder::attributes( 'fusion-carousel-holder' ) . '>';
				$html .= do_shortcode( $content );
				$html .= '</ul>';

				// Check if navigation should be shown.
				if ( 'yes' === $show_nav ) {
					$html .= '<div ' . FusionBuilder::attributes( 'fusion-carousel-nav' ) . '>';
					$html .= '<span ' . FusionBuilder::attributes( 'fusion-nav-prev' ) . '></span>';
					$html .= '<span ' . FusionBuilder::attributes( 'fusion-nav-next' ) . '></span>';
					$html .= '</div>';
				}
				$html .= '</div>';
				$html .= '</div>';
				$html .= '</div>';

				// Generate caption styles.
				$styles = $this->generate_caption_styles();
				if ( '' !== $styles ) {
					$html .= '<style type="text/css">' . $styles . '</style>';
				}

				$this->image_carousel_counter++;

				$this->on_render();

				return apply_filters( 'fusion_element_image_carousel_parent_content', $html, $args );

			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function attr() {

				$attr = fusion_builder_visibility_atts(
					$this->parent_args['hide_on_mobile'],
					[
						'class' => 'fusion-image-carousel fusion-image-carousel-' . $this->parent_args['picture_size'] . ' fusion-image-carousel-' . $this->image_carousel_counter,
					]
				);

				if ( 'yes' === $this->parent_args['lightbox'] ) {
					$attr['class'] .= ' lightbox-enabled';
				}

				if ( 'yes' === $this->parent_args['border'] ) {
					$attr['class'] .= ' fusion-carousel-border';
				}

				if ( in_array( $this->parent_args['caption_style'], [ 'above', 'below' ], true ) ) {
					$attr['class'] .= ' awb-image-carousel-top-below-caption';
				}

				if ( $this->parent_args['class'] ) {
					$attr['class'] .= ' ' . $this->parent_args['class'];
				}

				if ( $this->parent_args['id'] ) {
					$attr['id'] = $this->parent_args['id'];
				}

				return $attr;

			}

			/**
			 * Builds the carousel attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function carousel_attr() {

				$attr['class']            = 'fusion-carousel';
				$attr['data-autoplay']    = $this->parent_args['autoplay'];
				$attr['data-columns']     = $this->parent_args['columns'];
				$attr['data-itemmargin']  = $this->parent_args['column_spacing'];
				$attr['data-itemwidth']   = 180;
				$attr['data-touchscroll'] = $this->parent_args['mouse_scroll'];
				$attr['data-imagesize']   = $this->parent_args['picture_size'];
				$attr['data-scrollitems'] = $this->parent_args['scroll_items'];

				// Caption style.
				if ( in_array( $this->parent_args['caption_style'], [ 'above', 'below' ], true ) ) {
					$attr['class'] .= ' awb-imageframe-style awb-imageframe-style-' . $this->parent_args['caption_style'] . ' awb-imageframe-style-' . $this->image_carousel_counter;
				}
				return $attr;

			}

			/**
			 * Render the child shortcode.
			 *
			 * @access public
			 * @since 1.0
			 * @param  array  $args   Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string         HTML output.
			 */
			public function render_child( $args, $content = '' ) {

				$defaults = FusionBuilder::set_shortcode_defaults( self::get_element_defaults( 'child' ), $args, 'fusion_image' );
				$content  = apply_filters( 'fusion_shortcode_content', $content, 'fusion_image', $args );

				extract( $defaults );

				$this->child_args = $defaults;

				$width = $height = '';

				$image_size = 'full';
				if ( 'fixed' === $this->parent_args['picture_size'] ) {
					$image_size = 'portfolio-two';
					if ( '6' === $this->parent_args['columns'] || '5' === $this->parent_args['columns'] || '4' === $this->parent_args['columns'] ) {
						$image_size = 'blog-medium';
					}
				}

				$this->image_data = fusion_library()->images->get_attachment_data_by_helper( $this->child_args['image_id'], $image );

				$output = '';
				if ( $this->image_data && $this->image_data['id'] ) {

					// Responsive images.
					$number_of_columns = ( $this->number_of_images < $this->parent_args['columns'] ) ? $this->number_of_images : $this->parent_args['columns'];

					if ( 1 < $number_of_columns || 'full' !== $image_size ) {
						fusion_library()->images->set_grid_image_meta(
							[
								'layout'       => 'grid',
								'columns'      => $number_of_columns,
								'gutter_width' => $this->parent_args['column_spacing'],
							]
						);
					}

					if ( $alt ) {
						$output = wp_get_attachment_image( $this->image_data['id'], $image_size, false, [ 'alt' => $alt ] );
					} else {
						$output = wp_get_attachment_image( $this->image_data['id'], $image_size );
					}

					if ( 'full' === $image_size ) {
						$output = fusion_library()->images->edit_grid_image_src( $output, null, $this->image_data['id'], 'full' );
					}

					fusion_library()->images->set_grid_image_meta( [] );

				} else {
					$output = '<img src="' . $image . '" alt="' . $alt . '"/>';
				}

				if ( ! empty( $this->image_data ) ) {
					$output = fusion_library()->images->apply_lazy_loading( $output, null, $this->image_data['id'], 'full' );
				}

				// render caption markup.
				if ( ! in_array( $this->parent_args['caption_style'], [ 'off', 'above', 'below' ], true ) ) {
					$output .= $this->render_caption();
				}

				if ( 'no' === $this->parent_args['mouse_scroll'] && ( $link || 'yes' === $this->parent_args['lightbox'] ) ) {
					$output = '<a ' . FusionBuilder::attributes( 'image-carousel-shortcode-slide-link' ) . '>' . $output . '</a>';
				}

				$li = '<li ' . FusionBuilder::attributes( 'fusion-carousel-item' ) . '><div ' . FusionBuilder::attributes( 'fusion-carousel-item-wrapper' ) . '>';
				if ( 'above' === $this->parent_args['caption_style'] ) {
					$li .= $this->render_caption();
				}
				$li .= '<div ' . FusionBuilder::attributes( 'fusion-image-wrapper' ) . '>' . $output . '</div>';
				if ( 'below' === $this->parent_args['caption_style'] ) {
					$li .= $this->render_caption();
				}
				$li .= '</div></li>';

				return apply_filters( 'fusion_element_image_carousel_child_content', $li, $args );
			}

			/**
			 * Builds the slide-link attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function slide_link_attr() {

				$attr = [];

				if ( 'yes' === $this->parent_args['lightbox'] ) {

					if ( ! $this->child_args['link'] ) {
						$this->child_args['link'] = $this->child_args['image'];
					}

					$attr['data-rel'] = 'iLightbox[image_carousel_' . $this->image_carousel_counter . ']';

					if ( $this->image_data ) {
						$attr['data-caption'] = $this->image_data['caption'];
						$attr['data-title']   = $this->image_data['title'];
						$attr['aria-label']   = $this->image_data['title'];
					}
				}

				$attr['href'] = $this->child_args['link'];

				$attr['target'] = $this->child_args['linktarget'];
				if ( '_blank' === $this->child_args['linktarget'] ) {
					$attr['rel'] = 'noopener noreferrer';
				}
				return $attr;

			}

			/**
			 * Builds the caption attributes array.
			 *
			 * @access public
			 * @since 3.5
			 * @return array
			 */
			public function caption_attr() {

				$attr = [
					'class' => 'awb-imageframe-caption-container',
					'style' => '',
				];

				if ( ! fusion_element_rendering_is_flex() ) {
					return $attr;
				}

				if ( in_array( $this->args['caption_style'], [ 'above', 'below' ], true ) ) {
					// Responsive alignment.
					foreach ( [ 'large', 'medium', 'small' ] as $size ) {
						$key = 'caption_align' . ( 'large' === $size ? '' : '_' . $size );

						$align = ! empty( $this->args[ $key ] ) && 'none' !== $this->args[ $key ] ? $this->args[ $key ] : false;
						if ( $align ) {
							if ( 'large' === $size ) {
								$attr['style'] .= 'text-align:' . $this->args[ $key ] . ';';
							} else {
								$attr['class'] .= ( 'medium' === $size ? ' md-text-align-' : ' sm-text-align-' ) . $this->args[ $key ];
							}
						}
					}
				}

				return $attr;
			}

			/**
			 * Builds the image-wrapper attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function image_wrapper() {
				$attr = [
					'class' => 'fusion-image-wrapper',
				];
				if ( $this->parent_args['hover_type'] && in_array( $this->parent_args['caption_style'], [ 'off', 'above', 'below' ], true ) ) {
					$attr['class'] .= ' hover-type-' . $this->parent_args['hover_type'];
				}

				// Caption style.
				if ( ! in_array( $this->parent_args['caption_style'], [ 'off', 'above', 'below' ], true ) ) {
					$attr['class'] .= ' awb-imageframe-style awb-imageframe-style-' . $this->parent_args['caption_style'];
				}
				return $attr;
			}

			/**
			 * Render the caption.
			 *
			 * @access public
			 * @since 3.5
			 * @return string HTML output.
			 */
			public function render_caption() {
				if ( 'off' === $this->parent_args['caption_style'] ) {
					return '';
				}
				$output  = '<div ' . FusionBuilder::attributes( 'image-carousel-shortcode-caption' ) . '><div class="awb-imageframe-caption">';
				$title   = '';
				$caption = '';

				if ( $this->image_data ) {
					if ( '' !== $this->image_data['title'] ) {
						$title = $this->image_data['title'];
					}
					if ( '' !== $this->image_data['caption'] ) {
						$caption = $this->image_data['caption'];
					}
				}

				if ( '' !== $this->child_args['image_title'] ) {
					$title = $this->child_args['image_title'];
				}
				if ( '' !== $this->child_args['image_caption'] ) {
					$caption = $this->child_args['image_caption'];
				}

				if ( '' !== $title ) {
					$title_tag = 'div' === $this->parent_args['caption_title_tag'] ? 'div' : 'h' . $this->parent_args['caption_title_tag'];
					$output   .= sprintf( '<%1$s class="awb-imageframe-caption-title">%2$s</%1$s>', $title_tag, $title );
				}
				if ( '' !== $caption ) {
					$output .= sprintf( '<p class="awb-imageframe-caption-text">%1$s</p>', $caption );
				}
				$output .= '</div></div>';
				return $output;
			}

			/**
			 * Generate caption styles.
			 *
			 * @access public
			 * @since 3.5
			 * @return string CSS output.
			 */
			public function generate_caption_styles() {
				if ( 'off' === $this->parent_args['caption_style'] ) {
					return '';
				}
				$this->dynamic_css   = [];
				$this->base_selector = '.fusion-image-carousel.fusion-image-carousel-' . $this->image_carousel_counter;
				if ( in_array( $this->parent_args['caption_style'], [ 'above', 'below' ], true ) ) {
					$this->base_selector = '.awb-imageframe-style.awb-imageframe-style-' . $this->image_carousel_counter;
				}

				$selectors = [
					$this->base_selector . ' .awb-imageframe-caption-container .awb-imageframe-caption-title',
				];
				// title color.
				if ( ! $this->is_default( 'caption_title_color' ) ) {
					$this->add_css_property( $selectors, 'color', $this->parent_args['caption_title_color'] );
				}
				// title size.
				if ( ! $this->is_default( 'caption_title_size' ) ) {
					$this->add_css_property( $selectors, 'font-size', fusion_library()->sanitize->get_value_with_unit( $this->parent_args['caption_title_size'] ), true );
				}
				// title font.
				$font_styles = Fusion_Builder_Element_Helper::get_font_styling( $this->parent_args, 'caption_title_font', 'array' );

				foreach ( $font_styles as $rule => $value ) {
					$this->add_css_property( $selectors, $rule, $value, true );
				}
				// title transform.
				if ( ! $this->is_default( 'caption_title_transform' ) ) {
					$this->add_css_property( $selectors, 'text-transform', $this->parent_args['caption_title_transform'] );
				}

				$selectors = [
					$this->base_selector . ' .awb-imageframe-caption-container .awb-imageframe-caption-text',
				];
				// text color.
				if ( ! $this->is_default( 'caption_text_color' ) ) {
					$this->add_css_property( $selectors, 'color', $this->parent_args['caption_text_color'] );
				}
				// text size.
				if ( ! $this->is_default( 'caption_text_size' ) ) {
					$this->add_css_property( $selectors, 'font-size', fusion_library()->sanitize->get_value_with_unit( $this->parent_args['caption_text_size'] ) );
				}
				// text font.
				$font_styles = Fusion_Builder_Element_Helper::get_font_styling( $this->parent_args, 'caption_text_font', 'array' );

				foreach ( $font_styles as $rule => $value ) {
					$this->add_css_property( $selectors, $rule, $value );
				}
				// text transform.
				if ( ! $this->is_default( 'caption_text_transform' ) ) {
					$this->add_css_property( $selectors, 'text-transform', $this->parent_args['caption_text_transform'] );
				}

				// Border color.
				if ( 'resa' === $this->parent_args['caption_style'] && ! $this->is_default( 'caption_border_color' ) ) {
					$selectors = [
						$this->base_selector . ' .awb-imageframe-caption-container:before',
					];
					$this->add_css_property( $selectors, 'border-top-color', $this->parent_args['caption_border_color'] );
					$this->add_css_property( $selectors, 'border-bottom-color', $this->parent_args['caption_border_color'] );
					$selectors = [
						$this->base_selector . ' .awb-imageframe-caption-container:after',
					];
					$this->add_css_property( $selectors, 'border-right-color', $this->parent_args['caption_border_color'] );
					$this->add_css_property( $selectors, 'border-left-color', $this->parent_args['caption_border_color'] );
				}

				if ( 'dario' === $this->parent_args['caption_style'] && ! $this->is_default( 'caption_border_color' ) ) {
					$selectors = [
						$this->base_selector . ' .awb-imageframe-caption .awb-imageframe-caption-title:after',
					];
					$this->add_css_property( $selectors, 'background', $this->parent_args['caption_border_color'] );
				}

				// Overlay color.
				if ( in_array( $this->parent_args['caption_style'], [ 'dario', 'resa', 'schantel', 'dany', 'navin' ], true ) ) {
					$selectors = [
						$this->base_selector . ' .awb-imageframe-style',
					];
					$this->add_css_property( $selectors, 'background', $this->parent_args['caption_overlay_color'] );
				}

				// Background color.
				if ( in_array( $this->parent_args['caption_style'], [ 'schantel', 'dany' ], true ) && ! $this->is_default( 'caption_background_color' ) ) {
					$selectors = [
						$this->base_selector . ' .awb-imageframe-caption-container .awb-imageframe-caption-text',
					];
					$this->add_css_property( $selectors, 'background', $this->parent_args['caption_background_color'] );
				}

				// Caption area margin.
				if ( in_array( $this->parent_args['caption_style'], [ 'above', 'below' ], true ) ) {
					$sides     = [ 'top', 'right', 'bottom', 'left' ];
					$selectors = [
						$this->base_selector . ' .awb-imageframe-caption-container',
					];

					foreach ( $sides as $side ) {
						// Element margin.
						$margin_name = 'caption_margin_' . $side;

						if ( ! $this->is_default( $margin_name ) ) {
							$this->add_css_property( $selectors, 'margin-' . $side, fusion_library()->sanitize->get_value_with_unit( $this->parent_args[ $margin_name ] ) );
						}
					}

					if ( ! $this->is_default( 'caption_title' ) ) {
						$selectors = [
							$this->base_selector . ' .awb-imageframe-caption-container .awb-imageframe-caption-text',
						];
						$this->add_css_property( $selectors, 'margin-top', '0.5em' );
					}
				}

				return $this->parse_css();
			}

			/**
			 * Builds the "previous" nav attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function fusion_nav_prev() {
				return [
					'class' => 'fusion-nav-prev awb-icon-left',
				];
			}

			/**
			 * Builds the "next" nav attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function fusion_nav_next() {
				return [
					'class' => 'fusion-nav-next awb-icon-right',
				];
			}

			/**
			 * Sets the necessary scripts.
			 *
			 * @access public
			 * @since 3.2
			 * @return void
			 */
			public function on_first_render() {
				Fusion_Dynamic_JS::enqueue_script( 'fusion-lightbox' );
				Fusion_Dynamic_JS::enqueue_script( 'fusion-carousel' );
			}

			/**
			 * Used to set any other variables for use on front-end editor template.
			 *
			 * @static
			 * @access public
			 * @since 3.5
			 * @return array
			 */
			public static function get_element_extras() {
				$fusion_settings = awb_get_fusion_settings();
				return [
					'visibility_large'  => $fusion_settings->get( 'visibility_large' ),
					'visibility_medium' => $fusion_settings->get( 'visibility_medium' ),
					'visibility_small'  => $fusion_settings->get( 'visibility_small' ),
				];
			}

			/**
			 * Load base CSS.
			 *
			 * @access public
			 * @since 3.0
			 * @return void
			 */
			public function add_css_files() {
				FusionBuilder()->add_element_css( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/shortcodes/image-carousel.min.css' );
			}
		}
	}

	new FusionSC_ImageCarousel();

}

/**
 * Map shortcode to Avada Builder.
 */
function fusion_element_images() {
	$fusion_settings = awb_get_fusion_settings();

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_ImageCarousel',
			[
				'name'          => esc_attr__( 'Image Carousel', 'fusion-builder' ),
				'shortcode'     => 'fusion_images',
				'multi'         => 'multi_element_parent',
				'element_child' => 'fusion_image',
				'icon'          => 'fusiona-images',
				'preview'       => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-image-carousel-preview.php',
				'preview_id'    => 'fusion-builder-block-module-image-carousel-preview-template',
				'child_ui'      => true,
				'sortable'      => false,
				'help_url'      => 'https://theme-fusion.com/documentation/fusion-builder/elements/image-carousel-element/',
				'params'        => [
					[
						'type'        => 'tinymce',
						'heading'     => esc_attr__( 'Content', 'fusion-builder' ),
						'description' => esc_attr__( 'Enter some content for this image carousel.', 'fusion-builder' ),
						'param_name'  => 'element_content',
						'value'       => '[fusion_image link="" linktarget="_self" alt="" image_id="" /]',
					],
					[
						'type'             => 'multiple_upload',
						'heading'          => esc_attr__( 'Bulk Image Upload', 'fusion-builder' ),
						'description'      => __( 'This option allows you to select multiple images at once and they will populate into individual items. It saves time instead of adding one image at a time.', 'fusion-builder' ),
						'param_name'       => 'multiple_upload',
						'dynamic_data'     => true,
						'child_params'     => [
							'image'    => 'url',
							'image_id' => 'id',
						],
						'remove_from_atts' => true,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Picture Size', 'fusion-builder' ),
						'description' => __( 'fixed = width and height will be fixed <br />auto = width and height will adjust to the image.', 'fusion-builder' ),
						'param_name'  => 'picture_size',
						'value'       => [
							'fixed' => esc_attr__( 'Fixed', 'fusion-builder' ),
							'auto'  => esc_attr__( 'Auto', 'fusion-builder' ),
						],
						'default'     => 'fixed',
						'callback'    => [
							'function' => 'fusion_carousel_images',
							'action'   => 'get_fusion_image_carousel_children_data',
							'ajax'     => true,
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Hover Type', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the hover effect type. Hover Type will be disabled when caption styles other than Above or Below are chosen.', 'fusion-builder' ),
						'param_name'  => 'hover_type',
						'value'       => [
							'none'    => esc_attr__( 'None', 'fusion-builder' ),
							'zoomin'  => esc_attr__( 'Zoom In', 'fusion-builder' ),
							'zoomout' => esc_attr__( 'Zoom Out', 'fusion-builder' ),
							'liftup'  => esc_attr__( 'Lift Up', 'fusion-builder' ),
						],
						'default'     => 'none',
						'preview'     => [
							'selector' => '.fusion-image-wrapper',
							'type'     => 'class',
							'toggle'   => 'hover',
						],
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'navin',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dario',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'resa',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'schantel',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dany',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Autoplay', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to autoplay the carousel.', 'fusion-builder' ),
						'param_name'  => 'autoplay',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Maximum Columns', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the number of max columns to display.', 'fusion-builder' ),
						'param_name'  => 'columns',
						'value'       => '5',
						'min'         => '1',
						'max'         => '6',
						'step'        => '1',
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Column Spacing', 'fusion-builder' ),
						'description' => esc_attr__( 'Insert the amount of spacing between items without "px". ex: 13.', 'fusion-builder' ),
						'param_name'  => 'column_spacing',
						'value'       => '13',
						'min'         => '0',
						'max'         => '300',
						'step'        => '1',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Scroll Items', 'fusion-builder' ),
						'description' => esc_attr__( 'Insert the amount of items to scroll. Leave empty to scroll number of visible items.', 'fusion-builder' ),
						'param_name'  => 'scroll_items',
						'value'       => '',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Navigation', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to show navigation buttons on the carousel.', 'fusion-builder' ),
						'param_name'  => 'show_nav',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'yes',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Mouse Scroll', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to enable mouse drag control on the carousel. IMPORTANT: For easy draggability, when mouse scroll is activated, links will be disabled.', 'fusion-builder' ),
						'param_name'  => 'mouse_scroll',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Border', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to enable a border around the images.', 'fusion-builder' ),
						'param_name'  => 'border',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'yes',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Image lightbox', 'fusion-builder' ),
						'description' => esc_attr__( 'Show image in lightbox. Lightbox must be enabled in Global Options or the image will open up in the same tab by itself.', 'fusion-builder' ),
						'param_name'  => 'lightbox',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
					],
					[
						'type'             => 'select',
						'heading'          => esc_attr__( 'Caption', 'fusion-builder' ),
						'description'      => esc_attr__( 'Choose the caption style.', 'fusion-builder' ),
						'param_name'       => 'caption_style',
						'value'            => [
							'off'      => esc_attr__( 'Off', 'fusion-builder' ),
							'above'    => esc_attr__( 'Above', 'fusion-builder' ),
							'below'    => esc_attr__( 'Below', 'fusion-builder' ),
							'navin'    => esc_attr__( 'Navin', 'fusion-builder' ),
							'dario'    => esc_attr__( 'Dario', 'fusion-builder' ),
							'resa'     => esc_attr__( 'Resa', 'fusion-builder' ),
							'schantel' => esc_attr__( 'Schantel', 'fusion-builder' ),
							'dany'     => esc_attr__( 'Dany', 'fusion-builder' ),
						],
						'default'          => 'off',
						'group'            => esc_attr__( 'Caption', 'fusion-builder' ),
						'child_dependency' => true,
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Image Title Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the image title.', 'fusion-builder' ),
						'param_name'  => 'caption_title_color',
						'value'       => '',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'default'     => '',
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Image Title Size', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the font size of the image title. Enter value including any valid CSS unit, ex: 20px.', 'fusion-builder' ),
						'param_name'  => 'caption_title_size',
						'value'       => '',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Image Title Heading Tag', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose HTML tag of the image title, either div or the heading tag, h1-h6.', 'fusion-builder' ),
						'param_name'  => 'caption_title_tag',
						'value'       => [
							'1'   => 'H1',
							'2'   => 'H2',
							'3'   => 'H3',
							'4'   => 'H4',
							'5'   => 'H5',
							'6'   => 'H6',
							'div' => 'DIV',
						],
						'default'     => '2',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'font_family',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Image Title Font Family', 'fusion-builder' ),
						'description'      => esc_html__( 'Controls the font family of the image title.', 'fusion-builder' ),
						'param_name'       => 'caption_title_font',
						'group'            => esc_attr__( 'Caption', 'fusion-builder' ),
						'default'          => [
							'font-family'  => '',
							'font-variant' => '400',
						],
						'dependency'       => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Image Title Transform', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose how the title is displayed.', 'fusion-builder' ),
						'param_name'  => 'caption_title_transform',
						'default'     => '',
						'value'       => [
							''          => esc_attr__( 'Default', 'fusion-builder' ),
							'none'      => esc_attr__( 'Normal', 'fusion-builder' ),
							'uppercase' => esc_attr__( 'Uppercase', 'fusion-builder' ),
						],
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Image Caption Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the image caption.', 'fusion-builder' ),
						'param_name'  => 'caption_text_color',
						'value'       => '',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'default'     => '',
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Image Caption Background Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the background color of the caption.', 'fusion-builder' ),
						'param_name'  => 'caption_background_color',
						'value'       => '',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'default'     => '',
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'above',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'below',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'navin',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dario',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'resa',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Image Caption Size', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the font size of the image caption. Enter value including any valid CSS unit, ex: 20px.', 'fusion-builder' ),
						'param_name'  => 'caption_text_size',
						'value'       => '',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'font_family',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Image Caption Font Family', 'fusion-builder' ),
						'description'      => esc_html__( 'Controls the font family of the image caption.', 'fusion-builder' ),
						'param_name'       => 'caption_text_font',
						'group'            => esc_attr__( 'Caption', 'fusion-builder' ),
						'default'          => [
							'font-family'  => '',
							'font-variant' => '400',
						],
						'dependency'       => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Image Caption Transform', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose how the text is displayed.', 'fusion-builder' ),
						'param_name'  => 'caption_text_transform',
						'default'     => '',
						'value'       => [
							''          => esc_attr__( 'Default', 'fusion-builder' ),
							'none'      => esc_attr__( 'Normal', 'fusion-builder' ),
							'uppercase' => esc_attr__( 'Uppercase', 'fusion-builder' ),
						],
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Caption Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the caption border.', 'fusion-builder' ),
						'param_name'  => 'caption_border_color',
						'value'       => '',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'default'     => '',
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'above',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'below',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'navin',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'schantel',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dany',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Image Overlay Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the image overlay.', 'fusion-builder' ),
						'param_name'  => 'caption_overlay_color',
						'value'       => '',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'default'     => $fusion_settings->get( 'primary_color' ),
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'above',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'below',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Caption Align', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose how to align the caption.', 'fusion-builder' ),
						'param_name'  => 'caption_align',
						'responsive'  => [
							'state' => 'large',
						],
						'value'       => [
							'none'   => esc_attr__( 'Text Flow', 'fusion-builder' ),
							'left'   => esc_attr__( 'Left', 'fusion-builder' ),
							'right'  => esc_attr__( 'Right', 'fusion-builder' ),
							'center' => esc_attr__( 'Center', 'fusion-builder' ),
						],
						'default'     => 'none',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'schantel',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dany',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'navin',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dario',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'resa',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Caption Area Margin', 'fusion-builder' ),
						'description'      => esc_attr__( 'In pixels or percentage, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'caption_margin',
						'value'            => [
							'caption_margin_top'    => '',
							'caption_margin_right'  => '',
							'caption_margin_bottom' => '',
							'caption_margin_left'   => '',
						],
						'callback'         => [
							'function' => 'fusion_style_block',
						],
						'group'            => esc_attr__( 'Caption', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'schantel',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dany',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'navin',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dario',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'resa',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'checkbox_button_set',
						'heading'     => esc_attr__( 'Element Visibility', 'fusion-builder' ),
						'param_name'  => 'hide_on_mobile',
						'value'       => fusion_builder_visibility_options( 'full' ),
						'default'     => fusion_builder_default_visibility( 'array' ),
						'description' => __( 'Choose to show or hide the element on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
				],
			],
			'parent'
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_images' );

/**
 * Map shortcode to Avada Builder.
 */
function fusion_element_fusion_image() {
	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_ImageCarousel',
			[
				'name'              => esc_attr__( 'Image', 'fusion-builder' ),
				'description'       => esc_attr__( 'Enter some content for this textblock.', 'fusion-builder' ),
				'shortcode'         => 'fusion_image',
				'hide_from_builder' => true,
				'params'            => [
					[
						'type'        => 'upload',
						'heading'     => esc_attr__( 'Image', 'fusion-builder' ),
						'description' => esc_attr__( 'Upload an image to display.', 'fusion-builder' ),
						'param_name'  => 'image',
						'value'       => '',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Image ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Image ID from Media Library.', 'fusion-builder' ),
						'param_name'  => 'image_id',
						'value'       => '',
						'hidden'      => true,
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_image_carousel',
							'ajax'     => true,
						],
					],
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Image Title', 'fusion-builder' ),
						'description'  => esc_attr__( 'Enter title text to be displayed on image.', 'fusion-builder' ),
						'param_name'   => 'image_title',
						'value'        => '',
						'dynamic_data' => true,
						'dependency'   => [
							[
								'element'  => 'parent_caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Image Caption', 'fusion-builder' ),
						'description'  => esc_attr__( 'Enter caption text to be displayed on image.', 'fusion-builder' ),
						'param_name'   => 'image_caption',
						'value'        => '',
						'dynamic_data' => true,
						'dependency'   => [
							[
								'element'  => 'parent_caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'link_selector',
						'heading'     => esc_attr__( 'Image Link', 'fusion-builder' ),
						'description' => esc_attr__( 'Add the url the image should link to. If lightbox option is enabled, you can also use this to open a different image in the lightbox.', 'fusion-builder' ),
						'param_name'  => 'link',
						'value'       => '',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Link Target', 'fusion-builder' ),
						'description' => __( '_self = open in same window <br />_blank = open in new window.', 'fusion-builder' ),
						'param_name'  => 'linktarget',
						'value'       => [
							'_self'  => esc_attr__( '_self', 'fusion-builder' ),
							'_blank' => esc_attr__( '_blank', 'fusion-builder' ),
						],
						'default'     => '_self',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Image Alt Text', 'fusion-builder' ),
						'description' => esc_attr__( 'The alt attribute provides alternative information if an image cannot be viewed.', 'fusion-builder' ),
						'param_name'  => 'alt',
						'value'       => '',
					],
				],
				'tag_name'          => 'li',
				'callback'          => [
					'function' => 'fusion_ajax',
					'action'   => 'get_fusion_image_carousel',
					'ajax'     => true,
				],
			],
			'child'
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_fusion_image' );
