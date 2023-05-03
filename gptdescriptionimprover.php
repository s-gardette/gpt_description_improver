<?php
if (!defined("_PS_VERSION_")) {
  exit();
}

class GptDescriptionImprover extends Module
{
  public function __construct()
  {
    $this->name = "gpt_description_improver";
    $this->tab = "administration";
    $this->version = "1.0.0";
    $this->author = "SimonGardette";
    $this->need_instance = 0;
    $this->ps_versions_compliancy = [
      "min" => "1.7",
      "max" => _PS_VERSION_,
    ];
    $this->dependencies = [];

    $this->bootstrap = true;
    parent::__construct();

    $this->displayName = $this->l("GPT Description Improver");
    $this->description = $this->l(
      "Improve product descriptions using ChatGPT."
    );
    $this->confirmUninstall = $this->l("Are you sure you want to uninstall?");
  }

  public function install()
  {
    if (
      !parent::install() ||
      !$this->registerHook("displayAdminProductsExtra")
    ) {
      return false;
    }

    return true;
  }

  public function uninstall()
  {
    return parent::uninstall();
  }

  public function hookDisplayAdminProductsExtra($params)
  {
    $this->context->smarty->assign(
      "gpt_improve_url",
      $this->context->link->getAdminLink("AdminGptDescriptionImprover")
    );
    $this->context->smarty->assign(
      "gpt_ajax_url",
      $this->context->link->getModuleLink($this->name, "improveDescription")
    );
    return $this->display(
      __FILE__,
      "views/templates/hook/gpt_improve_button.tpl"
    );
  }
  private function callChatGPT($text)
  {
    $url = "https://api.openai.com/v1/engines/davinci-codex/completions";
    $apiKey = Configuration::get("GPT_API_KEY");

    $data = [
      "prompt" => "Improve the following product description: {$text}",
      "max_tokens" => 100,
      "n" => 1,
      "stop" => null,
      "temperature" => 0.7,
    ];

    $options = [
      "http" => [
        "header" =>
          "Content-Type: application/json\r\n" .
          "Authorization: Bearer {$apiKey}\r\n",
        "method" => "POST",
        "content" => json_encode($data),
      ],
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
      return false;
    }

    $jsonResponse = json_decode($response, true);

    if (isset($jsonResponse["choices"][0]["text"])) {
      return $jsonResponse["choices"][0]["text"];
    }

    return false;
  }
  public function ajaxProcessImproveDescription()
  {
    $originalText = Tools::getValue("description", "");
    $improvedText = $this->callChatGPT($originalText);

    if ($improvedText !== false) {
      die(
        Tools::jsonEncode(["success" => true, "improvedText" => $improvedText])
      );
    } else {
      die(Tools::jsonEncode(["success" => false]));
    }
  }

  public function getContent()
  {
    $output = null;

    if (Tools::isSubmit("submit" . $this->name)) {
      $apiKey = strval(Tools::getValue("GPT_API_KEY"));
      if (!$apiKey || empty($apiKey)) {
        $output .= $this->displayError($this->l("Invalid API Key"));
      } else {
        Configuration::updateValue("GPT_API_KEY", $apiKey);
        $output .= $this->displayConfirmation($this->l("Settings updated"));
      }
    }

    return $output . $this->displayForm();
  }
  public function displayForm()
  {
    // Get default language
    $defaultLang = (int) Configuration::get("PS_LANG_DEFAULT");

    // Init Fields form array
    $fieldsForm[0]["form"] = [
      "legend" => [
        "title" => $this->l("Settings"),
      ],
      "input" => [
        [
          "type" => "text",
          "label" => $this->l("API Key"),
          "name" => "GPT_API_KEY",
          "size" => 50,
          "required" => true,
        ],
      ],
      "submit" => [
        "title" => $this->l("Save"),
        "class" => "button",
      ],
    ];

    $helper = new HelperForm();

    // Module, token, and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite("AdminModules");
    $helper->currentIndex =
      AdminController::$currentIndex . "&configure=" . $this->name;

    // Language
    $helper->default_form_language = $defaultLang;
    $helper->allow_employee_form_lang = $defaultLang;

    // Title and toolbar
    $helper->title = $this->displayName;
    $helper->show_toolbar = true; // false -> remove toolbar
    $helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = "submit" . $this->name;
    $helper->toolbar_btn = [
      "save" => [
        "desc" => $this->l("Save"),
        "href" =>
          AdminController::$currentIndex .
          "&configure=" .
          $this->name .
          "&save" .
          $this->name .
          "&token=" .
          Tools::getAdminTokenLite("AdminModules"),
      ],
      "back" => [
        "href" =>
          AdminController::$currentIndex .
          "&token=" .
          Tools::getAdminTokenLite("AdminModules"),
        "desc" => $this->l("Back to list"),
      ],
    ];

    // Load current value
    $helper->fields_value["GPT_API_KEY"] = Configuration::get("GPT_API_KEY");

    return $helper->generateForm($fieldsForm);
  }
}
