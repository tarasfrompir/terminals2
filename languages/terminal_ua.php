<?php
/**
 * Ukranian language file
 *
 * @package MajorDoMo
 * @author Serge Dzheigalo <jey@tut.by> http://smartliving.ru/
 * @version 1.0
 */


$dictionary = array (

    
    /* start array for convert number to string */
    
	'NUMBER_TO_STRING_1TEN' => array( array('','один','два','три','чотири','п`ять','шість','сім', 'вісім','дев`ять'), array('','одна','дві','три','чотири','п`ять','шість','сім', 'вісім','дев`ять')),
	'NUMBER_TO_STRING_2TEN' => array('десять','одиннадцять','дванадцять','тринадцять','чотирнадцять' ,'п`ятнадцять','шістнадцять','сімнадцять','вісімнадцять','дев`ятнадцять'),
	'NUMBER_TO_STRING_TENS' => array(2=>'двадцять','тридцять','сорок','п`ятьдесят','шістдесят','сімдесят' ,'вісімдесят','дев`яносто'),
	'NUMBER_TO_STRING_HUNDRED' => array('','сто','двісті','триста','чоториста','п`ятсот','шістьсот', 'сімсот','вісімсот','дев`ятсот'),
	'NUMBER_TO_STRING_UNIT' => array(array('десята' ,'десятих' , 1), array(' ' ,' ціла'   ,'цілих ' ,0),array('тисяча'  ,'тисячі'  ,'тисяч'     ,1), array('мільйон' ,'мільйона','мільйонів' ,0), array('мілльярд','мільярда','мільярдів',0)),
	'NUMBER_TO_STRING_NULL' => 'нуль',
	
    /* end array for convert number to string  */

);

foreach ($dictionary as $k => $v) {
    if (!defined('LANG_' . $k)) {
        define('LANG_' . $k, $v);
    }
}
