<?php
/**
 * Russian language file
 *
 * @package MajorDoMo
 * @author Serge Dzheigalo <jey@tut.by> http://smartliving.ru/
 * @version 1.0
 */


$dictionary = array(

    /* start array for convert number to string */
    
	'NUMBER_TO_STRING_1TEN' => array(array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'), array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять')),
	'NUMBER_TO_STRING_2TEN' => array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать'),
	'NUMBER_TO_STRING_TENS' => array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто'),
	'NUMBER_TO_STRING_HUNDRED' => array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот'),
	'NUMBER_TO_STRING_UNIT' => array(array('десятая' ,'десятых' ,	 1), array(' '   ,' целая'   ,'целых '    ,0), array('тысяча'  ,'тысячи'  ,'тысяч'     ,1), array('миллион' ,'миллиона','миллионов' ,0), array('миллиард','милиарда','миллиардов',0)),
	'NUMBER_TO_STRING_NULL' => 'ноль',
	
    /* end array for convert number to string  */

);

foreach ($dictionary as $k => $v) {
    if (!defined('LANG_' . $k)) {
        @define('LANG_' . $k, $v);
    }
}
