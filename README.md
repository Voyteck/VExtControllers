# VExtControllers
Extensions for Zend Framework controllers

## Voyteck\VExtControllers\Controller\AbstractController
Extends the standard (Zend) AbstractActionController with possibility to distinguish between AJAX and normal (HTTP) actions.
Also validates paramters passed to the controller - now all the GET/POST/params[] parameters can be specified in the function definition, including specifying whether the paramer needs to be POST or GET, as well as with possibility to provide optional parameters

### Action methods usage
The most common way to use the class is to extend your controllers by this class. After doing this you will be able to create action methods in the following way:
* _public function testAction()_ for method that will be a standard 'test' action methiod and will work as before
* _public function testAjax()_ for method that can be called __only using Ajax__ call
* _public function testAjax($parameter1)_ for method that can e called using AJAX and needs to provide parameter1 (so the call needs to have parameter1 specified - no matter if it is via POST or GET). Important thing is that this parameter here is __mandatory__ and if not provided - the response 400 will be returned and error will be triggered
* _public function testAjax($parameter1 = 1)_ for method called using AJAX with with __non-mandatory__ parameter1 that can be passed via GET or POST
* _public function testAjax($post_parameter1)_ for method called using AJAX with mandatory parameter1 (__not post_parameter1 !__) __passed via POST__ - this parameter provided via GET will not satisfy the needs - response 400 will be returned and error will be triggered
* _public function testAjax($get_parameter1)_ for method called using Ajax with mandatory parameter1 __passed via GET__ - this parameter provided via POST will not satisfy the needs - response 400 will be returned and error will be triggered

Also the way how the function returned values are treated is modified:
* for Ajax calls (functions postfixed with Ajax) if returned value (_$returnedValue_) boolean - it is translated into _return new JsonModel(['success' => $returnedValue]);_
* for Ajax calls (functions postfixed with Ajax) if returned value (_$returnedValue_) is array - it is translated into _return new JsonModel($returnedValue);_

### protected function retrieveFileData(string $fileElementName = 'uploaded-file', array $postData = array())
Retrieves file that has been uploaded using AJAX transfer
* @param string $fileElementName     Name of element that is holding the transferred file needs to be provided - defaults to 'uploaded-file'
* @param array $postData             Contains  list of other fields taht are sent together with the file (they will be stored in Hidden files of the form)
* @return \Zend\Form\Form|boolean    Returns FALSE if the form could not be correctly parsed/validated

## Voyteck\VExtControllers\Factory\LazyControllerFactory
Factory for automatically create controllers.
If used - package voyteck/extmvc needs to be installed (specified within required packages in composer.json file - so should be already installed if you use composer).

### Usage
Easiest way to install (and use) the factory is to specify usage of it in your module.config.php file:
```
'controllers' => [
	...
	'abstract_factories' => [
		...
		\Voyteck\VExtControllers\Factory\LazyControllerFactory::class,
		...
	],
	...
],
```

