<?php
/**
 * JSON Schema builder for ACF fields.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Acf
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Acf;

defined( 'ABSPATH' ) || exit;

/**
 * Turns resolved ACF field definitions into JSON Schema.
 *
 * The schemas are what an AI client reads to know how to fill a block, so the
 * labels and instructions the theme authors wrote in the admin are carried
 * through as descriptions rather than dropped.
 */
final class SchemaBuilder {

	/**
	 * Attachment reference: an ID already in the media library, or a URL to sideload.
	 *
	 * @var array<string, mixed>
	 */
	private const ATTACHMENT_REF = array(
		'type'        => array( 'integer', 'string' ),
		'description' => 'Attachment ID from the media library, or an image URL to import.',
	);

	/**
	 * Builds an object schema for a list of resolved fields.
	 *
	 * @param array<int, array<string, mixed>> $fields Resolved ACF fields.
	 * @return array<string, mixed> JSON Schema object.
	 */
	public static function from_fields( array $fields ): array {
		$properties = array();
		$required   = array();

		foreach ( $fields as $field ) {
			$name = (string) ( $field['name'] ?? '' );

			if ( '' === $name ) {
				continue;
			}

			$properties[ $name ] = self::from_field( $field );

			// A required field with a default is filled with it when left out,
			// as the editor pre-fills it: the client does not have to send it.
			if ( ! empty( $field['required'] ) && ! self::has_default( $field ) ) {
				$required[] = $name;
			}
		}

		$schema = array(
			'type'                 => 'object',
			// `properties` has to encode as a JSON object. Two cases would make
			// it an array: no fields at all (a `styleguide` block), and fields
			// named `0`, `1`… (a theme's pair of links or images), which PHP
			// turns into a list.
			'properties'           => empty( $properties ) || array_is_list( $properties ) ? (object) $properties : $properties,
			'additionalProperties' => false,
		);

		if ( ! empty( $required ) ) {
			$schema['required'] = $required;
		}

		return $schema;
	}

	/**
	 * Returns whether a field carries a default value.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @return bool
	 */
	public static function has_default( array $field ): bool {
		$default = $field['default_value'] ?? null;

		return null !== $default && '' !== $default && array() !== $default;
	}

	/**
	 * Builds the schema for one resolved ACF field.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @return array<string, mixed> JSON Schema fragment.
	 */
	public static function from_field( array $field ): array {
		$type   = (string) ( $field['type'] ?? 'text' );
		$schema = self::for_type( $type, $field );

		$description = self::describe( $field );

		if ( '' !== $description ) {
			$schema['description'] = isset( $schema['description'] )
				? $description . ' ' . $schema['description']
				: $description;
		}

		if ( isset( $field['default_value'] ) && ! is_array( $field['default_value'] ) && '' !== $field['default_value'] ) {
			$schema['default'] = $field['default_value'];
		}

		return $schema;
	}

	/**
	 * Maps an ACF field type onto a JSON Schema fragment.
	 *
	 * @param string               $type  ACF field type.
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @return array<string, mixed>
	 */
	private static function for_type( string $type, array $field ): array {
		switch ( $type ) {
			case 'textarea':
			case 'text':
			case 'email':
			case 'url':
			case 'password':
			case 'color_picker':
			case 'date_picker':
			case 'date_time_picker':
			case 'time_picker':
			case 'oembed':
				return self::string_schema( $field );

			case 'wysiwyg':
				return array(
					'type'        => 'string',
					'description' => 'Rich text. Accepts HTML (p, strong, em, ul, ol, li, a).',
				);

			case 'number':
			case 'range':
				return self::number_schema( $field );

			case 'true_false':
				return array( 'type' => 'boolean' );

			case 'select':
				return self::choice_schema( $field, ! empty( $field['multiple'] ) );

			case 'radio':
			case 'button_group':
				return self::choice_schema( $field, false );

			case 'checkbox':
				return self::choice_schema( $field, true );

			case 'link':
				return self::link_schema();

			case 'image':
			case 'file':
				return self::ATTACHMENT_REF;

			case 'gallery':
				return array(
					'type'        => 'array',
					'items'       => self::ATTACHMENT_REF,
					'description' => 'Ordered list of attachments.',
				);

			case 'post_object':
				return self::relation_schema( $field, ! empty( $field['multiple'] ) );

			case 'relationship':
				return self::relation_schema( $field, true );

			case 'taxonomy':
				return self::taxonomy_schema( $field );

			case 'group':
				return self::from_fields( $field['sub_fields'] ?? array() );

			case 'repeater':
				return self::repeater_schema( $field );

			case 'flexible_content':
				return self::flexible_schema( $field );

			default:
				return array(
					'type'        => array( 'string', 'number', 'boolean', 'array', 'object' ),
					'description' => sprintf( 'Raw value for the unmapped ACF field type "%s".', $type ),
				);
		}
	}

	/**
	 * Builds a string schema, carrying ACF's length constraint.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @return array<string, mixed>
	 */
	private static function string_schema( array $field ): array {
		$schema = array( 'type' => 'string' );

		if ( ! empty( $field['maxlength'] ) ) {
			$schema['maxLength'] = (int) $field['maxlength'];
		}

		return $schema;
	}

	/**
	 * Builds a number schema, carrying ACF's range constraints.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @return array<string, mixed>
	 */
	private static function number_schema( array $field ): array {
		$step   = $field['step'] ?? 1;
		$is_int = '' === $step || null === $step || floor( (float) $step ) === (float) $step;
		$schema = array( 'type' => $is_int ? 'integer' : 'number' );

		if ( isset( $field['min'] ) && '' !== $field['min'] ) {
			$schema['minimum'] = $is_int ? (int) $field['min'] : (float) $field['min'];
		}

		if ( isset( $field['max'] ) && '' !== $field['max'] ) {
			$schema['maximum'] = $is_int ? (int) $field['max'] : (float) $field['max'];
		}

		return $schema;
	}

	/**
	 * Builds an enum schema from an ACF choices list.
	 *
	 * @param array<string, mixed> $field    Resolved ACF field.
	 * @param bool                 $multiple Whether several values may be selected.
	 * @return array<string, mixed>
	 */
	private static function choice_schema( array $field, bool $multiple ): array {
		$choices = $field['choices'] ?? array();
		$values  = array_map( 'strval', array_keys( is_array( $choices ) ? $choices : array() ) );
		$labels  = array();

		if ( is_array( $choices ) ) {
			foreach ( $choices as $value => $label ) {
				$labels[] = sprintf( '%s = %s', $value, is_scalar( $label ) ? (string) $label : $value );
			}
		}

		$item = array( 'type' => 'string' );

		if ( ! empty( $values ) ) {
			$item['enum']        = $values;
			$item['description'] = 'Options: ' . implode( '; ', $labels ) . '.';
		}

		if ( ! $multiple ) {
			return $item;
		}

		$description = $item['description'] ?? '';
		unset( $item['description'] );

		return array_filter(
			array(
				'type'        => 'array',
				'items'       => $item,
				'description' => $description,
			)
		);
	}

	/**
	 * Builds the schema for an ACF link field.
	 *
	 * @return array<string, mixed>
	 */
	private static function link_schema(): array {
		return array(
			'type'                 => 'object',
			'description'          => 'Link. Use a site-relative or absolute URL.',
			'properties'           => array(
				'url'    => array(
					'type'        => 'string',
					'description' => 'Destination URL.',
				),
				'title'  => array(
					'type'        => 'string',
					'description' => 'Link label shown to the visitor.',
				),
				'target' => array(
					'type'        => 'string',
					'enum'        => array( '', '_blank' ),
					'description' => 'Use "_blank" to open in a new tab, otherwise an empty string.',
				),
			),
			'required'             => array( 'url' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Builds the schema for a post object or relationship field.
	 *
	 * @param array<string, mixed> $field    Resolved ACF field.
	 * @param bool                 $multiple Whether several posts may be selected.
	 * @return array<string, mixed>
	 */
	private static function relation_schema( array $field, bool $multiple ): array {
		$post_types = array_filter( (array) ( $field['post_type'] ?? array() ) );
		$hint       = empty( $post_types )
			? 'Post ID or slug.'
			: sprintf( 'Post ID or slug, limited to: %s.', implode( ', ', $post_types ) );

		$item = array(
			'type'        => array( 'integer', 'string' ),
			'description' => $hint,
		);

		if ( ! $multiple ) {
			return $item;
		}

		return array(
			'type'        => 'array',
			'items'       => $item,
			'description' => $hint,
		);
	}

	/**
	 * Builds the schema for a taxonomy field.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @return array<string, mixed>
	 */
	private static function taxonomy_schema( array $field ): array {
		$taxonomy = (string) ( $field['taxonomy'] ?? '' );
		$hint     = '' === $taxonomy
			? 'Term ID or slug.'
			: sprintf( 'Term ID or slug from the "%s" taxonomy.', $taxonomy );

		$item     = array(
			'type'        => array( 'integer', 'string' ),
			'description' => $hint,
		);
		$multiple = in_array( $field['field_type'] ?? '', array( 'checkbox', 'multi_select' ), true );

		if ( ! $multiple ) {
			return $item;
		}

		return array(
			'type'        => 'array',
			'items'       => $item,
			'description' => $hint,
		);
	}

	/**
	 * Builds the schema for a repeater field.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @return array<string, mixed>
	 */
	private static function repeater_schema( array $field ): array {
		$schema = array(
			'type'  => 'array',
			'items' => self::from_fields( $field['sub_fields'] ?? array() ),
		);

		if ( ! empty( $field['min'] ) ) {
			$schema['minItems'] = (int) $field['min'];
		}

		if ( ! empty( $field['max'] ) ) {
			$schema['maxItems'] = (int) $field['max'];
		}

		return $schema;
	}

	/**
	 * Builds the schema for a flexible content field.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @return array<string, mixed>
	 */
	private static function flexible_schema( array $field ): array {
		$names = array();

		foreach ( (array) ( $field['layouts'] ?? array() ) as $layout ) {
			$name = (string) ( $layout['name'] ?? '' );

			if ( '' !== $name ) {
				$names[] = $name;
			}
		}

		return array(
			'type'        => 'array',
			'description' => 'Ordered list of blocks. Each item needs an "acf_fc_layout" naming its block type; call get-block-schema for the shape of a given type.',
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'acf_fc_layout' => array(
						'type'        => 'string',
						'enum'        => $names,
						'description' => 'Block type.',
					),
				),
				'required'   => array( 'acf_fc_layout' ),
			),
		);
	}

	/**
	 * Builds a human-readable description from an ACF field's label and instructions.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @return string
	 */
	private static function describe( array $field ): string {
		$parts = array();

		$label = trim( (string) ( $field['label'] ?? '' ) );

		if ( '' !== $label ) {
			$parts[] = rtrim( $label, '.' ) . '.';
		}

		$instructions = trim( wp_strip_all_tags( (string) ( $field['instructions'] ?? '' ) ) );

		if ( '' !== $instructions ) {
			$parts[] = $instructions;
		}

		return trim( implode( ' ', $parts ) );
	}
}
