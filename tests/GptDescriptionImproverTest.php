<?php

use PHPUnit\Framework\TestCase;
require_once dirname(__DIR__) . "/gpt_description_improver.php";

class GptDescriptionImproverTest extends TestCase
{
  private $gptDescriptionImprover;

  protected function setUp(): void
  {
    $this->gptDescriptionImprover = new GptDescriptionImprover();
  }

  public function testCallChatGPTWithValidInput()
  {
    $originalText = "This is a simple product description.";
    $improvedText = $this->gptDescriptionImprover->callChatGPT($originalText);

    $this->assertNotEquals($originalText, $improvedText);
  }

  public function testCallChatGPTWithEmptyInput()
  {
    $originalText = "";
    $improvedText = $this->gptDescriptionImprover->callChatGPT($originalText);

    $this->assertFalse($improvedText);
  }
}
