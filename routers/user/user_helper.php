<?php
function validatePassword($password): bool
{
    return validateStringLength($password) && matchStringRegex($password, "((?=.*[A-Z])(?=.*[a-z])(?=.*[\d])(?=.*[@$!%*#?&])[a-zA-Z\d@$!%*#?&]{8,})");
}

?>
