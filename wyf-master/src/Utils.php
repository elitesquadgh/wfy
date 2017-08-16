<?php

class Utils
{
    private static $singulars = array();
    private static $plurals = array();
    
    /**
     * Returns the sigular form of any plural english word which is passed to it.
     * 
     * @param string $word
     * @see Utils::plural
     */
    public static function singular($word)
    {
        $singular = array_search($word, Utils::$singulars);
        if($singular == false)
        {
            if(substr($word, -3) == "ses")
            {
                $singular = substr($word, 0, strlen($word) - 2);
            }
            elseif(substr($word, -3) == "ies")
            {
                $singular = substr($word, 0, strlen($word) - 3) . "y";
            }
            elseif(strtolower($word) == "indices")
            {
                $singular = "index";
            }
            else if(substr(strtolower($word), -4) == 'news')
            {
                $singular = $word;
            }
            else if(substr(strtolower($word), -8) == 'branches')
            {
                $singular = substr($word, 0, strlen($word) - 2);
            }
            else if(substr($word, -1) == "s")
            {
                $singular = substr($word, 0, strlen($word) - 1);
            }
            else
            {
                $singular = $word;
            }
            Utils::$singulars[$singular] = $word;
        }
        return $singular;
    }

    /**
     * Returns the plural form of any singular english word which is passed to it.
     * 
     * @param string $word
     */
    public static function plural($word)
    {
        $plural = array_search($word, Utils::$plurals);
        if($plural === false)
        {
            if(substr($word, -1) == "y")
            {
                $plural = substr($word, 0, strlen($word) - 1) . "ies";
            }
            elseif(strtolower($word) == "index")
            {
                $plural = "indices";
            }            
            elseif(substr($word, -2) == "us")
            {
                $plural = $word . "es";
            } 
            elseif(substr($word, -2) == "ss")
            {
                $plural = $word . "es";
            }
            elseif(substr($word, -1) != "s")
            {
                $plural = $word . "s";
            }
            else
            {
                throw new exceptions\UnknownPluralException("Could not determine the plural for $word");
            }
            Utils::$plurals[$plural] = $word;
        }
        return $plural;
    }    
    
    /**
     * Converts a string time representation of the format DD/MM/YYY [HH:MI:SS]
     * into a unix timestamp. The conversion is done with the strtotime()
     * function which comes as part of the php standard library.
     *
     * @param string $string The date
     * @param boolean $hasTime When specified, the time components are also added
     * @return int
     */
    public static function stringToTime($string, $hasTime = false)
    {
        if(preg_match("/(\d{2})\/(\d{2})\/(\d{4})(\w\d{2}:\d{2}:\d{2})?/", $string) == 0) return false;
        $dateComponents = explode(" ", $string);

        $decomposeDate = explode("/", $dateComponents[0]);
        $decomposeTime = array();

        if($hasTime === true)
        {
            $decomposeTime = explode(":", $dateComponents[1]);
        }

        return
        strtotime("{$decomposeDate[2]}-{$decomposeDate[1]}-{$decomposeDate[0]}") +
        ($hasTime === true ? ($decomposeTime[0] * 3600 + $decomposeTime[1] * 60 + $decomposeTime[2]) : 0);
    }

    /**
     * Converts a string time representation of the format DD/MM/YYY [HH:MI:SS]
     * into an oracle date format DD-MON-YY.
     *
     * @param string $string The date
     * @param boolean $hasTime When specified, the time components are also added
     * @todo Allow the returning of the time values too.
     * @return string
     */
    public static function stringToDatabaseDate($string, $hasTime = false)
    {
        $timestamp = Common::stringToTime($string, $hasTime);
        return date("Y-m-d", $timestamp);
    }    
}
