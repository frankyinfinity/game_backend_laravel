<?php

namespace App\Custom\Action;

use App\Custom\Draw\Complex\Form\InputDraw;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Helper\Helper;
use Illuminate\Support\Str;

class ActionForm {

    private array $inputs;
    public function __construct() {
        $this->inputs = [];
    }

    public function setInput(InputDraw $input) {
        $this->inputs[] = $input;
    }

    private $urlRequest;
    public function setUrlRequest($urlRequest) {
        $this->urlRequest = $urlRequest;
    }

    public function setButton(ButtonDraw $button) {

        $datas = [];
        $inputs = $this->inputs;
        foreach ($inputs as $input) {
            $name = $input->getName();
            $uid = $input->getUidValueElement();
            $datas['field_'.$name] = $uid;
        }

        $jsPathClick = resource_path('js/function/entity/click_button_form.blade.php');
        $jsPathClick = file_get_contents($jsPathClick);
        $jsPathClick = str_replace('__FIELDS__', json_encode($datas), $jsPathClick);
        $jsPathClick = str_replace('__URL__', $this->urlRequest, $jsPathClick);

        $jsPathClick = Helper::setCommonJsCode($jsPathClick, Str::random(20));

        $button->setOnClick($jsPathClick);
        $button->build();

    }

}