<?php

namespace C\P;

class Helper
{
    static function isPOST(){
        $b = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $b = true;
        }
        return $b;
    }
    static function isGET(){
        $b = false;
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $b = true;
        }
        return $b;
    }
    static function isPUT(){
        $b = false;
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $b = true;
        }
        return $b;
    }
    static function isDELETE(){
        $b = false;
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $b = true;
        }
        return $b;
    } 
}