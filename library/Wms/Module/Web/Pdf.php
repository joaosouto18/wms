<?php

namespace Wms\Module\Web;

/**
 * Description of Pdf
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Pdf extends \Core\Pdf
{

    /**
     * @var int 
     */
    private $labelHeight;

    /**
     * @var array
     */
    private $labels;

    /**
     * @var int 
     */
    private $colHeight;

    /**
     * @var array
     */
    private $cols;

    /**
     * @var int
     */
    private $numRows;

    /**
     * @var array
     */
    private $subtotal = false;

    /**
     * @var string 
     */
    private $title = '';
    /**
     * @var string 
     */
    private $subTitle = '';


    /**
     *
     * @param int $value
     * @return \Wms\Module\Web\Pdf 
     */
    public function setLabelHeight($value)
    {
        $this->labelHeight = $value;
        return $this;
    }

    /**
     * @param int $key Unique key that identify the column an 
     * @param int $w
     * @param string $txt
     * @param int $border
     * @param int $ln
     * @param string $align
     * @param string $fill
     * @param string $link
     * @return \Wms\Module\Web\Pdf 
     */
    public function addLabel($key, $w, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        $this->labels[] = array(
            'key' => $key,
            'width' => $w,
            'height' => $this->labelHeight,
            'txt' => $txt,
            'border' => $border,
            'ln' => $ln,
            'align' => $align,
            'fill' => $fill,
            'link' => $link,
        );

        return $this;
    }

    /**
     *
     * @param int $value
     * @return \Wms\Module\Web\Pdf 
     */
    public function setColHeight($value)
    {
        $this->colHeight = $value;
        return $this;
    }

    /**
     * @param int $key
     * @param int $w
     * @param string $txt
     * @param int $border
     * @param int $ln
     * @param string $align
     * @param string $fill
     * @param string $link
     * @param boolean $subtotal
     * @return \Wms\Module\Web\Pdf 
     */
    public function addCol($key, $w, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '', $subtotal = false)
    {
        $this->cols[] = array(
            'key' => $key,
            'width' => $w,
            'height' => $this->colHeight,
            'txt' => $txt,
            'border' => $border,
            'ln' => $ln,
            'align' => $align,
            'fill' => $fill,
            'link' => $link,
            'subtotal' => $subtotal,
        );

        return $this;
    }

    /**
     * 
     */
    public function render()
    {
        $this->setFont('Arial', '', 9);

        $this->a = 0;
        $labels = $this->getLabels();
        $cols = $this->getCols();

        if (count($cols) > 0) {
            foreach ($cols as $col) {

                $w = ($col['width']) ? : $labels[$col['index']]['width'];

                $this->cell($col['width'], $col['height'], $col['txt'], $col['border'], $col['ln'], $col['align'], $col['fill'], $col['link']);

                if ($col['subtotal'])
                    $this->subtotal[$col['key']] += $col['txt'];

                $this->a += (int) $col['txt'];
            }
        }

        return $this;
    }

    public function getLabels()
    {
        return $this->labels;
    }

    public function getCols()
    {
        return $this->cols;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title, $isUTF8 = false)
    {
        $this->title = $title;
        return $this;
    }

    public function getNumRows()
    {
        return $this->numRows;
    }

    public function getTextNumRows()
    {
        return ($this->getNumRows()) ? 'Total de ' . $this->getNumRows() . ' registro(s) encontrado(s).' : '';
    }

    /**
     *
     * @param mixed $numRows
     * @return \Wms\Module\Web\Pdf 
     */
    public function setNumRows($numRows)
    {
        $this->numRows = $numRows;
        return $this;
    }

    /**
     * Page header 
     */
    public function Header()
    {
        $lineWidth = ($this->DefOrientation == 'P') ? 195 : 280;
        $titleWidth = $lineWidth - 75;

        // Logo
        $this->Image(APPLICATION_PATH . '/../public/img/admin/logoRelatorio.png', 10, 6, 30);

        $data = new \DateTime;
        $user = \Zend_Auth::getInstance()->getIdentity();

        $this->setFont('Arial', 'B', 15);
        $this->cell(40);
        $this->cell($titleWidth, 5, $this->getTitle(), 0, 0, 'L');
        $this->setFont('Arial', '', 9);
        $this->cell(35, 5, 'Data: ' . $data->format('d/m/Y'), 0, 1, 'R');
        $this->cell(40);
        $this->cell($titleWidth, 5, $this->getTextNumRows(), 0, 0, 'R');
        $this->cell(35, 5, 'Hora: ' . $data->format('H:m:s'), 0, 1, 'R');
        // Line break
        $this->cell($lineWidth, 3, '', 'B', 1);
        $this->cell($lineWidth, 3, '', 0, 1);

        // header

        $this->setFont('Arial', 'B', 10);

        $labels = $this->getLabels();

        if (count($labels)) {
            foreach ($labels as $label)
                $this->cell($label['width'], $label['height'], $label['txt'], $label['border'], $label['ln'], $label['align'], $label['fill'], $label['link']);
        }

        $this->cell($lineWidth, 2, '', 0, 1);
    }

    /**
     * Page footer 
     */
    public function Footer()
    {
        $lineWidth = ($this->DefOrientation == 'P') ? 195 : 280;

        if ($this->subtotal) {

            // pagination
            $this->setFont('Arial', 'B', 9);
            $this->setFillColor(211, 211, 211);
            $this->cell($lineWidth, 2, '', 0, 1);

            $labels = $this->getLabels();

            if (count($labels)) {

                foreach ($labels as $col) {
                    $this->cell(1, $col['height']);
                    $this->cell($col['width'] - 1, $col['height'] + 2, $this->subtotal[$col['key']], '', $col['ln'], $col['align'], 1, $col['link']);
                }
            }
        }

        // pagination
        $this->setFont('Arial', '', 9);

        // Position at 1.5 cm from bottom
        $this->setY(-15);
        // Arial italic 8
        $this->setFont('Arial', 'I', 8);
        // Page number
        $this->cell(0, 10, 'Page ' . $this->pageNo() . '/{nb}' . $this->aliasNbPages(), 0, 0, 'C');
    }

}
