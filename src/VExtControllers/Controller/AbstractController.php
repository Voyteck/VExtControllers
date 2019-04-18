<?php
namespace Voyteck\VExtControllers\Controller;

use \Zend\Mvc\MvcEvent;
use \Zend\Mvc\Controller\AbstractActionController;
use \Zend\View\Model\JsonModel;
use Zend\Http\Response;

abstract class AbstractController extends AbstractActionController {

  const LAZYFACTORY_INCLUDE_SERVICELOCATOR = 'serviceLocator';

  const PARAMTYPES_POST 	= 1;
  const PARAMTYPES_GET 		= 2;
  const PARAMTYPES_PARAMS 	= 4;

  const PARAMTYPES_GET_PREFIX = "get_";
  const PARAMTYPES_POST_PREFIX = "post_";
  
  protected function getAjaxResponse(int $responseCode, $additionalMsg = null, $triggerError = true) {
      if ($triggerError)
          trigger_error('AJAX reponse with code ' . $responseCode . ($additionalMsg === null ? '' : ' - ' . $additionalMsg), E_USER_ERROR);
          
      $response = $this->getResponse();
      $response->setStatusCode($responseCode);
      if ($additionalMsg !== null)
          $response->setContent($additionalMsg);
          
      return $response;
  }

  /**
   * Returns appropriate method results depending on type of request:
   *    [method_name]Action - standard method
   *    [method_name]Ajax - for Ajax calls
   *
   * @return NULL[]
   */
  protected function getMethodTypesConfig($exceptionMethodTrace = false) {
  	return [
  		'Action' 			=> function(AbstractController $object, string $methodName) {
  			$methodName .= 'Action';
  			$paramsValidationResult = $object->validateParameters($methodName);
	  		if ($paramsValidationResult === false) {
	  			trigger_error('At least one of the mandatory parameters was not provided on request ' . $object->getRequest()->getUriString(), E_USER_ERROR);
	  			return $object->notFoundAction();
	  		}
//   			return parent::onDispatch($object->getEvent());
            return call_user_func_array(array($object, $methodName), $paramsValidationResult);
  		},

  		'Ajax'				=> function(AbstractController $object, string $methodName) {
      		$methodName .= 'Ajax';
      		
      		if (!$object->getRequest()->isXmlHttpRequest())
      		    return $this->getAjaxResponse(Response::STATUS_CODE_400, $object->getRequest()->getUriString() . ' can be called only using XML HTTP Request (AJAX)');
      		    
  		    $paramsValidationResult = $object->validateParameters($methodName);
  		    if ($paramsValidationResult === false)
  		        return $this->getAjaxResponse(Response::STATUS_CODE_400, 'At least one of the mandatory parameters was not provided on request ' . $object->getRequest()->getUriString());
  		    
  		    try {
	           $returnedValue = call_user_func_array(array($object, $methodName), $paramsValidationResult);
  		    } catch (\Exception $e) {
//   		        if ($exceptionMethodTrace)
//   		            $message = $e->getTraceAsString();
//   		        else 
  		            $message = 'Exception has been raised on method call';
  		        return $this->getAjaxResponse(Response::STATUS_CODE_400, $message);
  		    }
	        if (is_array($returnedValue))
	            return new JsonModel($returnedValue);
  		            
            if (is_bool($returnedValue))
                return new JsonModel(['success' => $returnedValue]);
  		                
            return $returnedValue;
  		},

  	];
  }

  /**
   * Validates whether all mandatory parameters are provided
   * On default POST, GET and PARAMS parameters are validated - it can be steered in 2 ways:
   *    by using $paramTypes function parameter
   *    within parameter name
   *        parameters prefixed by post_* can be only passed using POST method;
   *        parameters prefixed by get_* can be only passed using GET method
   *        for prefixed parameters the real parameter tha should be passed is the one WITHOUT PREFIX
   *
   *
   * @param string $methodName
   * @param int $paramTypes
   * @return boolean|NULL[]
   */
  protected function validateParameters(string $methodName, int $paramTypes = AbstractController::PARAMTYPES_GET + AbstractController::PARAMTYPES_POST + AbstractController::PARAMTYPES_PARAMS) {
  	$ajaxMethodReflection = new \ReflectionMethod($this, $methodName);
  	$paramsArray = array();
  	foreach($ajaxMethodReflection->getParameters() as $parameter) {
  	    $queryParam = $postParam = $paramParam = $defaultParam = false;
  	    $parameterName = $parameter->getName();

  		if (strpos($parameterName, self::PARAMTYPES_GET_PREFIX) === 0) {
  		    $paramTypesChecked = self::PARAMTYPES_GET;
  		    $parameterName = substr($parameterName, strlen(self::PARAMTYPES_GET_PREFIX));
  		}
	    elseif (strpos($parameterName, self::PARAMTYPES_POST_PREFIX) === 0) {
	        $paramTypesChecked = self::PARAMTYPES_POST;
	        $parameterName = substr($parameterName, strlen(self::PARAMTYPES_POST_PREFIX));
	    }
	    else
	        $paramTypesChecked = $paramTypes;

	    if(\Voyteck\ExtLibs::byteOn($paramTypesChecked, self::PARAMTYPES_GET) && $this->getRequest()->getQuery($parameterName) !== null) {
	        $queryParam = true;
            $paramsArray[] = $this->getRequest()->getQuery($parameterName);
  		}
  		if(\Voyteck\ExtLibs::byteOn($paramTypesChecked, self::PARAMTYPES_POST) && $this->getRequest()->getPost($parameterName) !== null) {
	        $postParam = true;
	        $paramsArray[] = $this->getRequest()->getPost($parameterName);
  		}
  		if(\Voyteck\ExtLibs::byteOn($paramTypesChecked, self::PARAMTYPES_PARAMS) && $this->params('params')[$parameterName] !== null) {
  			$paramParam = true;
  			$paramsArray[] = $this->params('params')[$parameterName];
  		}

  		if (!($queryParam || $postParam || $paramParam) && $parameter->isDefaultValueAvailable()) {
  		    $defaultParam = true;
  		    $paramsArray[] = $parameter->getDefaultValue();
  		}
  		
  		if (!($queryParam || $postParam || $paramParam || $defaultParam)) {
  		    throw new \Zend\Mvc\Exception\InvalidArgumentException('Mandatory parameter ' . $parameterName . ' (' . $parameter->getName() . ') not provided for method ' . $methodName, E_USER_ERROR);
  		    return false;
  		}
  	}
  	return $paramsArray;
  }

  protected static function getMethodFromMasked(string $action, string $postfix = 'Action') {
    $method = static::getMethodFromAction($action);
    if ($postfix == 'Action')
      return $method;
    
    $method = substr($method, 0, -strlen('Action'));
    $method .= $postfix;
    
    return $method;
  }

  public function onDispatch(MvcEvent $event) {
    $routeMatch = $event->getRouteMatch();
    if (!$routeMatch)
    	throw new \Zend\Mvc\Exception\DomainException('Missing route matches; unsure how to retrieve action');

    $action = $routeMatch->getParam('action', 'not-found');

	$functionNotFound = false;
	foreach($this->getMethodTypesConfig() as $methodTypeName => $methodTypeFunction)
		if (method_exists($this, static::getMethodFromMasked($action, $methodTypeName))) {
			$actionResponse = $methodTypeFunction($this, $action);
			$functionNotFound = false;
			break;
		}
		else
			$functionNotFound = true;

	if ($functionNotFound)
		throw new \Zend\Mvc\Exception\DomainException('No function found for the action ' . $action);

    $event->setResult($actionResponse);
    return $actionResponse;
  }


  /**
   * Retrieves file that has been uploaded using AJAX transfer
   *
   * @param string $fileElementName     Name of element that is holding the transferred file needs to be provided - defaults to 'uploaded-file'
   * @param array $postData             Contains  list of other fields taht are sent together with the file (they will be stored in Hidden files of the form)
   * @return \Zend\Form\Form|boolean    Returns FALSE if the form could not be correctly parsed/validated
   */
  protected function retrieveFileData(string $fileElementName = 'uploaded-file', array $postData = array()) {
      $form = new \Zend\Form\Form();
      $fileElement = new \Zend\Form\Element\File($fileElementName);
      $form->add($fileElement);
      if (count($postData) > 0)
          foreach($postData as $fieldName)
                  $form->add(new \Zend\Form\Element\Hidden($fieldName));
      $form->setData(array_merge_recursive(
          $this->getRequest()->getPost()->toArray(),
          $this->getRequest()->getFiles()->toArray()
      ));

      if ($form->isValid())
          return $form;
      else
          return false;

  }
}
