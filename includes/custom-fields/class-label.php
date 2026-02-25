<?php

namespace MydPro\Includes\Custom_Fields;

class Label {
	/**
	 * Attr for
	 *
	 * @var string
	 */
	protected $for;

	/**
	 * Label text content
	 *
	 * @var string
	 */
	protected $text;

	/**
	 * Constructor
	 */
	public function __construct( array $args = array() ) {
		$this->for = $args['id'] ?? '';
		$this->text = $args['label'] ?? '';
	}

	/**
	 * Output the rendered label.
	 *
	 * @return string
	 */
	public function output() {
		if( empty( $this->text ) ) {
			return;
		}
		
		return sprintf(
			'<label for="%s">%s</label>',
			esc_attr( $this->for ),
			esc_html( $this->text )
		);
	}
}
