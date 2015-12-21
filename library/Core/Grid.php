<?php

namespace Core;

use \Core\Grid\Export;

/**
 * Datagrid
 *
 * @author Administrator
 */
class Grid
{

    /**
     * @var string html id used by grid
     */
    protected $id;

    /**
     * @var Grid\Source\ISource source of the grid
     */
    protected $source;

    /**
     * @var bool if the grid will show headers
     */
    protected $showHeaders = true;

    /**
     * @var bool if the grid will filter results
     */
    protected $showFilter = true;
    protected $hasFilter = false;

    /**
     * @var boolean if the grid should order results
     */
    protected $hasOrdering;

    /**
     * @var boolean if the grid should export results
     */
    protected $showExport = true;

    /**
     * @var boolean path for templates
     */
    protected $templatePath = 'templates/';

    /**
     * @var boolean template used by grid
     */
    protected $template = 'grid';

    /**
     * @var array columns used by grid
     */
    protected $columns = array();

    /**
     * @var array actions used by grid
     */
    protected $actions = array();
    protected $showActions = true;

    /**
     * @var array Mass Actions used by grid
     */
    protected $massActions;
    protected $hasMassActions = false;

    /**
     * @var boolean if the grid should show massactions
     */
    protected $showMassActions = true;

    /**
     * @var bool if the grid was builded
     */
    private $builded = false;

    /**
     * @var Pager pager used by grid
     */
    protected $pager;
    protected $hasPager = false;

    /**
     * @var boolean if the grid should paginate results
     */
    protected $showPager = true;

    /**
     * @var int number of the actual page
     */
    protected $page;

    /**
     * @var Zend_Controller_Request_Abstract The http request
     */
    protected $request;

    /**
     * @var string order'colum of the grid config
     */
    protected $orderCol;

    /**
     * @var string order's direction of the grid config
     */
    protected $orderDirection;

    /**
     * Filters list
     *
     * @var array
     */
    protected $filters = array();

    /**
     * @var \Zend_Session
     */
    protected $session;

    /**
     * @var mixed index name of the PK
     */
    protected $pkIndex = 'id';

    /**
     * Form metadata and attributes
     * @var array
     */
    protected $attribs = array();

    /**
     * Form button action
     */
    protected $buttonForm;

    /**
     * Guarda id em input hidden
     */
    protected $hiddenId;

    /**
     * Constructor of the class
     * @param Core\Grid\Source\ISource $source
     * @param array $options 
     */
    public function __construct(\Core\Grid\Source\ISource $source = null, array $options = array())
    {
        if ($source)
            $this->setSource($source);

        if (isset($options))
            $this->setOptions($options);

        $this->setRequest(\Zend_Controller_Front::getInstance()->getRequest());
    }
    
    /**
     * Set grid state from options array
     *
     * @param  array $options
     * @return Grid
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            //forbiden options
            if (in_array($method, array()))
                if (!is_object($value))
                    continue;

            if (method_exists($this, $method))
            // Setter exists; use it
                $this->$method($value);
            else
                throw new Grid\Exception("Unknown option {$method}");
        }
        return $this;
    }

    /**
     * Sets the source of the grid
     * @param \Core\Grid\Source\ISource $source
     * @return Grid 
     */
    public function setSource(\Core\Grid\Source\ISource $source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * gets source
     * @return Grid\Source\ISource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set form attribute
     *
     * @param  string $key
     * @param  mixed $value
     * @return Zend_Form
     */
    public function setAttrib($key, $value)
    {
        $key = (string) $key;
        $this->attribs[$key] = $value;
        return $this;
    }

    /**
     * Add multiple form attributes at once
     *
     * @param  array $attribs
     * @return Zend_Form
     */
    public function addAttribs(array $attribs)
    {
        foreach ($attribs as $key => $value) {
            $this->setAttrib($key, $value);
        }
        return $this;
    }

    /**
     * Set multiple form attributes at once
     *
     * Overwrites any previously set attributes.
     *
     * @param  array $attribs
     * @return Zend_Form
     */
    public function setAttribs(array $attribs)
    {
        $this->clearAttribs();
        return $this->addAttribs($attribs);
    }

    /**
     * Retrieve a single form attribute
     *
     * @param  string $key
     * @return mixed
     */
    public function getAttrib($key)
    {
        $key = (string) $key;
        if (!isset($this->attribs[$key])) {
            return null;
        }

        return $this->attribs[$key];
    }

    /**
     * Retrieve all form attributes/metadata
     *
     * @return array
     */
    public function getAttribs()
    {
        return $this->attribs;
    }

    /**
     * Remove attribute
     *
     * @param  string $key
     * @return bool
     */
    public function removeAttrib($key)
    {
        if (isset($this->attribs[$key])) {
            unset($this->attribs[$key]);
            return true;
        }

        return false;
    }

    /**
     * Clear all form attributes
     *
     * @return Zend_Form
     */
    public function clearAttribs()
    {
        $this->attribs = array();
        return $this;
    }

    /**
     * Returns HTML id of the grid
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets HTML id of the grid
     * @param string $id
     * @return Grid 
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Add a new column
     *
     * $column may be either an array column options, or an object of type
     * Grid\Column. 
     *
     * @param  array|Grid\Column $column
     * @throws Core\Grid\Exception on invalid element
     * @return Grid
     */
    public function addColumn($column)
    {
        if (is_array($column)) {
            $options = $column;

            if (null === $options['index'])
                throw new Grid\Exception('Columns specified by array must have an accompanying index');

            $this->columns[] = $this->createColumn($options);
        } elseif ($column instanceof Grid\Column) {

            if (null === $column->getIndex())
                throw new Grid\Exception('Columns must have an accompanying index');

            $this->columns[$column->getIndex()] = $element;
        } else {
            throw new Grid\Exception('Column must be specified by array options or Core\Grid\Column instance');
        }

        return $this;
    }

    /**
     * Create a column
     *
     * Acts as a factory for creating column. Columns created with this
     * method will not be attached to the grid, but will contain column
     * settings as specified in the grid object (including plugin render
     * ordering, filter, etc.).
     *
     * @param  string $type
     * @param  string $name
     * @param  array $options
     * @return Grid\Column
     */
    public function createColumn(array $options)
    {
        $column = new Grid\Column($options);
        return $column;
    }

    /**
     * Returns the columns of the grid
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Adds an user action to the grid
     * @return Grid 
     */
    public function addAction($action)
    {
        if (is_array($action))
            $action = new \Core\Grid\Action($action);
        elseif ($action instanceof \Core\Grid\Action)
            $action = $action;
        else
            throw new \Exception('Invalid action param');

        $this->actions[] = $action;
        return $this;
    }

    /**
     * returns actions
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * returns only actions that attends to self condition
     * @param array $row
     * @return array
     */
    public function getActionsByRow(array $row)
    {
        $tmpActions = array();
        $actions = $this->getActions();
        foreach ($actions as $action)
            if ($action->attendToRowCondition($row))
                $tmpActions[] = $action;
        return $tmpActions;
    }

    public function getShowActions()
    {
        return (count($this->getActions()) && $this->showActions);
    }

    public function setShowActions($option)
    {
        $this->showActions = $option;
        return $this;
    }

    /**
     * @param mixed $buttonForm
     */
    public function setButtonForm($buttonForm)
    {
        $this->buttonForm = $buttonForm;
    }

    /**
     * @return mixed
     */
    public function getShowButtonForm()
    {
        if (!is_null($this->buttonForm)) {
            return $this->buttonForm;
        }
    }

    /**
     * @return mixed
     */
    public function getHiddenId()
    {
        return $this->hiddenId;
    }

    /**
     * @param mixed $hiddenId
     */
    public function setHiddenId($hiddenId)
    {
        $this->hiddenId = $hiddenId;
    }

    /**
     * sets the template to be used
     * @param string $template template name
     * @return Grid 
     */
    public function setTemplate($template)
    {
        $this->template = (string) $template;
        return $this;
    }

    /**
     * Returns if the grid uses filter
     * @return bool
     */
    public function getShowFilter()
    {
        return ($this->showFilter && $this->hasFilter);
    }

    /**
     * Sets if the grid uses filter
     * @param bool $filtered 
     * @return Grid
     */
    public function setShowFilter($filtered)
    {
        $this->showFilter = (bool) $filtered;
        return $this;
    }

    /**
     * Checks if grid has ordering
     * @return type 
     */
    public function getHasOrdering()
    {
        return $this->hasOrdering;
    }

    /**
     * Sets order for the grid
     * 
     * @param string $column String with an index column
     * @return \Core\Grid 
     */
    public function setHasOrdering($boolean)
    {
        $this->hasOrdering = (bool) $boolean;
        return $this;
    }

    /**
     * Sets the name of the column to order the grid
     * 
     * @param string $column String with an index column
     * @return \Core\Grid 
     */
    public function setOrderCol($column = null)
    {
        $this->orderCol = $column;
        return $this;
    }

    /**
     * return the column to order
     * @return array
     */
    public function getOrderCol()
    {
        return $this->orderCol;
    }

    /**
     * Direction of the column's order
     * 
     * @param string $direction
     * @return \Core\Grid 
     */
    public function setOrderDirection($direction = 'ASC')
    {
        $this->orderDirection = $direction;
        return $this;
    }

    /**
     * return the direction of the order
     * @return array
     */
    public function getOrderDirection()
    {
        return $this->orderDirection;
    }

    /**
     * Returns if the grid can export results
     * @return bool
     */
    public function getShowExport()
    {
        return $this->showExport;
    }

    /**
     * just an alias for $this->getShowExport()
     * @return bool
     */
    public function showExport()
    {
        return $this->getShowExport();
    }

    /**
     * Sets if the can exports results
     * @param bool $ordered 
     * @return Grid
     */
    public function setShowExport($canExport)
    {
        $this->showExport = (bool) $canExport;
        return $this;
    }

    /**
     * returns the pager used by grid
     * @return Core\Grid\Pager; 
     */
    public function getPager()
    {
        return $this->pager;
    }

    /**
     * sets the pager
     * @param \Core\Grid\Pager $pager
     * @return Grid 
     */
    public function setPager(\Core\Grid\Pager $pager)
    {
        $this->pager = $pager;
        $this->hasPager = true;
        return $this;
    }

    /**
     * Returns the actual page
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Sets the actual page
     * @return int
     */
    public function setPage($page)
    {
        $this->page = (int) $page;
        return $this;
    }

    /**
     * Sets if shows the pager
     * @param bool $paginated 
     * @return Grid
     */
    public function setShowPager($value)
    {
        $this->showPager = (bool) $value;
        return $this;
    }

    /**
     * Returns if shows the pager
     * @return bool
     */
    public function getShowPager()
    {
        return ($this->showPager && $this->hasPager);
    }

    /**
     * sets if the grid uses mass actions
     * @param type $useMassActions 
     * @return Grid
     */
    public function setShowMassActions($value)
    {
        $this->showMassActions = (bool) $value;
        return $this;
    }

    public function getShowMassActions()
    {
        return ($this->showMassActions && $this->hasMassActions);
    }

    public function addMassActions(array $actions)
    {
        foreach ($actions as $name => $label) {
            $this->addMassAction($name, $label);
        }
    }

    /**
     *
     * @param type $name
     * @param type $label
     */
    public function addMassAction($name, $label)
    {
        $this->hasMassActions = true;
        $this->massActions[$name] = $label;
        return $this;
    }

    public function removeMassAction($name)
    {
        unset($this->massAction[$name]);

        if (!count($this->massAction)) {
            $this->hasMassActions = false;
        }
    }

    public function getMassActions()
    {
        return $this->massActions;
    }

    /**
     * get the template path
     * @return string
     */
    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    /**
     * sets the template path
     * @param string $templatePath 
     * @return Grid
     */
    public function setTemplatePath($templatePath)
    {
        $this->templatePath = (string) $templatePath;
        return $this;
    }

    /**
     * sets if the grid shows headers
     * @param bool $value
     * @return Grid 
     */
    public function setShowHeaders($value)
    {
        $this->showHeaders = (bool) $value;
        return $this;
    }

    /**
     * returns if the grid shows headers
     * @return bool
     */
    public function getShowHeaders()
    {
        return $this->showHeaders;
    }

    /**
     * just an alias for Grid::getShowHeaders()
     * @return bool 
     */
    public function showHeaders()
    {
        return $this->getShowHeaders();
    }

    /**
     * Returns the HTML output 
     * @param string $name name of the template
     * @return string
     */
    public function render($name = NULL)
    {
        if ($this->builded === false)
            $this->build();

        $view = new \Zend_View;
        $view->setBasePath(APPLICATION_PATH . '/' . $this->getTemplatePath());
        $view->setScriptPath(APPLICATION_PATH . '/' . $this->getTemplatePath());
        $view->grid = $this;
        return $view->render('grid.phtml');
    }

    /**
     * The user request
     * @return Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Sets the request object
     * @param \Zend_Controller_Request_Abstract $request
     * @return Grid 
     */
    public function setRequest(\Zend_Controller_Request_Abstract $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * sets number of results of the grid
     * @param type $total
     * @return Grid 
     */
    public function setNumResults($total)
    {
        $this->numResults = (int) $total;
        return $this;
    }

    /**
     * return the number of results of the grid
     * @return int 
     */
    public function getNumResults()
    {
        return $this->numResults;
    }

    protected function getFilters()
    {
        return $this->filters;
    }

    /**
     * count records on source - this aux the pager
     * @return Grid 
     */
    private function countRecords()
    {
        $total = count($this->getResult());
        $this->setNumResults($total);
        $this->counted = true;
        return $this;
    }

    /**
     * Process the pager 
     * @return Grid 
     */
    private function processPager()
    {
        if (!$this->counted)
            throw new Exception('You need do count the records before build the pager. Use Grid::countRecords()');

        // if page don't exists
        if (null == $this->getPage()) {
            //gets the page by request
            $page = $this->getRequest()->getParam('page');
            $this->setPage($page);
        }

        if (null === $this->getPager()) {
            //get default pager
            $pager = new \Core\Grid\Pager($this->getNumResults(), $this->getPage());
            $this->setPager($pager);
        } else {
            //get user defined pager
            $pager = $this->getPager();
        }

        $result = $this->getResult();
        $resultFiltered = array();

        if (count($result)) {
            foreach ($result as $key => $row)
                if (($key >= $pager->getOffset()) && ($key < $pager->getMaxset()))
                    array_push($resultFiltered, $row);
        }

        //seto resultado filtrado pela ordenacao para a grid 
        $this->result = $resultFiltered;

        //$this->getSource()
        //->setLimit($pager->getMaxPerPage())
        //->setOffset($pager->getOffset());

        $this->hasPager = true;
        return $this;
    }

    /**
     * Sort an array based in a Assoc index
     * 
     * @param array $data Array to be sorted
     * @param string $field Field to be used to sort
     * @param string $direction Direction to be done ASC | DESC
     * @return array 
     */
    private function orderBy($data, $field, $direction = 'ASC')
    {
        //verifico se tenho datas para converter para strings
        $code = "if (is_object(\$a['$field']) && (get_class(\$a['$field']) == 'DateTime')) \$a['$field'] = \$a['$field']->format('Ymd'); ";
        $code .= "if (is_object(\$b['$field']) && (get_class(\$b['$field']) == 'DateTime')) \$b['$field'] = \$b['$field']->format('Ymd'); ";
        //ordenacao
        $code .= "return strnatcmp(\$a['$field'], \$b['$field']);";
        usort($data, create_function('$a,$b', $code));

        return ($direction == 'DESC') ? array_reverse($data) : $data;
    }

    /**
     * 
     */
    private function processColumnOrder()
    {
        //colunas
        $columns = $this->getColumns();
        //parametros
        $params = $this->getRequest()->getParams();

        //checo se tenho ordenacao
        if (!$this->getHasOrdering()) {
            $blnOrdering = false;
            //caso alguma coluna definida seto grid com ordenacao
            foreach ($columns as $column) {
                if ($column->hasOrdering() === true)
                    $blnOrdernig = true;
                else
                    $column->setHasOrdering(false);
            }

            if (!$blnOrdering)
                return $this;

            $this->setHasOrdering($blnOrdering);
        }

        // remover ordernacao
        if (isset($params['grid']['removeOrder'])) {
            //removo a sessao
            unset($this->session->ordering);
        }

        //checo coluna selecionada via session ou parametros
        $colOrderSes = $this->session->ordering['colOrder'];
        $dirOrderSes = $this->session->ordering['dirOrder'];
        $colOrder = ($colOrderSes) ? $colOrderSes : false;
        $dirOrder = ($dirOrderSes) ? $dirOrderSes : false;
        $colOrder = (isset($params['grid']['colOrder'])) ? $params['grid']['colOrder'] : $colOrder;
        $dirOrder = (isset($params['grid']['dirOrder'])) ? $params['grid']['dirOrder'] : $dirOrder;

        //checo colunas com ordering
        foreach ($columns as $column) {
            if ($colOrder == $column->getIndex()) {
                //ordenacao da grid
                $this->setOrderCol($colOrder);
                $this->setOrderDirection($dirOrder);
                //nova ordenacao para a coluna
                $column->setDirOrder((($dirOrder == 'ASC') ? 'DESC' : 'ASC'));
            }
        }

        //defino colunas nulas com ordenacao
        foreach ($columns as $column) {
            if ($column->hasOrdering() === null)
                $column->setHasOrdering(true);
        }
    }

    /**
     * Process the order
     * @return Grid 
     */
    private function processOrder()
    {
        //filtro resultados
        $result = $this->result;

        //processar coluna
        $this->processColumnOrder();

        //caso exista coluna definida
        if ($this->getOrderCol()) {
            //ordeno
            $result = $this->orderBy($result, $this->getOrderCol(), $this->getOrderDirection());
            //gravo na sessao
            $this->session->ordering['colOrder'] = $this->getOrderCol();
            $this->session->ordering['dirOrder'] = $this->getOrderDirection();
        }
        //result ordenado
        $this->result = $result;

        return $this;
    }

    /**
     * Process the filters of columns and set grid with or not
     * @return Grid 
     */
    private function processColumnFilters()
    {
        $columns = $this->getColumns();

        foreach ($columns as $column) {
            // caso coluna tenha filtro
            if ($column->hasFilter())
            // seto grid com filtro
                $this->setHasFilter(true);
        }

        return $this;
    }

    /**
     * Build user defined filters
     *
     * @return Grid
     */
    private function processFilters()
    {
        //processo filtros das colunas
        $this->processColumnFilters();

        // pego parametros enviados
        $params = $this->getRequest()->getParams();

        // remover filtros
        if (isset($params['grid']['removeFilter'])) {
            //removo a sessao
            unset($this->session->filters);
            return $this;
        }

        //adicionar filtros validos, limpo sessao
        if (isset($params['grid']['addFilter']))
            unset($this->session->filters);

        //caso tenha filtros na sessao
        if (is_array($this->session->filters))
            $params['grid'] = $this->session->filters;

        // caso nao haja nada da grid ignoro
        if (!isset($params['grid']['filter']))
            return $this;

        foreach ($params['grid']['filter'] AS $key => $value) {
            if (is_array($value))
                foreach ($value as $subKey => $subVal) {
                    if (!empty($subVal))
                        $this->filters[$key][$subKey] = $subVal;
                }
            elseif (!empty($value))
                $this->filters[$key] = $value;
        }

        // add filtros a sessao
        $this->session->filters = $params['grid'];

        //filtro resultados
        $this->result = $this->processResultFilters($this->result);

        return $this;
    }

    /**
     * Checa as condicoes dos valores do campo com a da culuna baseados na condicao
     * 
     * @param string $condition
     * @param string $fieldVal
     * @param string $filterVal
     * @return boolean 
     */
    private function gridFiltersConditions($condition, $fieldVal, $filterVal)
    {
        switch ($condition) {
            case '>=':
                if (\Core\Util\Number::toInt($fieldVal) >= \Core\Util\Number::toInt($filterVal))
                    return true;
                break;
            case '<=':
                if (\Core\Util\Number::toInt($fieldVal) <= \Core\Util\Number::toInt($filterVal))
                    return true;
                break;
            case '>':
                if (\Core\Util\Number::toInt($fieldVal) > \Core\Util\Number::toInt($filterVal))
                    return true;
                break;
            case '<':
                if (\Core\Util\Number::toInt($fieldVal) < \Core\Util\Number::toInt($filterVal))
                    return true;
                break;
            case 'fulltext':
                //procuro pela palavra no em qlqr posicao da string
                if (stristr($fieldVal, $filterVal))
                    return true;
                break;
            case '=':
                //procuro pela palavra exatamente igual
                if ($fieldVal == $filterVal)
                    return true;
                break;
        }

        return false;
    }

    /**
     * Metodo para processar filtros enviados
     * 
     * @param array $result array com resultados do Source
     */
    private function processResultFilters(array $result)
    {
        $filters = $this->getFilters();

        //sem filtros retorno result
        if (count($filters) == 0)
            return $result;

        //resultados filtrados
        $resultFiltered = array();

        // colunas da grid
        $columns = $this->getColumns();

        //loop nos resultados
        foreach ($result as $key => $row) {
            $blnFilter = true;

            //loop nas colunas
            foreach ($columns as $column) {
                //pego filtro
                $objFilter = $column->getFilter();

                //caso n tenha filtro para a coluna ignoro
                if (!$objFilter)
                    continue;

                $objRender = $objFilter->getRender();

                //pego informacoes 
                $range = $objRender->getRange();
                $conditions = $objRender->getCondition();

                // guardar valores padroes dos campos
                if ($range) {
                    //loop nos filtros
                    foreach ($filters as $keyCond => $fieldFilter) {
                        //nenhum elemento em array enviado
                        if (!is_array($fieldFilter))
                            continue;

                        // loop nos campos do tipo do filtro
                        foreach ($fieldFilter as $field => $value) {
                            foreach ($conditions['range'] as $typeCond => $condition)
                            // caso filtro com mesmo nome da coluna
                                if (($field == $column->getIndex()) && ($keyCond == $typeCond))
                                    $column->getFilter()->getRender()->setAttributeValue("value[{$typeCond}]", $value);
                        }
                    }
                } else {
                    //loop nos filtros
                    foreach ($filters as $filter => $value)
                    //caso filtro com mesmo nome da coluna
                        if ($filter == $column->getIndex())
                        //populo valor padrao
                            $column->getFilter()->getRender()->setAttributeValue('value', $value);
                }

                // caso ja setado como falso ignoro 
                if (!$blnFilter)
                    continue;

                // dentro de um range
                if ($range) {
                    //loop nos filtros
                    foreach ($filters as $keyCond => $fieldFilter) {
                        //nenhum elemento em array enviado
                        if (!is_array($fieldFilter))
                            continue;

                        // loop nos campos do tipo do filtro
                        foreach ($fieldFilter as $field => $value) {
                            // caso filtro n tenha valor nem comparo
                            if (empty($value))
                                continue;

                            foreach ($conditions['range'] as $typeCond => $condition) {
                                // caso ja setado como falso ignoro 
                                if (!$blnFilter)
                                    continue;

                                // caso tipo de condicao diferente da condicao do range nao comparo
                                if ($keyCond != $typeCond)
                                    continue;

                                // caso filtro com mesmo nome da coluna
                                if ($field == $column->getIndex()) {
                                    //valor da linha
                                    $rowValue = $row[$field];

                                    //tratamento quando sao tipo date
                                    if (is_object($rowValue)) {
                                        if (get_class($rowValue) == 'DateTime') {
                                            $rowValue = $rowValue->format('Ymd');
                                            $value = \Core\Util\Date::fromBRtoNumber($value);
                                        }
                                    }

                                    $blnFilter = $this->gridFiltersConditions($condition, $rowValue, $value);
                                }
                            }
                        }
                    }
                    // valor especifico
                } else {
                    //loop nos filtros
                    foreach ($filters as $filter => $value) {
                        //elemento em array enviado
                        if (is_array($value))
                            continue;

                        //caso filtro com mesmo nome da coluna
                        if ($filter == $column->getIndex()) {

                            //valor da linha
                            $rowValue = $row[$filter];

                            //tratamento quando sao tipo date
                            if (is_object($rowValue)) {
                                if (get_class($rowValue) == 'DateTime') {
                                    $rowValue = $rowValue->format('Ymd');
                                    $value = \Core\Util\Date::fromBRtoNumber($value);
                                }
                            }

                            foreach ($conditions['match'] as $condition) {
                                $blnFilter = $this->gridFiltersConditions($condition, $rowValue, $value);
                            }
                        }
                    }
                }
            }
            //posso adicionar
            if ($blnFilter)
                array_push($resultFiltered, $row);
        }

        return $resultFiltered;
    }

    /**
     * 
     */
    private function processSource()
    {
        // busco resultados
        $this->result = $this->getSource()->execute();
        $this->sourceProcessed = true;

        return $this;
    }

    public function getResult()
    {
        if (!$this->sourceProcessed)
            throw new \Exception('You must build the grid before get the result. Use Grid::build()');

        return $this->result;
    }

    /**
     * Deploys
     *
     * @return Grid
     */
    final public function build()
    {
        if ($this->getSource() === null)
            throw new \Exception('Please specify your source');

        if (count($this->getColumns()) == 0)
            throw new \Exception('No columns to show');

        //set up session of the grid
        $this->session = new \Zend_Session_Namespace('grid');

        // generate the grid
        $this->processSource()
                ->processFilters()
                ->processOrder();

        // pego parametros enviados
        $params = $this->getRequest()->getParams();
        $view = \Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');

        // remover filtros
        if (isset($params['grid']['export']) && !empty($params['grid']['export'])) {

            $title = $this->getAttrib('title');
            if (isset($title) || (!is_null($title)) | (!empty($title))) {
                $title = 'Grid';
            }
            switch ($params['grid']['export']) {
                case 'pdf':
                    Export\Pdf::render($this, $title);
                    break;
                case 'csv':
                    Export\Csv::render($this, $title);
                    break;
                case 'xml':
                    Export\Xml::render($this, $title);
                    break;
                default:
                    throw new \Exception('The option to export is not a valid one.');
                    break;
            }

            \Zend_Layout::getMvcInstance()->disableLayout(true);
            \Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        }

        // generate the grid
        $this->countRecords()
                ->processPager();

        return $this;
    }

    public function getPkIndex()
    {
        return $this->pkIndex;
    }

    /**
     *
     * @param type $pkIndex 
     */
    public function setPkIndex($pkIndex)
    {
        $this->pkIndex = $pkIndex;
    }

    /**
     * String representation of grid
     * Proxies to {@link render()}.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (\Exception $e) {
            trigger_error($e->getMessage());
            return '';
        }
    }

    /**
     *
     * @param type $hasFilter 
     */
    public function setHasFilter($hasFilter)
    {
        $this->hasFilter = (bool) $hasFilter;
    }

}
