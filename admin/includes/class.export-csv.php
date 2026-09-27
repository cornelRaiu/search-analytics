<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'MWTSA_Export_CSV' ) ) {

    class MWTSA_Export_CSV {

        public function mwtsa_export_to_csv( $values, $filename = '', $columns = array() ) {

            if ( empty( $values ) ) {
                return false; //TODO: return error message?
            }

            if ( empty( $filename ) ) {
                $filename = apply_filters( 'mwtsa_export_filename', 'export-' . md5( 'export-' . microtime( true ) ) . '.csv' );
            }

            header( 'Content-Type: text/csv' );
            header( 'Content-Disposition: attachment; filename=' . $filename );
            header( 'Pragma: no-cache' );
            header( "Expires: 0" );

            $stream = fopen( "php://output", "w" );

            if ( ! empty ( $columns ) ) {
                fputcsv( $stream, array_map( array( $this, 'escape_csv_cell' ), $columns ), ',', '"', '\\' );
            }

            foreach ( $values as $result ) {
                fputcsv( $stream, array_map( array( $this, 'escape_csv_cell' ), $result ), ',', '"', '\\' );
            }

            fclose( $stream ); // phpcs:ignore
            exit();
        }

        /**
         * Search terms are typed by visitors, so a term such as =HYPERLINK(...) must not run as a formula when the
         * export is opened in a spreadsheet app. Prefixing a quote makes the app show it as text.
         */
        public function escape_csv_cell( $value ) {
            if ( is_string( $value ) && '' !== $value && ! is_numeric( $value ) && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
                return "'" . $value;
            }

            return $value;
        }
    }

}