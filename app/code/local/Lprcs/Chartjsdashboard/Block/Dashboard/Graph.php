<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2019 Magento, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml dashboard google chart block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */

class Lprcs_Chartjsdashboard_Block_Dashboard_Graph extends Mage_Adminhtml_Block_Dashboard_Graph
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('lprcs/dashboard/graph.phtml');
    }

    /**
     * Get tab template
     *
     * @return string
     */
    protected function _getTabTemplate()
    {
        return 'lprcs/dashboard/graph.phtml';
    }

    /**
     * Chart width
     *
     * @var string
     */
    protected $_width = "1000";

    /**
     * Chart height
     *
     * @var string
     */
    protected $_height = '350';

    /**
     * Get chart info
     *
     * @return array
     */
    public function getChartData()
    {
        $this->_allSeries = $this->getRowsData($this->_dataRows);

        foreach ($this->_axisMaps as $axis => $attr){
            $this->setAxisLabels($axis, $this->getRowsData($attr, true));
        }

        $timezoneLocal = Mage::app()->getStore()->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);

        [$dateStart, $dateEnd] = Mage::getResourceModel('reports/order_collection')
            ->getDateRange($this->getDataHelper()->getParam('period'), '', '', true);

        $dateStart->setTimezone($timezoneLocal);
        $dateEnd->setTimezone($timezoneLocal);

        $dates = array();
        $datas = array();

        while($dateStart->compare($dateEnd) < 0){
            switch ($this->getDataHelper()->getParam('period')) {
                case '24h':
                    $d = $dateStart->toString('yyyy-MM-dd HH:00');
                    $dateStart->addHour(1);
                    break;
                case '7d':
                case '1m':
                    $d = $dateStart->toString('yyyy-MM-dd');
                    $dateStart->addDay(1);
                    break;
                case '1y':
                case '2y':
                    $d = $dateStart->toString('yyyy-MM');
                    $dateStart->addMonth(1);
                    break;
            }
            foreach ($this->getAllSeries() as $index=>$serie) {
                if (in_array($d, $this->_axisLabels['x'])) {
                    $datas[$index][] = (float)array_shift($this->_allSeries[$index]);
                } else {
                    $datas[$index][] = 0;
                }
            }
            $dates[] = $d;
        }

        $this->_axisLabels['x'] = $dates;
        $this->_allSeries = $datas;

        // process each string in the array, and find the max length
        foreach ($this->getAllSeries() as $index => $serie) {
            $localmaxlength[$index] = count($serie);
            $localmaxvalue[$index] = max($serie);
            $localminvalue[$index] = min($serie);
        }

        if (is_numeric($this->_max)) {
            $maxvalue = $this->_max;
        } else {
            $maxvalue = max($localmaxvalue);
        }
        if (is_numeric($this->_min)) {
            $minvalue = $this->_min;
        } else {
            $minvalue = min($localminvalue);
        }

        // default values
        $yrange = 0;
        $yLabels = array();
        $miny = 0;
        $maxy = 0;

        if ($minvalue >= 0 && $maxvalue >= 0) {
            $miny = 0;
            if ($maxvalue > 10) {
                $p = 10 ** $this->_getPow($maxvalue);
                $maxy = (ceil($maxvalue/$p))*$p;
                $yLabels = range($miny, $maxy, $p);
            } else {
                $maxy = ceil($maxvalue+1);
                $yLabels = range($miny, $maxy, 1);
            }
            $yrange = $maxy;
        }


        if (count($this->_axisLabels) > 0) {
            foreach ($this->_axisLabels as $idx=>$labels){
                if ($idx === 'x') {
                    /**
                     * Format date
                     */
                    foreach ($this->_axisLabels[$idx] as $_index=>$_label) {
                        if ($_label != '') {
                            switch ($this->getDataHelper()->getParam('period')) {
                                case '24h':
                                    $this->_axisLabels[$idx][$_index] = $this->formatTime(
                                        new Zend_Date($_label, 'yyyy-MM-dd HH:00'), 'short', false
                                    );
                                    break;
                                case '7d':
                                case '1m':
                                    $this->_axisLabels[$idx][$_index] = $this->formatDate(
                                        new Zend_Date($_label, 'yyyy-MM-dd')
                                    );
                                    break;
                                case '1y':
                                case '2y':
                                    $formats = Mage::app()->getLocale()->getTranslationList('datetime');
                                    $format = isset($formats['yyMM']) ? $formats['yyMM'] : 'MM/yyyy';
                                    $format = str_replace(array("yyyy", "yy", "MM"), array("Y", "y", "m"), $format);
                                    $this->_axisLabels[$idx][$_index] = date($format, strtotime($_label));
                                    break;
                            }
                        } else {
                            $this->_axisLabels[$idx][$_index] = '';
                        }
                    }
                }
            }
        };

        $chartData['datas'] = $datas;
        $chartData['dates'] = $dates;
        $chartData['serie'] = $serie;
        $chartData['yLabels'] = $yLabels;
        $chartData['yRange'] = $yrange;
        $chartData['attr'] = $attr;
        $chartData['axisMaps'] = $this->_axisMaps;
        $chartData['axisLabels'] = $this->_axisLabels;
        $chartData['dataRow'] = $this->_dataRows;
        $chartData['allSeries'] = $this->_allSeries;

        return $chartData;
    }

}
