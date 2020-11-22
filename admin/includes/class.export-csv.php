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
                fputcsv( $stream, $columns );
            }

            foreach ( $values as $result ) {
                fputcsv( $stream, $result );
            }

            fclose( $stream );
            exit();
        }
    }

}