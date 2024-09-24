<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Attribute_Manager {

    public $render_attributes = [];

    public function add_attribute( $element, $key = null, $value = null, $overwrite = false ) {
        
		if ( is_array( $element ) ) {
			foreach ( $element as $element_key => $attributes ) {
				$this->add_attribute( $element_key, $attributes, null, $overwrite );
			}
			return $this;
		}

		if ( is_array( $key ) ) {
			foreach ( $key as $attribute_key => $attributes ) {
				$this->add_attribute( $element, $attribute_key, $attributes, $overwrite );
			}
			return $this;
		}

		if ( empty( $this->render_attributes[ $element ][ $key ] ) ) {
			$this->render_attributes[ $element ][ $key ] = [];
		}

		settype( $value, 'array' );

		if ( $overwrite ) {
			$this->render_attributes[ $element ][ $key ] = $value;
		} else {
			$this->render_attributes[ $element ][ $key ] = array_merge( $this->render_attributes[ $element ][ $key ], $value );
		}

		return $this;
	}

    public function get_attribute_string( $element ) {

        $attributes = [];

        if ( gettype($element) == 'array' ) {

            foreach ( $element as $_element ) {

                if ( empty( $_attributes = $this->render_attributes[ $_element ] ) ) continue;
                
                foreach ( $_attributes as $attribute_key => $attribute_values ) {

                    settype($attribute_values, 'array');

                    if ( ! array_key_exists($attribute_key, $attributes) ) {
                        $attributes[$attribute_key] = $attribute_values;
                    } else {
                        $attributes[$attribute_key] = array_merge( $attributes[$attribute_key], $attribute_values );
                    }
                }
                
            }

        } else {

            if ( empty( $attributes = $this->render_attributes[ $element ] ) ) return '';

        }
            
        $rendered_attributes = [];
    
        foreach ( $attributes as $attribute_key => $attribute_values ) {
            if ( is_array( $attribute_values ) ) {
                $attribute_values = implode( ' ', $attribute_values );
            }
            $rendered_attributes[] = sprintf( '%1$s="%2$s"', sanitize_key( $attribute_key ), esc_attr( $attribute_values ) );
        }

        return implode( ' ', $rendered_attributes );

	}

    public function print_attribute_string( $element ) {
        echo sanitize_text_field( $this->get_attribute_string( $element ) );
    }

}