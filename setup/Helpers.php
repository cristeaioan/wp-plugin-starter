<?php

class Helpers {

    /**
     * Prompts the user for input and returns it.
     *
     * @param string $text
     * @param string $default
     * @return string
     */
    public static function prompt( $text, $default = '' ) {
        echo $text . ($default ? " [{$default}]" : '') . ": ";
        $input = trim(fgets(STDIN));
        return $input ?: $default;
    }

    /**
     * Determines whether the answer is a confirmation.
     *
     * @param string $answer
     * @return bool
     */
    public static function has_confirmed( $answer ) {
        return in_array(strtolower($answer), ['yes', 'y']);
    }

}