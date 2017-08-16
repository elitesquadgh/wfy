<?php

/**
 * A sub class of the ReportController class which reads XML descriptions of reports
 * and automatically generates reports in various supported formats. The schema
 * for the report XML files can be found in /lib/rapi/rapi.xsd.
 *
 * @author James Ekow Abaka Ainooson <jainooson@gmail.com>
 * @ingroup Controllers
 */
class XmlDefinedReportController extends ReportController {

    /**
     * An XML datafile which contains a description of the report and it's
     * fields.
     * @var SimpleXMLElement
     */
    protected $xml;

    /**
     * An instance of the logo which is displayed on this report. This instance
     * is kept so that it could be repeated when new pages are being added to
     * the reports.
     * 
     * @var LogoContent
     */
    public $logo;
    protected $tableConditions;

    /**
     * An instance of the label which is displayed on this report. This instance
     * is kept so that it could be repeated when new pages are being added to
     * the reports.
     *
     * @var TextContent
     */
    public $label;

    /**
     * Base Package
     */
    private $basePackage;

    /**
     * Loads an XML file and set's up the class to generate reports based on
     * the description of the class.
     * @param string $report A path to the xml file which contains the
     *                       description of this report
     */
    public function __construct($report, $redirected = false) 
    {
        parent::__construct();
        $this->xml = simplexml_load_file(($redirected ? "" : "app/modules") . $report);
        $path = $this->xml["name"] . "/generate/pdf";
        $this->name = (string) $this->xml["name"];
        $this->label = $this->xml["label"];
        Application::setTitle($this->label);

        $baseModel = $this->xml["baseModel"];
        $this->basePackage = isset($this->xml["basePackage"]) ? (string) $this->xml["basePackage"] : reset(explode(".", (string) $this->xml["baseModel"]));

        try {
            $baseModel = Model::load((string) $baseModel);
        } catch (Exception $e) {
            throw new Exception("Base model (" . (string) $baseModel . ") could not be loaded ({$e->getMessage()})");
        }
        $this->referencedFields = array();

        foreach ($baseModel->referencedFields as $field) {
            $this->referencedFields[] = $field["referencing_field"];
        }
    }
    
    private function generateLogoSection($reader, $report)
    {
        $reader->moveToAttribute("class");
        $class = $reader->value;
        $logo = $class == '' ? new LogoContent() : new $class();
        $report->add($logo);
        $report->logo = $logo;        
    }
    
    private function generateTextSection($reader, $report)
    {
        $reader->moveToAttribute("style");
        $style = $reader->value;
        $reader->read();
        $text = $reader->value;
        $report->add(new TextContent($text, $style));        
    }
    
    private function generateTableSection($reader, $report)
    {
        $reader->moveToAttribute("name");
        $name = $reader->value;

        $tableConditionsArray = array();
        if ($reader->moveToAttribute("conditions")) {
            $tableConditionsArray[] = $reader->value;
        }
        if ($this->tableConditions != '') {
            $tableConditionsArray[] = $this->tableConditions;
        }

        $tableConditions = implode(" AND ", $tableConditionsArray);

        $fields = $this->xml->xpath("/rapi:report/rapi:table[@name='$name']/rapi:fields/rapi:field");
        $headers = $this->xml->xpath("/rapi:report/rapi:table[@name='$name']/rapi:fields/rapi:field[@label!='']/@label");
        $dontJoins = $this->xml->xpath("/rapi:report/rapi:table[@name='$name']/rapi:dont_join/rapi:pair");

        $ignoredFields = array();
        $dataParams["total"] = array();
        $hardCodedSorting = array();
        $reportGroupingFields = array();

        $models = array();
        $fieldInfos = array();

        // Generate filter conditions
        $filters = array();
        $filterSummaries = array();
        $keyOffset = 0;
        foreach ($fields as $key => $field) 
        {
            // Load the model for this field if it hasn't been
            // loaded already. I have a hunch that this check
            // is really not necessary since the model loader
            // sort of caches loaded models now.
            $modelInfo = Model::resolvePath((string) $field);
            if (array_search($modelInfo["model"], array_keys($models)) === false) {
                $models[$modelInfo["model"]] = Model::load($modelInfo["model"]);
            }

            $model = $models[$modelInfo["model"]];
            $fieldInfo = reset($model->getFields(array($modelInfo["field"])));
            $fieldInfos[(string) $field] = $fieldInfo;

            //Ignore fields which are not needed.
            if (isset($_REQUEST[$name . "_" . $fieldInfo["name"] . "_ignore"])) 
            {
                $ignoredFields[] = $key;
            }

            if (isset($field["sort"])) 
            {
                $sortField = "{$model->database}.{$fieldInfo["name"]}";
                $hardCodedSorting[] = array("field" => $sortField, "type" => $field["sort"]);
            }

            $tableHeaders[] = (string) $field["label"];
            switch ($fieldInfo["type"]) {
                case "integer":
                    $dataParams["type"][] = "number";
                    break;
                case "double":
                    $dataParams["type"][] = "double";
                    break;
                default:
                    $dataParams["type"][] = "";
            }
            $dataParams["total"][] = $field["total"] == "true" ? true : false;

            $fields[$key] = (string) $field;
            $value = $field["value"];
            $field = (string) $field;

            if (array_search($model->getKeyField(), $this->referencedFields) === false || $fieldInfo["type"] == "double" || $fieldInfo["type"] == "date") 
            {
                if ($value != null) 
                {
                    $filters[] = "{$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}='$value'";
                    continue;
                }

                switch ($fieldInfo["type"]) {
                    case "string":
                    case "text":
                        if ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"] != "") {
                            switch ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_option"]) {
                                case "CONTAINS":
                                    $filterSummaries[] = "{$headers[$key]} containing {$_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"]}";
                                    $filters[] = $models[$modelInfo["model"]]->getSearch($models[$modelInfo["model"]]->escape($_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"]), "{$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}");
                                    break;

                                case "EXACTLY";
                                    $filterSummaries[] = "{$headers[$key]} being exactly {$_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"]}";
                                    $filters[] = "{$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}='" . $models[$modelInfo["model"]]->escape($_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"]) . "'";
                                    break;
                            }
                        }
                        break;

                    case "integer":
                    case "double":
                        if ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_value"] != "") {
                            switch ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_option"]) {
                                case "EQUALS":
                                    $filterSummaries[] = "{$headers[$key]} equals {$_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_value"]}";
                                    $filters[] = "{$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}='" . $models[$modelInfo["model"]]->escape($_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_value"]) . "'";
                                    break;
                                case "GREATER":
                                    $filterSummaries[] = "{$headers[$key]} greater than {$_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_value"]}";
                                    $filters[] = "{$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}>'" . $models[$modelInfo["model"]]->escape($_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_value"]) . "'";
                                    break;
                                case "LESS":
                                    $filterSummaries[] = "{$headers[$key]} less than {$_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_value"]}";
                                    $filters[] = "{$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}<'" . $models[$modelInfo["model"]]->escape($_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_value"]) . "'";
                                    break;
                                case "BETWEEN":
                                    $filterSummaries[] = "{$headers[$key]} between {$_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_value"]} and {$_REQUEST[$name . "_" . $fieldInfo["name"] . "_end_value"]}";
                                    $filters[] = "({$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}>='" . $models[$modelInfo["model"]]->escape($_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_value"]) . "' AND {$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}<='" . $models[$modelInfo["model"]]->escape($_REQUEST[$name . "_" . $fieldInfo["name"] . "_end_value"]) . "')";
                                    break;
                            }
                        }
                        break;

                    case "reference":
                        break;

                    case "datetime":
                    case "date":
                        if ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_date"] != "") {
                            switch ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_option"]) {
                                case "EQUALS":
                                    $filterSummaries[] = "{$headers[$key]} on {$_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_date"]}";
                                    $filters[] = "{$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}='" . $models[$modelInfo["model"]]->escape(Utils::stringToDatabaseDate($_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_date"])) . "'";
                                    break;
                                case "GREATER":
                                    $filterSummaries[] = "{$headers[$key]} after {$_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_date"]}";
                                    $filters[] = "{$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}>'" . $models[$modelInfo["model"]]->escape(Utils::stringToDatabaseDate($_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_date"])) . "'";
                                    break;
                                case "LESS":
                                    $filterSummaries[] = "{$headers[$key]} before {$_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_date"]}";
                                    $filters[] = "{$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}<'" . $models[$modelInfo["model"]]->escape(Utils::stringToDatabaseDate($_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_date"])) . "'";
                                    break;
                                case "BETWEEN":
                                    $filterSummaries[] = "{$headers[$key]} from {$_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_date"]} to {$_REQUEST[$name . "_" . $fieldInfo["name"] . "_end_date"]}";
                                    $filters[] = "({$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}>='" . $models[$modelInfo["model"]]->escape(Utils::stringToDatabaseDate($_REQUEST[$name . "_" . $fieldInfo["name"] . "_start_date"])) . "' AND {$models[$modelInfo["model"]]->getDatabase()}.{$fieldInfo["name"]}<='" . $models[$modelInfo["model"]]->escape(Utils::stringToDatabaseDate($_REQUEST[$name . "_" . $fieldInfo["name"] . "_end_date"])) . "')";
                                    break;
                            }
                        }
                        break;

                    case "enum":
                        if (count($_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"]) >= 1 && $_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"][0] != ""/* $_REQUEST[$name."_".$fieldInfo["name"]."_value"] != "" */) {
                            $m = $models[$modelInfo["model"]];
                            if ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_option"] == "INCLUDE") {
                                $summary = array();
                                foreach ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"] as $value) {
                                    $summary[] = $fieldInfo["options"][$value];
                                }
                                $filterSummaries[] = "{$headers[$key]} being " . implode(", ", $summary);

                                $condition = array();
                                foreach ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"] as $value) {
                                    if ($value != "")
                                        $condition[] = "{$m->getDatabase()}.{$fieldInfo["name"]}='" . $m->escape($value) . "'";
                                }
                            }
                            else if ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_option"] == "EXCLUDE") {
                                $summary = array();
                                foreach ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"] as $value) {
                                    $summary[] = $fieldInfo["options"][$value];
                                }
                                $filterSummaries[] = "{$headers[$key]} excluding " . implode(", ", $summary);

                                $condition = array();
                                foreach ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"] as $value) {
                                    if ($value != "")
                                        $condition[] = "{$m->getDatabase()}.{$fieldInfo["name"]}<>'" . $m->escape($value) . "'";
                                }
                            }
                            if (count($condition) > 0)
                                $filters[] = "(" . implode(" OR ", $condition) . ")";
                        }
                        break;
                }
            }
            else 
            {
                if ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"] != "") 
                {
                    if ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_option"] == "IS_ANY_OF") 
                    {
                        foreach ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"] as $value) 
                        {
                            if ($value != "")
                                $condition[] = "{$model->getDatabase()}.{$fieldInfo["name"]}='" . $model->escape($value) . "'";
                        }
                    }
                    else if ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_option"] == "IS_NONE_OF") 
                    {
                        foreach ($_REQUEST[$name . "_" . $fieldInfo["name"] . "_value"] as $value) 
                        {
                            if ($value != "")
                                $condition[] = "{$model->getDatabase()}.{$fieldInfo["name"]}<>'" . $model->escape($value) . "'";
                        }
                    }
                    if (count($condition) > 0)
                        $filters[] = "(" . implode(" OR ", $condition) . ")";
                }
            }
        }

        // Generate the various tables taking into consideration grouping
        if (count($filterSummaries) > 0) 
        {
            $report->filterSummary = new TextContent(str_replace("\\n", " ", implode("\n", $filterSummaries)), 'summary');
            $report->add($report->filterSummary);
        }

        $params = array
        (
            "fields" => $fields,
            "conditions" => implode(" AND ", $filters),
            "headers" => $tableHeaders,
            "dont_join" => array(),
            'total' => $dataParams['total'],
            'type' => $dataParams['type']
        );

        foreach($dontJoins as $pair)
        {
            $params['dont_join'][] = (string)$pair;
        }               

        if ($tableConditions != "") {
            $params["conditions"] = $params['conditions'] . ($params['conditions'] != '' ? " AND " : '') . "($tableConditions)";
        }

        if ($_REQUEST[$name . "_sorting"] != "") {
            array_unshift(
                $hardCodedSorting, array
                (
                    "field" => $_REQUEST[$name . "_sorting"],
                    "type" => $_REQUEST[$name . "_sorting_direction"]
                )
            );
        }

        if (is_array($_REQUEST[$name . "_grouping"])) {
            foreach ($_REQUEST[$name . "_grouping"] as $postGrouping) {
                if ($postGrouping != "") {
                    $groupingFields = explode(",", $postGrouping);
                    foreach ($groupingFields as $key => $groupingField) {
                        $modelInfo = Model::resolvePath($groupingField);
                        $model = Model::load($modelInfo["model"]);
                        $groupingFields[$key] = "{$model->database}.{$modelInfo["field"]}";
                    }
                    $reportGroupingFields[] = array
                        (
                        "field" => $model->datastore->concatenate($groupingFields),
                        "type" => "ASC"
                    );
                }
            }
            $hardCodedSorting = array_merge($reportGroupingFields, $hardCodedSorting);
        }

        $params["sort_field"] = $hardCodedSorting;
        if ($_REQUEST[$name . "_limit"] != '') {
            $params['limit'] = $_REQUEST[$name . "_limit"];
        }
        $params["no_num_formatting"] = true;
        $this->reportData = SQLDBDataStore::getMulti($params, SQLDatabaseModel::MODE_ARRAY);

        unset($params["sort_field"]);
        $wparams = $params;
        $wparams["global_functions"] = array("LENGTH", "MAX");
        $wparams["global_functions_set"] = true;
        $this->widths = reset(SQLDBDataStore::getMulti($wparams, SQLDatabaseModel::MODE_ARRAY));
        
        foreach ($tableHeaders as $i => $header) {
            foreach (explode("\\n", $header) as $line) {
                if (strlen($line) / 2 > $this->widths[$i]) {
                    $this->widths[$i] = strlen($line) / 2;
                }
            }
        }
        
        $params['ignored_fields'] = $ignoredFields;
        
        if ($_REQUEST[$name . "_grouping"][0] == "") 
        {
            $tableDetails = array(
                'headers' => $headers,
                'data' => $this->reportData, 
                'params' => $params, 
                'totals' =>true
            );
            
            $this->drawTable($report, $tableDetails);
        } 
        else if ($_REQUEST[$name . "_grouping"][0] != "" && $_REQUEST["grouping_1_summary"] == '1') 
        {
            $params["grouping_fields"] = $_REQUEST[$name . "_grouping"];
            $params["grouping_level"] = 0;
            $params["previous_headings"] = array();
            $params["ignored_fields"] = array();
            $this->generateSummaryTable($params);
        } 
        else 
        {
            $params["grouping_fields"] = $_REQUEST[$name . "_grouping"];
            $params["grouping_level"] = 0;
            $params["previous_headings"] = array();
            //$params["ignored_fields"] = array();
            $total = $this->generateTable($report, $params);

            if (is_array($total) && count($total) > 0) 
            {
                $total[0] = $total[0] == "" ? "Overall Total" : $total[0];
                $dataParams["widths"] = $this->widths;
                $totalTable = new TableContent($tableHeaders, $total);
                $totalTable->setDataParams($dataParams);
                $totalTable->setAsTotalsBox(true);
                $report->add($totalTable);
            }
        }
    }
    
    private function generateSection($section, $reader, $report)
    {
        $methodName = "generate" . end(explode(':', $section)) . "section";
        if(method_exists($this, $methodName))
        {
            $this->$methodName($reader, $report);
        }
    }

    /**
     * Generates the report by processing the XML file.
     * @param array $params 
     */
    public function generate($params) {
        $report = $this->getReport();

        $reader = new XMLReader();
        $reader->XML($this->xml->asXML());

        while ($reader->read()) 
        {
            if ($reader->nodeType !== XMLReader::ELEMENT) 
            {
                continue;
            }
            $this->generateSection($reader->name, $reader, $report);
        }
        $report->output();
    }

    protected function describeQuery($query) {
        $query = str_replace(array("\n", "\r"), null, $reader->readString());
        preg_match("/(SELECT )(?<fields>.*)(FROM)(?<tables>.*)(WHERE)(?<conditions>.*)/i", $query, $matches);
    }

    /**
     * Returns a form to be used to filter the report. This method analyses the
     * XML file and uses the fields specified in there to generate a very form
     * which allows you to define filter for the form. The form generated also
     * gives you options to sort and group the reports.
     * @return Form
     */
    public function getForm() 
    {
        $form = new Form();
        $filters = array();
        $tables = $this->xml->xpath("/rapi:report/rapi:table");

        /// Filters and sorting.
        foreach ($tables as $table) {
            $numConcatFields = 0;
            $fields = $table->xpath("/rapi:report/rapi:table[@name='{$table["name"]}']/rapi:fields/rapi:field");
            $filters = new TableLayout(count($fields) + 1, 5);

            $filters
                    ->add(Element::create("Label", "Field")->addCssClass("header-label"), 0, 0)
                    ->add(Element::create("Label", "Options")->addCssClass("header-label"), 0, 1)
                    ->add(Element::create("Label", "Exclude")->addCssClass("header-label"), 0, 4)
                    ->resetCssClasses()
                    ->addCssClass("filter-table")
                    ->setRenderer("default");

            $sortingField = new SelectionList("Sorting Field", "{$table["name"]}_sorting_field");
            $grouping1 = new SelectionList();

            $i = 1;

            foreach ($fields as $key => $field) {
                if (isset($field["labelsField"]))
                    continue;

                if (count(explode(",", (string) $field)) == 1) {
                    $fieldInfo = Model::resolvePath((string) $field);
                    $model = Model::load($fieldInfo["model"]);
                    $fieldName = $fieldInfo["field"];
                    $fieldInfo = $model->getFields(array($fieldName));
                    $fieldInfo = $fieldInfo[0];
                    $fields[$key] = (string) $field;

                    $sortingField->addOption(str_replace("\\n", " ", $fieldInfo["label"]), $model->getDatabase() . "." . $fieldInfo["name"]);
                    $grouping1->addOption(str_replace("\\n", " ", $field["label"]), (string) $field);

                    if (array_search($model->getKeyField(), $this->referencedFields) === false || $fieldInfo["type"] == "double" || $fieldInfo["type"] == "date") {
                        switch ($fieldInfo["type"]) {
                            case "integer":
                            case "double":
                                $filters
                                        ->add(Element::create("Label", str_replace("\\n", " ", (string) $field["label"])), $i, 0)
                                        ->add(Element::create("SelectionList", "", "{$table["name"]}.{$fieldInfo["name"]}_option")
                                                ->addOption("Equals", "EQUALS")
                                                ->addOption("Greater Than", "GREATER")
                                                ->addOption("Less Than", "LESS")
                                                ->addOption("Between", "BETWEEN")
                                                ->setValue("BETWEEN"), $i, 1)
                                        ->add(Element::create("TextField", "", "{$table["name"]}.{$fieldInfo["name"]}_start_value")->setAsNumeric(), $i, 2)
                                        ->add(Element::create("TextField", "", "{$table["name"]}.{$fieldInfo["name"]}_end_value")->setAsNumeric(), $i, 3);
                                //->add(Element::create("Checkbox","","{$table["name"]}.{$fieldInfo["name"]}_ignore","","1"),$i,4);
                                break;

                            case "date":
                            case "datetime":
                                $filters
                                        ->add(Element::create("Label", str_replace("\\n", " ", (string) $field["label"])), $i, 0)
                                        ->add(Element::create("SelectionList", "", "{$table["name"]}.{$fieldInfo["name"]}_option")
                                                ->addOption("Before", "LESS")
                                                ->addOption("After", "GREATER")
                                                ->addOption("On", "EQUALS")
                                                ->addOption("Between", "BETWEEN")
                                                ->setValue("BETWEEN"), $i, 1)
                                        ->add(Element::create("DateField", "", "{$table["name"]}.{$fieldInfo["name"]}_start_date")->setId("{$table["name"]}_{$fieldInfo["name"]}_start_date"), $i, 2)
                                        ->add(Element::create("DateField", "", "{$table["name"]}.{$fieldInfo["name"]}_end_date")->setId("{$table["name"]}_{$fieldInfo["name"]}_end_date"), $i, 3);
                                //->add(Element::create("Checkbox","","{$table["name"]}.{$fieldInfo["name"]}_ignore","","1"),$i,4);
                                break;

                            case "enum":
                                $enum_list = new SelectionList("", "{$table["name"]}.{$fieldInfo["name"]}_value");
                                $enum_list->setMultiple(true);
                                foreach ($fieldInfo["options"] as $value => $label) {
                                    $enum_list->addOption($label, $value);
                                }
                                if (!isset($field["value"])) {
                                    $filters
                                            ->add(Element::create("Label", str_replace("\\n", " ", (string) $field["label"])), $i, 0)
                                            ->add(Element::create("SelectionList", "", "{$table["name"]}.{$fieldInfo["name"]}_option")
                                                    ->addOption("Is any of", "INCLUDE")
                                                    ->addOption("Is none of", "EXCLUDE")
                                                    ->setValue("INCLUDE"), $i, 1)
                                            ->add($enum_list, $i, 2);
                                }
                                //->add(Element::create("Checkbox","","{$table["name"]}.{$fieldInfo["name"]}_ignore","","1"),$i,4);
                                break;

                            case "string":
                            case "text":
                                $filters
                                        ->add(Element::create("Label", str_replace("\\n", " ", (string) $field["label"])), $i, 0)
                                        ->add(Element::create("SelectionList", "", "{$table["name"]}.{$fieldInfo["name"]}_option")
                                                ->addOption("Is exactly", "EXACTLY")
                                                ->addOption("Contains", "CONTAINS")
                                                ->setValue("CONTAINS"), $i, 1)
                                        ->add(Element::create("TextField", "", "{$table["name"]}.{$fieldInfo["name"]}_value"), $i, 2);
                                //->add(Element::create("Checkbox","","{$table["name"]}.{$fieldInfo["name"]}_ignore","","1"),$i,4);
                                break;
                        }
                        if (isset($field["hide"])) {
                            $filters->add(Element::create("HiddenField", "{$table["name"]}.{$fieldInfo["name"]}_ignore", "1"), $i, 4);
                        } else {
                            $filters->add(Element::create("Checkbox", "", "{$table["name"]}.{$fieldInfo["name"]}_ignore", "", "1"), $i, 4);
                        }
                    } else {
                        $enum_list = new ModelSearchField();
                        $enum_list->setName("{$table["name"]}.{$fieldInfo["name"]}_value");
                        $enum_list->setModel($model, $fieldInfo["name"]);
                        $enum_list->addSearchField($fieldInfo["name"]);
                        $enum_list->boldFirst = false;
                        $filters
                                ->add(Element::create("Label", str_replace("\\n", " ", (string) $field["label"])), $i, 0)
                                ->add(Element::create("SelectionList", "", "{$table["name"]}.{$fieldInfo["name"]}_option")
                                        ->addOption("Is any of", "IS_ANY_OF")
                                        ->addOption("Is none of", "IS_NONE_OF")
                                        ->setValue("IS_ANY_OF"), $i, 1)
                                ->add(Element::create("MultiFields")->setTemplate($enum_list), $i, 2)
                                ->add(Element::create("Checkbox", "", "{$table["name"]}.{$fieldInfo["name"]}_ignore", "", "1"), $i, 4);
                    }
                } else {
                    $grouping1->addOption(str_replace("\\n", " ", $field["label"]), $field);
                    $filters
                            ->add(Element::create("Label", str_replace("\\n", " ", (string) $field["label"])), $i, 0)
                            ->add(Element::create("SelectionList", "", "{$table["name"]}_concat_{$numConcatFields}_option")
                                    ->addOption("Is exactly", "EXACTLY")
                                    ->addOption("Contains", "CONTAINS")
                                    ->setValue("CONTAINS"), $i, 1)
                            ->add(Element::create("TextField", "", "{$table["name"]}_concat_{$numConcatFields}_value"), $i, 2)
                            ->add(Element::create("Checkbox", "", "{$table["name"]}_concat_{$numConcatFields}_ignore", "", "1"), $i, 4);
                    $numConcatFields++;
                }
                $i++;
            }

            $grouping1->setName("{$table["name"]}_grouping[]")->setLabel("Grouping Field 1");
            $g1Paging = new Checkbox("Start on a new page", "grouping_1_newpage", "", "1");
            $g1Logo = new Checkbox("Repeat Logos", "grouping_1_logo", "", "1");
            $g1Summarize = new Checkbox("Summarize", "grouping_1_summary", "", "1");

            $grouping2 = clone $grouping1;
            $grouping2->setName("{$table["name"]}_grouping[]")->setLabel("Grouping Field 2");

            $grouping3 = clone $grouping1;
            $grouping3->setName("{$table["name"]}_grouping[]")->setLabel("Grouping Field 3");

            $sortingField->setLabel("Sorting Field");
            $sortingField->setName($table["name"] . "_sorting");

            $groupingTable = new TableLayout(3, 4);

            $groupingTable->add($grouping1, 0, 0);
            $groupingTable->add($g1Paging, 0, 1);
            $groupingTable->add($g1Logo, 0, 2);
            $groupingTable->add($g1Summarize, 0, 3);

            $groupingTable->add($grouping2, 1, 0);
            $groupingTable->add($grouping3, 2, 0);

            $form->add(
                    Element::create("FieldSet", "Filters")->add($filters)->setId("table_{$table['name']}"), Element::create("FieldSet", "Sorting & Limiting")->add(
                            $sortingField, Element::create("SelectionList", "Direction", "{$table["name"]}.sorting_direction")->addOption("Ascending", "ASC")->addOption("Descending", "DESC"), Element::create('TextField', 'Limit', "{$table['name']}.limit")->setAsNumeric()
                    )->setId("{$table['name']}_sorting_fs"), Element::create("FieldSet", "Grouping")->
                            setId("{$table['name']}_grouping_fs")->
                            add($groupingTable)
            );
            $sortingField->setName($table["name"] . "_sorting");
        }

        $form->setShowSubmit(false);
        
        return $form;
    }
}
