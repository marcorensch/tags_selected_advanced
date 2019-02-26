<?php
function sentenceTrim($string, $maxLength = 300) {
    $string = preg_replace('/\s+/', ' ', trim($string)); // Replace new lines (optional)

    if (mb_strlen($string) >= $maxLength) {
        $string = mb_substr($string, 0, $maxLength);

        $puncs  = array('. ', '! ', '? '); // Possible endings of sentence
        $maxPos = 0;

        foreach ($puncs as $punc) {
            $pos = mb_strrpos($string, $punc);

            if ($pos && $pos > $maxPos) {
                $maxPos = $pos;
            }
        }

        if ($maxPos) {
            return mb_substr($string, 0, $maxPos + 1);
        }

        return rtrim($string) . '&hellip;';
    } else {
        return $string;
    }           
}
?>

