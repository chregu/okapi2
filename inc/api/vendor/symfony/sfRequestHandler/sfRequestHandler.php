<?php
class sfRequestHandler 
{ 
  protected $dispatcher = null;
  
  public function __construct(sfEventDispatcher $dispatcher) 
  { 
    $this->dispatcher = $dispatcher; 
  }
  
  public function handle($request) 
  { 
    try 
    { 
      return $this->handleRaw($request); 
    } 
    catch (Exception $e) 
    { 
      $event = $this->dispatcher->notifyUntil(new sfEvent($this, 'application.exception', array('request' => $request, 'exception' => $e))); 
      if ($event->isProcessed()) 
      { 
        return $this->filterResponse($event->getReturnValue(), 'An "application.exception" listener returned a non response object.'); 
      } 
      throw $e; 
    } 
  }
   
  public function handleRaw($request) 
  { 
    $event = $this->dispatcher->notifyUntil(new sfEvent($this, 'application.request', array('request' => $request))); 
    if ($event->isProcessed()) 
    { 
      return $this->filterResponse($event->getReturnValue(), 'An "application.request" listener returned a non response object.'); 
    }
    
    $event = $this->dispatcher->notifyUntil(new sfEvent($this, 'application.load_controller', array('request' => $request))); 
    if (!$event->isProcessed()) 
    { 
      throw new Exception('Unable to load the controller.'); 
    } 
    list($controller, $arguments) = $event->getReturnValue(); 
    
    if (!is_callable($controller)) 
    { 
      throw new Exception(sprintf('The controller must be a callable (%s).', var_export($controller, true))); 
    } 
    
    $event = $this->dispatcher->notifyUntil(new sfEvent($this, 'application.controller', array('request' => $request, 'controller' => &$controller, 'arguments' => &$arguments))); 
    if ($event->isProcessed()) 
    { 
      try 
      { 
        return $this->filterResponse($event->getReturnValue(), 'An "application.controller" listener returned a non response object.'); 
      } 
      catch (Exception $e) 
      { 
        $retval = $event->getReturnValue(); 
      } 
    } 
    else 
    { 
      $retval = call_user_func_array($controller, $arguments); 
    } 
    
    $event = $this->dispatcher->filter(new sfEvent($this, 'application.view'), $retval); 
    return $this->filterResponse($event->getReturnValue(), sprintf('The controller must return a response (instead of %s).', is_object($event->getReturnValue()) ? 'an object of class '.get_class($event->getReturnValue()) : (string) $event->getReturnValue())); 
  }
  
  protected function filterResponse($response, $message) 
  {
    if (!is_object($response) || !method_exists($response, 'send')) 
    { 
      throw new RuntimeException($message); 
    } 
    $event = $this->dispatcher->filter(new sfEvent($this, 'application.response'), $response); 
    $response = $event->getReturnValue();
    if (!is_object($response) || !method_exists($response, 'send')) 
    { 
      throw new RuntimeException('An "application.response" listener returned a non response object.'); 
    } 
    return $response; 
  }
}
