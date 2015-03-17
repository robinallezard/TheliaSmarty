<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace TheliaSmarty\Template\Plugins;

use Thelia\Core\HttpFoundation\Request;
use TheliaSmarty\Template\AbstractSmartyPlugin;
use TheliaSmarty\Template\Exception\SmartyPluginException;
use TheliaSmarty\Template\SmartyPluginDescriptor;
use Thelia\Tools\DateTimeFormat;
use Thelia\Tools\MoneyFormat;
use Thelia\Tools\NumberFormat;

/**
 *
 * format_date and format_date smarty function.
 *
 * Class Format
 * @package Thelia\Core\Template\Smarty\Plugins
 * @author Manuel Raynaud <manu@thelia.net>
 * @author Benjamin Perche <benjamin@thelia.net>
 */
class Format extends AbstractSmartyPlugin
{
    private static $dateKeys = ["day", "month", "year"];
    private static $timeKeys = ["hour", "minute", "second"];

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * return date in expected format
     *
     * available parameters :
     *  date => DateTime object (mandatory)
     *  format => expected format
     *  output => list of default system format. Values available :
     *      date => date format
     *      time => time format
     *      datetime => datetime format (default)
     *
     * ex :
     *  {format_date date=$dateTimeObject format="Y-m-d H:i:s"} will output the format with specific format
     *  {format_date date=$dateTimeObject format="%e %B %Y" locale="fr_FR"} will output the format with specific format (see strftime() function)
     *  {format_date date=$dateTimeObject output="date"} will output the date using the default date system format
     *  {format_date date=$dateTimeObject} will output with the default datetime system format
     *
     * @param  array                                                  $params
     * @param  null                                                   $template
     * @throws \TheliaSmarty\Template\Exception\SmartyPluginException
     * @return string
     */
    public function formatDate($params, $template = null)
    {
        $date = $this->getParam($params, "date", false);

        if ($date === false) {
            // Check if we have a timestamp
            $timestamp = $this->getParam($params, "timestamp", false);

            if ($timestamp === false) {
                // No timestamp => error
                throw new SmartyPluginException("Either date or timestamp is a mandatory parameter in format_date function");
            } else {
                $date = new \DateTime();
                $date->setTimestamp($timestamp);
            }
        } elseif (is_array($date)) {
            $keys = array_keys($date);

            $isDate = $this->arrayContains(static::$dateKeys, $keys);
            $isTime = $this->arrayContains(static::$timeKeys, $keys);

            // If this is not a date, fallback on today
            // If this is not a time, fallback on midnight
            $dateFormat = $isDate ? sprintf("%d-%d-%d", $date["year"], $date["month"], $date["day"]) : (new \DateTime())->format("Y-m-d");
            $timeFormat = $isTime ? sprintf("%d:%d:%d", $date["hour"], $date["minute"], $date["second"]) : "0:0:0";

            $date = new \DateTime(sprintf("%s %s", $dateFormat, $timeFormat));
        }

        if (!($date instanceof \DateTime)) {
            try {
                $date = new \DateTime($date);
            } catch (\Exception $e) {
                return "";
            }
        }

        $format = $this->getParam($params, "format", false);

        if ($format === false) {
            $format = DateTimeFormat::getInstance($this->request)->getFormat($this->getParam($params, "output", null));
        }

        $locale = $this->getParam($params, 'locale', false);

        if (false === $locale) {
            $value = $date->format($format);
        } else {
            $value = $this->formatDateWithLocale($date, $locale, $format);
        }

        return $value;
    }

    private function formatDateWithLocale(\DateTime $date, $locale, $format)
    {
        if (function_exists('setlocale')) {
            // Save the current locale
            $systemLocale = setlocale(LC_TIME, 0);
            setlocale(LC_TIME, $locale);
            $localizedDate =  strftime($format, $date->getTimestamp());
            // Restore the locale
            setlocale(LC_TIME, $systemLocale);

            return $localizedDate;
        } else {
            // setlocale() function not available => error
            throw new SmartyPluginException("The setlocale() function is not available on your system.");
        }
    }

    /**
     *
     * display numbers in expected format
     *
     * available parameters :
     *  number => int or float number
     *  decimals => how many decimals format expected
     *  dec_point => separator for the decimal point
     *  thousands_sep => thousands separator
     *
     *  ex : {format_number number="1246.12" decimals="1" dec_point="," thousands_sep=" "} will output "1 246,1"
     *
     * @param $params
     * @param  null                                                   $template
     * @throws \TheliaSmarty\Template\Exception\SmartyPluginException
     * @return string                                                 the expected number formatted
     */
    public function formatNumber($params, $template = null)
    {
        $number = $this->getParam($params, "number", false);

        if ($number ===  false || $number === '') {
            return "";
        }

        return NumberFormat::getInstance($this->request)->format(
            $number,
            $this->getParam($params, "decimals", null),
            $this->getParam($params, "dec_point", null),
            $this->getParam($params, "thousands_sep", null)
        );
    }
    /**
     *
     * display a amount in expected format
     *
     * available parameters :
     *  number => int or float number
     *  decimals => how many decimals format expected
     *  dec_point => separator for the decimal point
     *  thousands_sep => thousands separator
     *  symbol => Currency symbol
     *
     *  ex : {format_money number="1246.12" decimals="1" dec_point="," thousands_sep=" " symbol="€"} will output "1 246,1 €"
     *
     * @param $params
     * @param  null                                                   $template
     * @throws \TheliaSmarty\Template\Exception\SmartyPluginException
     * @return string                                                 the expected number formatted
     */
    public function formatMoney($params, $template = null)
    {
        $number = $this->getParam($params, "number", false);

        if ($number ===  false || $number === '') {
            return "";
        }

        return MoneyFormat::getInstance($this->request)->format(
            $number,
            $this->getParam($params, "decimals", null),
            $this->getParam($params, "dec_point", null),
            $this->getParam($params, "thousands_sep", null),
            $this->getParam($params, "symbol", null)
        );
    }

    protected function arrayContains(array $expected, array $hayStack)
    {
        foreach ($expected as $value) {
            if (!in_array($value, $hayStack)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return SmartyPluginDescriptor[]
     */
    public function getPluginDescriptors()
    {
        return array(
            new SmartyPluginDescriptor("function", "format_date", $this, "formatDate"),
            new SmartyPluginDescriptor("function", "format_number", $this, "formatNumber"),
            new SmartyPluginDescriptor("function", "format_money", $this, "formatMoney"),
        );
    }
}
