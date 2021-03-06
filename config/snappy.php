<?php

return array(


    'pdf' => array(
        'enabled' => true,
        'binary' => '/usr/local/bin/wkhtmltopdf',
        'timeout' => false,
        'options' => array(
                            'print-media-type' => true,
                            'outline' => true,
                            'dpi' => 96,
                            'page-size' => 'A4',
                        ),
    ),
    'image' => array(
        'enabled' => true,
        'binary' => '/usr/local/bin/wkhtmltoimage',
        'timeout' => false,
        'options' => array(),
    ),


);
