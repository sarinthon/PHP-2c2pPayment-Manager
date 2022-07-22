<?php

namespace ShuGlobal\Core\Model;

class BaseModel
{
    public function __construct($json=null)
    {
        if (!isset($json)) {return;}
        if (is_string($json)) {
            $json = json_decode($json);
        }

        foreach ($json as $key => $value) {
            $this->$key = $value;
        }
    }
}