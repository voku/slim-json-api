<?php

namespace voku\slim;

use Slim\Slim;
use Slim\View;

/**
 * JsonApiView - view wrapper for json responses (with error code).
 */
class JsonApiView extends View
{

  /**
   * @var int
   */
  private $encodingOptions = 0;

  /**
   * @var string
   */
  private $contentType = 'application/json';

  /**
   * @var string
   */
  private $dataWraper;

  /**
   *
   * @var string
   */
  private $metaWrapper;

  /**
   *
   * @var bool
   */
  private $dataOnly = false;

  /**
   * Construct JsonApiView instance
   *
   * @param string $dataWrapper (optional) Wrapper for data in response
   * @param string $metaWrapper (optional) Wrapper for metadata in response
   */
  public function __construct($dataWrapper = null, $metaWrapper = null)
  {
    parent::__construct();

    $this->dataWraper = $dataWrapper;
    $this->metaWrapper = $metaWrapper;
  }

  /**
   * Set whether to return only the data.
   *
   * @param bool $dataOnly
   */
  public function setDataOnly($dataOnly = true)
  {
    $this->dataOnly = (bool)$dataOnly;
  }

  /**
   * @return int
   */
  public function getEncodingOptions()
  {
    return $this->encodingOptions;
  }

  /**
   * Bitmask consisting of<br /><br />
   * <b>JSON_HEX_QUOT</b>,<br />
   * <b>JSON_HEX_TAG</b>,<br />
   * <b>JSON_HEX_AMP</b>,<br />
   * <b>JSON_HEX_APOS</b>,<br />
   * <b>JSON_NUMERIC_CHECK</b>,<br />
   * <b>JSON_PRETTY_PRINT</b>,<br />
   * <b>JSON_UNESCAPED_SLASHES</b>,<br />
   * <b>JSON_FORCE_OBJECT</b>,<br />
   * <b>JSON_UNESCAPED_UNICODE</b>.<br />
   *
   * <p>The behaviour of these constants is described on the JSON constants page.</p>
   *
   * @param int $encodingOptions
   */
  public function setEncodingOptions($encodingOptions)
  {
    $this->encodingOptions = $encodingOptions;
  }

  /**
   * @return string
   */
  public function getContentType()
  {
    return $this->contentType;
  }

  /**
   * Content-Type sent through the HTTP header.
   *
   * <p>Default is set to "application/json", append ";charset=UTF-8" to force the charset</p>
   *
   * @param string $contentType
   */
  public function setContentType($contentType)
  {
    $this->contentType = $contentType;
  }

  /**
   * @param int    $status
   * @param null   $data
   * @param string $slimNameInstance
   */
  public function render($status = 200, $data = null, $slimNameInstance = 'default')
  {
    $app = Slim::getInstance($slimNameInstance);

    $status = (int)$status;

    if ($this->dataWraper) {
      $response[$this->dataWraper] = $this->all();
    } else {
      $response = $this->all();
    }

    if (!$this->dataOnly) {
      //append error bool
      if ($status < 400) {
        if ($this->metaWrapper) {
          $response[$this->metaWrapper]['error'] = false;
        } else {
          $response['error'] = false;
        }
      } else {
        if ($this->metaWrapper) {
          $response[$this->metaWrapper]['error'] = true;
        } else {
          $response['error'] = true;
        }
      }

      // append status code
      if ($this->metaWrapper) {
        $response[$this->metaWrapper]['status'] = $status;
      } else {
        $response['status'] = $status;
      }

      // add flash messages
      if (isset($this->data->flash) && is_object($this->data->flash)) {
        $flash = $this->data->flash->getMessages();

        if ($this->dataWraper) {
          unset($response[$this->dataWraper]['flash']);
        } else {
          unset($response['flash']);
        }

        if (count($flash)) {
          if ($this->metaWrapper) {
            $response[$this->metaWrapper]['flash'] = $flash;
          } else {
            $response['flash'] = $flash;
          }
        }
      }
    } else {
      unset($response['flash'], $response['status'], $response['error']);
    }

    $app->response()->status($status);
    $app->response()->header('Content-Type', $this->contentType);

    $jsonp_callback = $app->request->get('callback', null);

    if ($jsonp_callback !== null) {
      $app->response()->body($jsonp_callback . '(' . json_encode($response, $this->encodingOptions) . ')');
    } else {
      $app->response()->body(json_encode($response, $this->encodingOptions));
    }

    $app->stop();
  }
}
