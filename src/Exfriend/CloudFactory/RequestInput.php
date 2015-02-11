<?php

namespace Exfriend\CloudFactory;


class RequestInput {


    public $options = array(
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
    );


    /**
     * @param $headers
     * @return array
     */
    public function mergeAssocHeaders( $headers )
    {
        $new_headers = array();
        foreach ( $headers as $key => $value )
        {
            $new_headers [ ] = $key . ': ' . $value;
        }
        return $new_headers;
    }

}