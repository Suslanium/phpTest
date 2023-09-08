<?php
function validateStringLength($string, $minLength = 8): bool
{
    return strlen($string) >= $minLength;
}

function matchStringRegex($string, $pattern)
{
    return preg_match($pattern, $string);
}

?>
