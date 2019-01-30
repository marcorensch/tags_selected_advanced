<?php

function substr_sentence($string, $start=0, $limit=10, $max_char = 600)
    {
    /* This functions cuts a long string in sentences.
    *
    * substr_sentence($string, $start, $limit);
    * $string = 'A example. By someone that loves PHP. Do you? We do!';
    * $start = 0; // we would start at the beginning
    * $limit = 10; // so, we get 10 sentences (not 10 words or characters!)
    *
    * It's not as substr()) in single characters.
    * It's not as substr_words() in single words.
    * 
    * No more broken lines in a story. The story/article must go on!
    *
    * Written by Eddy Erkelens "Zunflappie"
    * Published on www.mastercode.nl 
    * May be free used and adapted
    *
    */
    
    // list of sentences-ends. All sentences ends with one of these. For PHP, add the ;
    $end_characters = array(
                '. '
                );
    
    // put $string in array $parts, necessary evil
    $parts = array($string);            
        
    // foreach interpunctation-mark we will do this loop
    foreach($end_characters as $end_character)
        {
        // go thru each part of the sentences we already have
        foreach($parts as $part)
            {
            // make array with the new sentences
            $sentences[] = explode($end_character, $part);
            }
        
        // unfortunately explode() removes the end character itself. So, place it back
        foreach($sentences as $sentence)
            {
            // some strange stuff
            foreach($sentence as $real_sentence)
                {
                // empty sentence we do not want
                if($real_sentence != '')
                    {
                    // if there is already an end-character, dont place another one
                    if(in_array(substr($real_sentence, -1, 1), $end_characters))
                        {
                        // store for next round
                        $next[] = trim($real_sentence);        
                        }
                    else
                        {
                        // store for next round and add the removed character
                        $next[] = trim($real_sentence).$end_character;    
                        }
                    }
                }
            }
            
        // store for next round
        $parts = $next;
        
        // unset the remaining and useless stuff
        unset($sentences, $sentence, $next);
        }    

	// check for max-char-length
	$total_chars = 0;
	$sentence_nr = 0;
	$sentences = array();
	
	// walk thru each member of $part
	foreach($parts as $part)
		{
		// count the string-lenght and add this to $total_chars
		$total_chars += strlen($part);
		
		// if $total-chars not already higher then max-char, add this sentences!
		if($total_chars < $max_char)
			{
			$sentences[] = $part;
			}
		}

    // return the shortened story as a string
    return implode(" ", array_slice($sentences, $start, $limit));
    }
?>