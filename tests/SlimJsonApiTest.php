<?php

class SlimJsonApiTest extends PHPUnit_Framework_TestCase
{
  public function testInit()
  {
    $app = new \Slim\Slim(array('mode' => 'foo'));

    $app->view(new \voku\slim\JsonApiView());
    $app->add(new \voku\slim\JsonApiMiddleware());

    self::assertSame('foo', $app->getMode());
  }
}
