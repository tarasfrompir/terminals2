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
    
    /* start array for convert date to string */
    
	'DATE_TO_STRING_THOUSANDS' => array(1 => 'одна тысяча', 2 => 'две тысячи',),
	'DATE_TO_STRING_HUNDREDS' => array(0 => '', 9 => 'девятьсот',),
	'DATE_TO_STRING_DAYS' => array(1 => 'первое',2 => 'второе',3 => 'третье',4 => 'четвертое',5 => 'пятое',6 => 'шестое',7 => 'седьмое',8 => 'восьмое',9 => 'девятое',10 => 'десятое',11 => 'одиннадцатое',12 => 'двенадцатое',13 => 'тринадцатое',14 => 'четырнадцатое',15 => 'пятнадцатое',16 => 'шестнадцатое',17 => 'семнадцатое',18 => 'восемнадцатое',19 => 'девятнадцатое',20 => 'двадцатое',30 => 'тридцатое',40 => 'сороковое',),
	'NUMBER_TO_STRING_MONTH' => array(0 => 'нулября',1 => 'января',2 => 'февраля',3 => 'марта',4 => 'апреля',5 => 'мая',6 => 'июня',7 => 'июля',8 => 'августа',9 => 'сентября',10 => 'октября',11 => 'ноября',12 => 'декабря',),
	
    /* end array for convert date to string  */

);

foreach ($dictionary as $k => $v) {
    if (!defined('LANG_' . $k)) {
        @define('LANG_' . $k, $v);
    }
}