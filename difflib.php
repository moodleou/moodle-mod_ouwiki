<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Standard diff function plus some extras for handling XHTML diffs.
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

/**
 * Basic diff utility function, using standard diff algorithm.
 *
 * Based on Bell Laboratories Computing Science Technical Report #41,
 * July 1976, Hunt & McIlroy, Appendix A.1 and A.3.
 *
 * http://www.cs.dartmouth.edu/~doug/diff.ps
 *
 * @param array $file1 Array of lines in file 1. The first line in the file
 *   MUST BE INDEX 1 NOT ZERO!!
 * @param array $file2 Array of lines in file 2, again starting from 1.
 * @return array An array with one entry (again 1-based) for each line in
 *   file 1, with its corresponding position in file 2 or 0 if it isn't there.
 */
require_once(dirname(__FILE__) . '/../../config.php');

function ouwiki_diff_internal($file1, $file2) {
    // Basic variables
    $n = count($file2);
    $m = count($file1);

    // Special-case for empty file2 which otherwise causes error
    if ($n == 0) {
        $result = array();
        for ($i = 1; $i <= $m; $i++) {
            $result[$i] = 0;
        }
        return $result;
    }

    // Step 1   Build list of elements
    /*////////////////////////////////*/

    $vhs = array();
    for ($j = 1; $j <= $n; $j++) {
        $vhs[$j] = new StdClass;
        $vhs[$j]->serial = $j;
        $vhs[$j]->hash = crc32($file2[$j]);
    }

    // Step 2   Sort by hash,serial
    /*/////////////////////////////*/

    usort($vhs, "ouwiki_diff_sort_v");

    // Make it start from 1 again
    array_unshift($vhs, 'bogus');
    unset($vhs[0]);

    // $vhshs is now an array including the line number 'serial' and hash
    // of each line in file 2, sorted by hash and then serial.

    // Step 3   Equivalence classes
    /*/////////////////////////////*/

    $eqc = array();
    $eqc[0] = new StdClass;
    $eqc[0]->serial = 0;
    $eqc[0]->last = true;
    for ($j = 1; $j <= $n; $j++) {
        $eqc[$j] = new StdClass;
        $eqc[$j]->serial = $vhs[$j]->serial;
        $eqc[$j]->last = $j===$n || $vhs[$j]->hash !== $vhs[$j+1]->hash;
    }

    // E is now an array sorted the same way as $vhs which includes
    // the line number 'serial' and whether or not that is the 'last'
    // line in the given equivalence class, i.e. set of identical lines

    // Step 4   For each line in file1, finds start of equivalence class
    /*//////////////////////////////////////////////////////////////////*/
    $pos = array();
    for ($i = 1; $i <= $m; $i++) {
        // Find matching last entry from equivalence list
        $pos[$i] = ouwiki_diff_find_last($vhs, $eqc, crc32($file1[$i]));
    }

    // P is now an array that finds the index (within $vhs) of the *first*
    // matching line in $vhs (referencing file 2, but not a line number,
    // because sorted in $vhs order) for each line in file 1. In other words
    // if you were to start at the P-value in $vhs and continue through, you
    // would find all the lines from file 2 that are equal to the given line
    // from file 1.

    // Step 5   Initialise vector of candidates
    /*/////////////////////////////////////////*/

    // I do not trust PHP references further than I can throw them (preferably
    // at the idiot who came up with the idea) so I am using a separate array
    // to store candidates and all references are integers into that.

    $candidates = array();
    $candidates[0] = new StdClass;
    $candidates[0]->a = 0;
    $candidates[0]->b = 0;
    $candidates[0]->previous = null;
    $candidates[1] = new StdClass;
    $candidates[1]->a = $m+1;
    $candidates[1]->b = $n+1;
    $candidates[1]->previous = null;

    $karr = array();
    $karr[0] = 0; // Ref to candidate 0
    $karr[1] = 1; // Ref to candidate 1
    $k = 0;

    // Step 6   Merge stage
    /*/////////////////////*/

    for ($i = 1; $i <= $m; $i++) {
        if ($pos[$i] !== 0) {
            ouwiki_diff_merge($karr, $k, $i, $eqc, $pos[$i], $candidates);
        }
    }

    // Step 7
    /*///////*/

    $jac = array();
    for ($i = 1; $i <= $m; $i++) {
        $jac[$i] = 0;
    }

    // Step 8   Follow candidate chain to make nice representation
    /*////////////////////////////////////////////////////////////*/

    $index = $karr[$k];
    while (!is_null($index)) {
        // Stop when we reach the first, dummy candidate
        if ($candidates[$index]->a != 0) {
            $jac[$candidates[$index]->a] = $candidates[$index]->b;
        }
        $index = $candidates[$index]->previous;
    }

    // Step 9   Get rid of 'jackpots' (hash collisions)
    /*////////////////////////////////////////////////*/

    for ($i = 1; $i <= $m; $i++) {
        if ($jac[$i] != 0 && $file1[$i] != $file2[$jac[$i]]) {
            $jac[$i] = 0;
        }
    }

    // Done! (Maybe.)
    return $jac;
}

// Functions needed by parts of the algorithm
/*///////////////////////////////////////////*/

// Merge, from step 7 (Appendix A.3)
function ouwiki_diff_merge(&$karr, &$k, $i, &$eqc, $p, &$candidates) {
    $r = 0;
    $c = $karr[0];

    while (true) {
        $j = $eqc[$p]->serial; // Paper says 'i' but this is wrong (OCR)

        // Binary search in $karr from $r to $k.
        $min = $r;
        $max = $k+1;

        while (true) {
            $try = (int)(($min+$max)/2);
            if ($candidates[$karr[$try]]->b >= $j) {
                $max = $try;
            } else if ($candidates[$karr[$try+1]]->b <= $j) {
                $min = $try+1;
            } else { // $try is less and $try+1 is more
                $s = $try;
                break;
            }
            if ($max <= $min) {
                $s = -1;
                break;
            }
        }

        if ($s > -1) {
            if ($candidates[$karr[$s+1]]->b > $j) {
                // Create new candidate
                $index = count($candidates);
                $candidates[$index] = new StdClass;
                $candidates[$index]->a = $i;
                $candidates[$index]->b = $j;
                $candidates[$index]->previous = $karr[$s];
                $karr[$r] = $c;
                $r = $s + 1;
                $c = $index; // Or should this go before?
            }

            if ($s === $k) {
                $karr[$k+2] = $karr[$k+1];
                $k++;
                break;
            }
        }

        if ($eqc[$p]->last) {
            break;
        }

        $p++;
    }
    $karr[$r] = $c;
}

// From Step 2
function ouwiki_diff_sort_v($a, $b) {
    if ($a->hash < $b->hash) {
        return -1;
    } else if ($a->hash > $b->hash) {
        return 1;
    } else if ($a->serial < $b->serial) {
        return -1;
    } else if ($a->serial > $b->serial) {
        return 1;
    } else {
        return 0;
    }
}

// From Step 4
function ouwiki_diff_find_last(&$vhs, &$eqc, $hash) {
    // Binary search in $vhs until we find something with $hash.

    // Min = 1, array is 1-indexed
    $min = 1;
    // Max = 1 higher than highest key
    end($vhs);
    $max = key($vhs)+1;
    while (true) {
        $try = (int)(($min+$max)/2);
        if ($vhs[$try]->hash > $hash) {
            $max = $try;
        } else if ($vhs[$try]->hash < $hash) {
            $min = $try+1;
        } else { // Equal
            break;
        }
        if ($max <= $min) {
            // No matching line
            return 0;
        }
    }

    // Now check back in $eqc to find the first line of that equivalence class
    for ($j = $try; !$eqc[$j-1]->last; $j--) {
        $j;// Inline control strucs not allowed.
    }
    return $j;
}

/*///////////////////////////*/


/**
 * Class representing one 'line' of HTML content for the purpose of
 * text comparison.
 */
class ouwiki_line {
    /* Array of ouwiki_words */
    public $words = array();

    /**
     * Construct line object based on a chunk of text.
     * @param string $data Text data that makes up this 'line'. (May include line breaks etc.)
     * @param int $linepos Position number for first character in text
     */
    public function __construct($data, $linepos) {
        // 1. Turn things we don't want into spaces (so that positioning stays same)

        // Whitespace replaced with space
        $data = preg_replace('/\s/', ' ', $data);

        // Various ways of writing non-breaking space replaced with space
        // Note that using a single param for replace only works because all
        // the search strings are 6 characters long
        $data = str_replace(array('&nbsp;', '&#xA0;', '&#160;') , '      ', $data);

        // Tags replaced with equal number of spaces
        $data = preg_replace_callback('/<.*?'.'>/', create_function(
            '$matches', 'return preg_replace("/./"," ", $matches[0]);'), $data);

        // 2. Analyse string so that each space-separated thing
        // is counted as a 'word' (note these may not be real words,
        // for instance words may include punctuation at either end)
        $pos = 0;
        while (true) {
            // Find a non-space
            for (; $pos < strlen($data) && substr($data, $pos, 1) === ' '; $pos++) {
                $pos;// Inline control strucs not allowed.
            }
            if ($pos == strlen($data)) {
                // No more content
                break;
            }

            // Aaaand find the next space after that
            $space2 = strpos($data, ' ', $pos);
            if ($space2 === false) {
                // No more spaces? Everything left must be a word.
                $word = ltrim(substr($data, $pos), chr(0xC2) . chr(0xA0));
                if (!empty($word)) {
                    $this->words[] = new ouwiki_word($word, $pos + $linepos);
                }
                break;
            } else {
                // Specially trim nbsp; see http://www.php.net/manual/en/function.trim.php#98812.
                $word = trim(substr($data, $pos, $space2-$pos), chr(0xC2) . chr(0xA0));
                if (!empty($word)) {
                    $this->words[] = new ouwiki_word($word, $pos + $linepos);
                }
                $pos = $space2;
            }
        }
    }

    /**
     * @return string Normalised string representation of this line object
     */
    public function get_as_string() {
        $result = '';
        foreach ($this->words as $word) {
            if ($result !== '') {
                $result .= ' ';
            }
            $result .= $word->word;
        }
        return $result;
    }

    /**
     * Static function converts lines to strings.
     * @param array $lines Array of ouwiki_line
     * @return array Array of strings
     */
    public static function get_as_strings($lines) {
        $strings = array();
        foreach ($lines as $key => $value) {
            $strings[$key] = $value->get_as_string();
        }
        return $strings;
    }

    /**
     * @return True if there are no words in the line
     */
    public function is_empty() {
        return count($this->words) === 0;
    }
}

/**
 * Represents single word for html comparison. Note that words
 * are just chunks of plain text and may not be actual words;
 * they could include punctuation or (if there was e.g. a span
 * in the middle of something) even be part-words.
 */
class ouwiki_word {
    /* Word as plain string */
    public $word;
    /* Start position in original xhtml */
    public $start;

    public function __construct($word, $start) {
        $this->word = $word;
        $this->start = $start;
    }
}

/**
 * Prepares XHTML content for text difference comparison.
 * @param string $content XHTML content [NO SLASHES]
 * @return array Array of ouwiki_line objects
 */
function ouwiki_diff_html_to_lines($content) {
    // These functions are a pain mostly because PHP preg_* don't provide
    // proper information as to the start/end position of matches. As a
    // consequence there is a lot of hackery going down. At every point we
    // replace things with spaces rather than getting rid, in order to store
    // positions within original content.

    // Get rid of all script, style, object tags (that might contain non-text
    // outside tags)
    $content = preg_replace_callback(
        '^(<script .*?</script>)|(<object .*?</object>)|(<style .*?</style>)^i', create_function(
            '$matches', 'return preg_replace("/./"," ",$matches[0]);'), $content);

    // Get rid of all ` symbols as we are going to use these for a marker later.
    $content = preg_replace('/[`]/', ' ', $content);

    // Put line breaks on block tags. Mark each line break with ` symbol
    $blocktags = array('p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'td', 'li');
    $taglist = '';
    foreach ($blocktags as $blocktag) {
        if ($taglist !== '') {
            $taglist .= '|';
        }
        $taglist .= "<$blocktag>|<\\/$blocktag>";
    }
    $content = preg_replace_callback('/(('.$taglist.')\s*)+/i', create_function(
        '$matches', 'return "`".preg_replace("/./"," ",substr($matches[0],1));'), $content);

    // Now go through splitting each line
    $lines = array();
    $index = 1;
    $pos = 0;
    while ($pos < strlen($content)) {
        $nextline = strpos($content, '`', $pos);
        if ($nextline === false) {
            // No more line breaks? Take content to end
            $nextline = strlen($content);
        }

        $linestr = substr($content, $pos, $nextline-$pos);
        $line = new ouwiki_line($linestr, $pos);
        if (!$line->is_empty()) {
            $lines[$index++] = $line;
        }
        $pos = $nextline+1;
    }
    return $lines;
}

/**
 * Represents a changed area of file and where it is located in the
 * two source files.
 */
class ouwiki_change_range {
    public $file1start, $file1count;
    public $file2start, $file2count;
}

/**
 * A more logical representation of the results from ouwiki_internal_diff()
 */
class ouwiki_changes {

    /* Array of indexes (in file 2) of added lines */
    public $adds;

    /* Array of indexes (in file 1) of deleted lines */
    public $deletes;

    /* Array of changed ranges */
    public $changes;

    /**
     * @param array $diff Array from line indices in file1
     *   to indices in file2. All indices 1-based.
     * @param int $count2 Number of lines in file2
     */
    public function __construct($diff, $count2) {
        // Find deleted lines
        $this->deletes = self::internal_find_deletes($diff, $count2);

        // Added lines work the same way after the comparison is
        // reversed.
        $this->adds = self::internal_find_deletes(
            ouwiki_diff_internal_flip($diff, $count2), count($diff));

        // Changed ranges are all the other lines from file 1 that
        // weren't found in file 2 but aren't deleted, and the
        // corresponding lines from file 2 (between the equivalent
        // 'found' lines).
        $this->changes = array();
        $matchbefore = 0;
        $inrange = -1; $lastrange = -1;
        foreach ($diff as $index1 => $index2) {
            // Changed line if this isn't in 'deleted' section and
            // doesn't have a match in file2.
            if ($index2 === 0 && !in_array($index1, $this->deletes)) {
                if ($inrange === -1) {
                    // Not already in a range, start a new one at array end
                    $inrange = count($this->changes);
                    $this->changes[$inrange] = new ouwiki_change_range;
                    $this->changes[$inrange]->file1start = $index1;
                    $this->changes[$inrange]->file1count = 1;
                    $this->changes[$inrange]->file2start = $matchbefore+1; // Last valid from file2
                    $this->changes[$inrange]->file2count = 0;
                    $lastrange = $inrange;
                } else {
                    // One more line that gets added to the range
                    $this->changes[$inrange]->file1count++;
                }
            } else {
                // Not in a range any more
                $inrange = -1;
                // If we have a line match...
                if ($index2 !== 0) {
                    // Remember this line as next range must start after it
                    $matchbefore = $index2;
                    // If last range is still looking for a number, fill that in too
                    if ($lastrange !== -1) {
                        $this->changes[$lastrange]->file2count = $index2
                            -$this->changes[$lastrange]->file2start;
                        $lastrange = -1;
                    }
                }
            }
        }
        // Unfinished range in file2 gets end of file
        if ($lastrange !== -1) {
            $this->changes[$lastrange]->file2count = $count2
                -$this->changes[$lastrange]->file2start+1;
        }
    }

    /**
     * Find deleted lines. These are lines in file1 that
     * cannot be present even in modified form in file2
     * because we have matching lines around them.
     * O(n) algorithm.
     * @param array $diff Array of file1->file2 indexes
     * @param int $count2 Count of lines in file2
     */
    public function internal_find_deletes($diff, $count2) {
        $deletes = array();

        // 1. Create a new array that includes the lowest-valued
        //    index2 value below each run of 0s.
        //    I.e. if our array is say 1,2,0,0,0,3,0 then the
        //    resulting array will be -,-,3,3,3,-,0
        $squidges = array();
        $lowest = 0;
        for ($index1 = count($diff); $index1 >= 1; $index1--) {
            $index2 = $diff[$index1];
            if ($index2 === 0) {
                $squidges[$index1] = $lowest;
            } else {
                $lowest = $index2;
            }
        }

        // 2. OK now we can use this new array to work out
        //    items that are known to be deleted because we
        //    have matching items either side
        $highest = 0;
        foreach ($diff as $index1 => $index2) {
            if ($index2 === 0) {
                if ($highest === $count2 || $highest+1 === $squidges[$index1]) {
                    // Yep! Definitely deleted.
                    $deletes[]=$index1;
                }
            } else {
                $highest = $index2;
            }
        }
        return $deletes;
    }
}

/**
 * Flips around the array returned by ouwiki_diff_internal
 * so that it refers to lines from the other file.
 * @param array $diff Array of index1=>index2
 * @param int $count2 Count of lines in file 2
 * @return array Flipped version
 */
function ouwiki_diff_internal_flip($diff, $count2) {
    $flip = array();
    for ($i = 1; $i <= $count2; $i++) {
        $flip[$i] = 0;
    }
    foreach ($diff as $index1 => $index2) {
        if ($index2 !== 0) {
            $flip[$index2]=$index1;
        }
    }
    return $flip;
}

/**
 * Compares two files based initially on lines and then on words within the lines that
 * differ.
 * @param array $lines1 Array of ouwiki_line
 * @param array $lines2 Array of ouwiki_line
 * @return array (deleted,added); deleted and added are arrays of ouwiki_word with
 *   position numbers from $lines1 and $lines2 respectively
 */
function ouwiki_diff_words($lines1, $lines2) {
    // Prepare arrays
    $deleted = array();
    $added = array();
    // Get line difference
    $linediff = ouwiki_diff(
        ouwiki_line::get_as_strings($lines1),
        ouwiki_line::get_as_strings($lines2));

    // Handle lines that were entirely deleted
    foreach ($linediff->deletes as $deletedline) {
        $deleted = array_merge($deleted, $lines1[$deletedline]->words);
    }
    // And ones that were entirely added
    foreach ($linediff->adds as $addedline) {
        $added = array_merge($added, $lines2[$addedline]->words);
    }

    // Changes get diffed at the individual-word level
    foreach ($linediff->changes as $changerange) {
        // Build list of all words in each side of the range
        $file1words = array();
        for ($index = $changerange->file1start; $index < $changerange->file1start + $changerange->file1count; $index++) {
            foreach ($lines1[$index]->words as $word) {
                $file1words[] = $word;
            }
        }
        $file2words = array();
        for ($index = $changerange->file2start; $index < $changerange->file2start + $changerange->file2count; $index++) {
            foreach ($lines2[$index]->words as $word) {
                $file2words[] = $word;
            }
        }

        // Make arrays 1-based
        array_unshift($file1words, 'dummy');
        unset($file1words[0]);
        array_unshift($file2words, 'dummy');
        unset($file2words[0]);

        // Convert word lists into plain strings
        $file1strings = array();
        foreach ($file1words as $index => $word) {
            $file1strings[$index] = $word->word;
        }
        $file2strings = array();
        foreach ($file2words as $index => $word) {
            $file2strings[$index] = $word->word;
        }

        // Run diff on strings
        $worddiff = ouwiki_diff($file1strings, $file2strings);
        foreach ($worddiff->adds as $index) {
            $added[] = $file2words[$index];
        }
        foreach ($worddiff->deletes as $index) {
            $deleted[] = $file1words[$index];
        }
        foreach ($worddiff->changes as $changerange) {
            for ($index = $changerange->file1start;
                $index < $changerange->file1start + $changerange->file1count; $index++) {
                $deleted[] = $file1words[$index];
            }
            for ($index = $changerange->file2start;
                $index < $changerange->file2start + $changerange->file2count; $index++) {
                $added[] = $file2words[$index];
            }
        }
    }

    return array($deleted, $added);
}

/**
 * Runs diff and interprets results into ouwiki_changes object.
 * @param array $file1 Array of lines in file 1. The first line in the file
 *   MUST BE INDEX 1 NOT ZERO!!
 * @param array $file2 Array of lines in file 2, again starting from 1.
 * @return ouwiki_changes Object describing changes
 */
function ouwiki_diff($file1, $file2) {
    return new ouwiki_changes(ouwiki_diff_internal($file1, $file2), count($file2));
}

/**
 * Adds HTML span elements to $html around the words listed in $words.
 * @param string $html HTML content
 * @param array $words Array of ouwiki_word to mark
 * @param string $markerclass Name of class for span element
 * @return HTML with markup added
 */
function ouwiki_diff_add_markers($html, $words, $markerclass, $beforetext, $aftertext) {
    // Sort words by start position
    usort($words, create_function('$a,$b', 'return $a->start-$b->start;'));

    // Add marker for each word. We use an odd tag name which will
    // be replaced by span later, this for ease of replacing
    $spanstart = "<ouwiki_diff_add_markers>";
    $pos = 0;
    $result = '';
    foreach ($words as $word) {
        // Add everything up to the word
        $result .= substr($html, $pos, $word->start - $pos);
        // Add word
        $result .= $spanstart.$word->word.'</ouwiki_diff_add_markers>';
        // Update position
        $pos = $word->start + strlen($word->word);
    }

    // Add everything after last word
    $result .= substr($html, $pos);

    // If we end a marker then immediately start one, get rid of
    // both the end and start
    $result = preg_replace('^</ouwiki_diff_add_markers>(\s*)<ouwiki_diff_add_markers>^', '$1', $result);

    // Turn markers into proper span
    $result = preg_replace('^<ouwiki_diff_add_markers>^', $beforetext.'<span class="'.$markerclass.'">', $result);
    $result = preg_replace('^</ouwiki_diff_add_markers>^', '</span>'.$aftertext, $result);

    return $result;
}

/**
 * Compares two HTML files. (This is the main function that everything else supports.)
 * @param string $html1 XHTML for file 1
 * @param string $html2 XHTML for file 2
 * @return array ($result1,$result2,$changes); result1 and result2 are XHTML
 *   to be displayed indicating the differences, while $changes is the total
 *   number of changed (added or deleted) words. May be 0 if there are no
 *   differences or if differences are only to HTML
 */
function ouwiki_diff_html($html1, $html2) {
    global $OUTPUT;

    $lines1 = ouwiki_diff_html_to_lines($html1);
    $lines2 = ouwiki_diff_html_to_lines($html2);
    list($deleted, $added) = ouwiki_diff_words($lines1, $lines2);
    $changes = count($deleted) + count($added);
    $result1 = ouwiki_diff_add_markers($html1, $deleted, 'ouw_deleted',
        '<img src="'.$OUTPUT->image_url('diff_deleted_begins', 'ouwiki').'" alt="'.get_string('deletedbegins', 'ouwiki').'" />',
        '<img src="'.$OUTPUT->image_url('diff_added_ends', 'ouwiki').'" alt="'.get_string('deletedends', 'ouwiki').'" />');
    $result2 = ouwiki_diff_add_markers($html2, $added, 'ouw_added',
        '<img src="'.$OUTPUT->image_url('diff_added_begins', 'ouwiki').'" alt="'.get_string('addedbegins', 'ouwiki').'" />',
        '<img src="'.$OUTPUT->image_url('diff_added_ends', 'ouwiki').'" alt="'.get_string('addedends', 'ouwiki').'" />');
    return array($result1, $result2, $changes);
}

// compare attachments between versions

function ouwiki_diff_attachments($a1, $a2, $modcontextid, $p1versionid, $p2versionid) {
    global $PAGE;
    $ouwikioutput = $PAGE->get_renderer('mod_ouwiki');

    $changes = 0;
    if (empty($a1) && empty($a2)) {
        // no attachments exist in either version
        $result1 = get_string('noattachments', 'ouwiki');
        $result2 = get_string('noattachments', 'ouwiki');
    } else if (!empty($a1) && empty($a2)) {
        // all attachments removed in version 2; only in version 1
        $result1 = '<ul>';
        foreach ($a1 as $file) {
            $result1 .= $ouwikioutput->ouwiki_print_attachment_diff($file, 'delete');
        }
        $result1 .= '</ul>';
        $result2 = get_string('noattachments', 'ouwiki');
        $changes = count($a1);
    } else if (empty($a1) && !empty($a2)) {
        // attachments all added by version 2; none in version 1
        $result2 = '<ul>';
        foreach ($a2 as $file) {
            $result2 .= $ouwikioutput->ouwiki_print_attachment_diff($file, 'add');
        }
        $result2 .= '</ul>';
        $result1 = get_string('noattachments', 'ouwiki');
        $changes = count($a2);
    } else { // compare all
        $changes = 0;
        $result1 = '<ul>';
        $result2 = '<ul>';

        // help speed it up by keeping hashes
        $f1hashes = array();
        $f2hashes = array();

        // start with in both
        foreach ($a1 as $f1) {
            $f1hash = $f1->get_contenthash();
            $f1hashes[$f1hash] = $f1hash;
            foreach ($a2 as $f2) {
                $f2hash = $f2->get_contenthash();
                $f2hashes[$f2hash] = $f2hash;
                if ($f1hash === $f2hash) {
                    $result1 .= $ouwikioutput->ouwiki_print_attachment_diff($f1);
                    $result2 .= $ouwikioutput->ouwiki_print_attachment_diff($f2);
                }
            }
        }

        // check for new files in version 2
        foreach ($a2 as $f2) {
            $f2hash = $f2->get_contenthash();
            if (!array_key_exists($f2hash, $f1hashes)) {
                $result2 .= $ouwikioutput->ouwiki_print_attachment_diff($f2, 'add');
                $changes++;
            }
        }

        // check for deleted files in version 1
        foreach ($a1 as $f1) {
            $f1hash = $f1->get_contenthash();
            if (!array_key_exists($f1hash, $f2hashes)) {
                $result1 .= $ouwikioutput->ouwiki_print_attachment_diff($f1, 'delete');
                $changes++;
            }
        }
        $result1 .= '</ul>';
        $result2 .= '</ul>';
    }

    return array($result1, $result2, $changes);
}
