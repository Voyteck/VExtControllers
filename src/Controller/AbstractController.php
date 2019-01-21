<?php
namespace Voyteck\Controller;

use \Zend\Mvc\MvcEvent;
use \Zend\Mvc\Controller\AbstractActionController;
use \Zend\View\Model\JsonModel;

abstract class AbstractController extends AbstractActionController {

  const LAZYFACTORY_INCLUDE_SERVICELOCATOR = 'serviceLocator';

  protected $authService;

  const PARAMTYPES_POST 	= 1;
  const PARAMTYPES_GET 		= 2;
  const PARAMTYPES_PARAMS 	= 4;

  const LAZYFACTORY_INCLUDE_TRANSLATOR = 'Translator';

  /**
   * Returns appropriate method results depending on type of request:
   *    [method_name]Action - standard method
   *    [method_name]Ajax - for Ajax calls
   *    [method_name]Confirm - for Confirm calls @todo documentation
   * 
   * @return NULL[]
   */
  protected function getMethodTypesConfig() {
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

	  		$paramsArray = array();
	  		$paramsValidationResult = $object->validateParameters($methodName);
	  		if ($paramsValidationResult === false) {
	  			trigger_error('At least one of the mandatory parameters was not provided on request ' . $object->getRequest()->getUriString(), E_USER_ERROR);
	  			$response = $this->getResponse();
	  			$response->setContent('At least one of the mandatory parameters was not provided on request ' . $object->getRequest()->getUriString());
	  			$response->setStatusCode(400);
	  			return $response;
	  			//return $object->notFoundAction();
	  		}

	  		$returnedValue = call_user_func_array(array($object, $methodName), $paramsValidationResult);
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
  public function validateParameters(string $methodName, int $paramTypes = AbstractController::PARAMTYPES_GET + AbstractController::PARAMTYPES_POST + AbstractController::PARAMTYPES_PARAMS) {
  	$ajaxMethodReflection = new \ReflectionMethod($this, $methodName);
  	$paramsArray = array();
  	foreach($ajaxMethodReflection->getParameters() as $key => $parameter) {
  		$queryParam = $postParam = $paramParam = false;
  		$parameterName = $parameter->name;
  		
  		if (strpos($parameterName, 'get_') === 0) {
  		    $paramTypesChecked = AbstractController::PARAMTYPES_GET;
  		    $parameterName = substr($parameterName, 4);
  		}
	    elseif (strpos($parameterName, 'post_') === 0) {
	        $paramTypesChecked = AbstractController::PARAMTYPES_POST;
	        $parameterName = substr($parameterName, 6);
	    }
	    else 
	        $paramTypesChecked = $paramTypes;
	    
	    if(\Voyteck\ExtLibs::byteOn($paramTypesChecked, static::PARAMTYPES_GET) && $this->getRequest()->getQuery($parameterName) !== null) {
	        $queryParam = true;
            $paramsArray[] = $this->getRequest()->getQuery($parameterName);
  		}
  		if(\Voyteck\ExtLibs::byteOn($paramTypesChecked, static::PARAMTYPES_POST) && $this->getRequest()->getPost($parameterName) !== null) {
	        $postParam = true;
	        $paramsArray[] = $this->getRequest()->getPost($parameterName);
  		}
  		if(\Voyteck\ExtLibs::byteOn($paramTypesChecked, static::PARAMTYPES_PARAMS) && $this->params('params')[$parameterName] !== null) {
  			$paramParam = true;
  			$paramsArray[] = $this->params('params')[$parameterName];
  		}

  		if (!($queryParam || $postParam || $paramParam) && $key < $ajaxMethodReflection->getNumberOfRequiredParameters()) {
  			trigger_error('Parameter ' . $parameterName . ' not provided for method ' . $methodName, E_USER_ERROR);
  			return false;
  		}
  	}
  	return $paramsArray;
  }

  public static function getMethodFromMasked(string $action, string $postfix = 'Action') {
    $method = static::getMethodFromAction($action);
    if ($postfix == 'Action')
      return $method;
    else {
      $method = substr($method, 0, -strlen('Action'));
      $method .= $postfix;
      return $method;
    }
  }

  public function onDispatch(MvcEvent $e) {
    $routeMatch = $e->getRouteMatch();
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

    $e->setResult($actionResponse);
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
      if (sizeof($postData) > 0)
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
