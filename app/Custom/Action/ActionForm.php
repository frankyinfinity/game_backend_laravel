<?php

namespace App\Custom\Action;

use App\Custom\Draw\Complex\Form\InputDraw;
use App\Custom\Draw\Complex\Form\SelectDraw;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Helper\Helper;
use Illuminate\Support\Str;

class ActionForm {

    private array $inputs;
    private array $selects;
    private array $extraDatas;
    private $urlRequest;
    private $submitFunctionPath;
    private array $submitFunctionParams;

    public function __construct() {
        $this->inputs = [];
        $this->selects = [];
        $this->extraDatas = [];
        $this->submitFunctionPath = null;
        $this->submitFunctionParams = [];
    }

    public function setInput(InputDraw $input) {
        $this->inputs[] = $input;
    }

    public function setSelect(SelectDraw $select) {
        $this->selects[] = $select;
    }

    public function setExtraData($key, $value) {
        $this->extraDatas[$key] = $value;
    }

    public function setUrlRequest($urlRequest) {
        $this->urlRequest = $urlRequest;
    }

    public function setSubmitFunction($path, $params = []) {
        $this->submitFunctionPath = $path;
        $this->submitFunctionParams = $params;
    }

    public function setButton(ButtonDraw $button) {

        $datas = [];
        $inputs = $this->inputs;
        $selects = $this->selects;
        foreach ($inputs as $input) {
            $name = $input->getName();
            $uid = $input->getUidValueElement();
            $datas['field_'.$name] = $uid;
        }
        foreach ($selects as $select) {
            $name = $select->getName();
            $uid = $select->getUidValueElement();
            $datas['field_'.$name] = $uid;
        }

        foreach ($this->extraDatas as $key => $value) {
            $datas['static_'.$key] = $value;
        }

        $jsPathClick = $this->submitFunctionPath ?? resource_path('js/function/entity/click_button_form.blade.php');
        $jsCode = file_get_contents($jsPathClick);
        $jsCode = str_replace('__FIELDS__', json_encode($datas), $jsCode);
        
        if ($this->urlRequest) {
            $jsCode = str_replace('__URL__', $this->urlRequest, $jsCode);
        }

        foreach ($this->submitFunctionParams as $key => $value) {
            $jsCode = str_replace($key, $value, $jsCode);
        }

        $jsCode = Helper::setCommonJsCode($jsCode, Str::random(20));

        $button->setOnClick($jsCode);
        $button->build();

    }

}