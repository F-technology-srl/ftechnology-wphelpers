<?php

namespace Ftechnology\WPHelpers;

use DateTime;
use Exception;

class Utils
{

    /**
     * Convert php array to php object
     * @param $array
     * @return mixed
     */
    public static function vec2obj($array)
    {
        return json_decode(json_encode($array));
    }


    /**
     * @param $obj
     * @param bool $print
     * @param bool $abs
     * @param int $zIndex
     * @param bool $height
     * @return bool|string
     */
    public static function debug($obj, $print = true, $abs = false, $zIndex = 9999, $height = false)
    {
        $styleAbs = '';
        if ($abs) {
            $styleAbs = '; position:fixed; z-index:' . $zIndex . '; width: 100%; height: 95vh; overflow-y: scroll; overflow-x: hidden; bottom: 0; left: 0; opacity:0.9';
        }

        $extraStyle = '';
        if ($height) {
            $extraStyle = '; height:' . $height . 'px';
        }
        $openPre = '<pre class="pre_debug" style="margin:0; padding:4px; font-size: 12px; background: #cccc77; color: black; font-family: Arial' . $styleAbs . $extraStyle . '" class="hidden-xs">';

        if ($print) {
            echo $openPre;
            print_r($obj);
            echo '</pre>';
        } else {
            ob_start();
            var_dump($obj);
            $result = $openPre . ob_get_clean() . '</pre>';
            return $result;
        }
        return true;
    }


    /**
     * Convert seconds to duration ISO8601
     * @param $second
     * @return string
     */
    public static function secondsToDurationISO8601($second)
    {
        $h = intval($second / 3600);
        $m = intval(($second - $h * 3600) / 60);
        $s = $second - ($h * 3600 + $m * 60);
        $ret = 'PT';
        if ($h)
            $ret .= $h . 'H';
        if ($m)
            $ret .= $m . 'M';
        if ((!$h && !$m) || $s)
            $ret .= $s . 'S';
        return $ret;
    }


    /**
     * @param $str
     * @return array
     */
    public static function parseDuration($str)
    {
        $result = array();
        preg_match('/^(?:P)([^T]*)(?:T)?(.*)?$/', trim($str), $sections);
        if (!empty($sections[1])) {
            preg_match_all('/(\d+)([YMWD])/', $sections[1], $parts, PREG_SET_ORDER);
            $units = array('Y' => 'years', 'M' => 'months', 'W' => 'weeks', 'D' => 'days');
            foreach ($parts as $part) {
                $result[$units[$part[2]]] = $part[1];
            }
        }
        if (!empty($sections[2])) {
            preg_match_all('/(\d+)([HMS])/', $sections[2], $parts, PREG_SET_ORDER);
            $units = array('H' => 'hours', 'M' => 'minutes', 'S' => 'seconds');
            foreach ($parts as $part) {
                $result[$units[$part[2]]] = $part[1];
            }
        }
        return ($result);
    }


    /**
     * @param $html
     * @param $subject
     * @param bool $mailTo
     * @param string $mailFromName
     * @param string $mailFrom
     * @return bool
     */
    public static function sendMailLog($html, $subject, $mailTo = false, $mailFromName = '', $mailFrom = '')
    {
        if($html AND $subject AND $mailTo){
            try {
                $headers = 'MIME-Version: 1.0' . "\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "Content-Transfer-Encoding: base64\r\n";
                $headers .= "To: " . $mailTo . "\r\n";
                $headers .= "From: " . $mailFromName . " <" . $mailFrom . ">\r\n";

                $text_mail = '<html lang="it-IT"><body>' . $html . '</body></html>';
                $text_mail = chunk_split(base64_encode($text_mail));
                mail($mailTo, $subject, $text_mail, $headers);
                return true;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }


    /**
    /**
     * @param bool|string $dateFrom
     * @param $dateTo
     * @param $type
     * @return float|int|string|null
     */
    public static function getTotalInterval($type, $dateFrom, $dateTo = false)
    {
        if(!$dateTo){
            $dateTo = date_create(date("c"));
        }else{
            $dateTo = date_create($dateTo);
        }

        $dateFrom = date_create($dateFrom);
        $interval = date_diff($dateTo, $dateFrom);

        switch ($type) {
            case 'years':
                return $interval->format('%r%Y');
                break;
            case 'months':
                $years = $interval->format('%r%Y');
                $months = 0;
                if ($years) {
                    $months += $years * 12;
                }
                $months += $interval->format('%r%m');
                return $months;
                break;
            case 'days':
                return $interval->format('%r%a');
                break;
            case 'hours':
                $days = $interval->format('%r%a');
                $hours = 0;
                if ($days) {
                    $hours += 24 * $days;
                }
                $hours += $interval->format('%r%H');
                return $hours;
                break;
            case 'minutes':
                $days = $interval->format('%r%a');
                $minutes = 0;
                if ($days) {
                    $minutes += 24 * 60 * $days;
                }
                $hours = $interval->format('%r%H');
                if ($hours) {
                    $minutes += 60 * $hours;
                }
                $minutes += $interval->format('%r%i');
                return $minutes;
                break;
            case 'seconds':
                $days = $interval->format('%r%a');
                $seconds = 0;
                if ($days) {
                    $seconds += 24 * 60 * 60 * $days;
                }
                $hours = $interval->format('%r%H');
                if ($hours) {
                    $seconds += 60 * 60 * $hours;
                }
                $minutes = $interval->format('%r%i');
                if ($minutes) {
                    $seconds += 60 * $minutes;
                }
                $seconds += $interval->format('%r%s');
                return $seconds;
                break;
            case 'milliseconds':
                $days = $interval->format('%r%a');
                $seconds = 0;
                if ($days) {
                    $seconds += 24 * 60 * 60 * $days;
                }
                $hours = $interval->format('%r%H');
                if ($hours) {
                    $seconds += 60 * 60 * $hours;
                }
                $minutes = $interval->format('%r%i');
                if ($minutes) {
                    $seconds += 60 * $minutes;
                }
                $seconds += $interval->format('%r%s');
                $milliseconds = $seconds * 1000;
                return $milliseconds;
                break;
            default:
                return NULL;
        }
    }


    /**
     * @param $str
     * @param int $start
     * @param int $words
     * @param string $suffix
     * @return string
     */
    public static function customWordCutString($str, $start = 0, $words = 15, $suffix = '...')
    {
        $arr = preg_split("/[\s]+/", $str, $words + 1);
        $arr = array_slice($arr, $start, $words);
        $arrJoin = join(' ', $arr) . $suffix . " ** " . $start . ' * ' . $words;
        return $arrJoin;
    }


    /**
     * @param $date
     * @param string $format
     * @return bool
     */
    public static function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        if($d){
            return $d && $d->format($format) === $date;
        }
        return false;
    }

    /**
     * @param $date
     * @param string $currentFormat
     * @param string $newFormat
     * @return string
     */
    public static function convertDateFormat($date, $currentFormat = 'd/m/Y', $newFormat = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($currentFormat, $date);
        if($d){
            return $d->format($newFormat);
        }
        return false;
    }

    /**
     * @param $file string
     * @param $start string
     * @param $end string
     * @return array|bool
     */
    public static function getTextFilePortion($file, $start = false, $end = false)
    {

        $linesOfFile = file($file, FILE_IGNORE_NEW_LINES);
        if (is_array($linesOfFile)) {

            $indStart = array_search($start, $linesOfFile);
            $indEnd = array_search($end, $linesOfFile);

            if ($indStart !== false AND $indEnd !== false) {

                $newVec = array_slice($linesOfFile, $indStart, $indEnd + 1);
                $newVec = array_filter($newVec);

                if (is_array($newVec)) {
                    $vecBefore = array_slice($linesOfFile, 0, $indStart);
                    $vecAfter = array_slice($linesOfFile, $indEnd + 1, count($linesOfFile));
                    return [$vecBefore, $newVec, $vecAfter];
                }
            } else {
                // Creo la sezione
                return [$linesOfFile, [$start, $end], []];
            }
        }
        return false;

    }

	public static function getPagination() {

		global $wp_query;

		$big = 999999999;

		$argsPagination = array(
				'base'               => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
				'format'             => '?paged=%#%',
				'total'              => $wp_query->max_num_pages,
				'current'            => max( 1, get_query_var( 'paged' ) ),
				'prev_next'          => true,
				'prev_text'          => __( '&laquo;' ),
				'next_text'          => __( '&raquo;' ),
				'add_fragment'       => '',
				'before_page_number' => '',
				'after_page_number'  => '',
				'type'               => 'array',
		);

		return $pagination = paginate_links( $argsPagination );

	}
}

class Sort_Posts
{
    var $order, $orderby;

    function __construct($orderby, $order)
    {
        $this->orderby = $orderby;
        $this->order = ('desc' == strtolower($order)) ? 'DESC' : 'ASC';
    }

    function sort($a, $b)
    {
        if ($a->{$this->orderby} == $b->{$this->orderby}) {
            return 0;
        }

        if ($a->{$this->orderby} < $b->{$this->orderby}) {
            return ('ASC' == $this->order) ? -1 : 1;
        } else {
            return ('ASC' == $this->order) ? 1 : -1;
        }
    }
}