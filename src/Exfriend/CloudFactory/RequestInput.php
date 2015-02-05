<?php

namespace Exfriend\CloudFactory;


class RequestInput {


    public $options = array(
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
    );


}